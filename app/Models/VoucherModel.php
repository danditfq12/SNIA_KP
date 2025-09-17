<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table      = 'voucher';
    protected $primaryKey = 'id_voucher';
    
    protected $allowedFields = [
        'kode_voucher', 'tipe', 'nilai', 'kuota', 
        'masa_berlaku', 'status'
    ];

    protected $useTimestamps = false;

    /**
     * ENHANCED: Find valid voucher with comprehensive checks
     */
    public function findValidVoucher(string $code, int $userId = 0, int $eventId = 0): ?array
    {
        $code = trim($code);
        if ($code === '') return null;

        $voucher = $this->select('voucher.*, 
                                (SELECT COUNT(*) FROM pembayaran 
                                 WHERE pembayaran.id_voucher = voucher.id_voucher 
                                 AND pembayaran.status IN ("pending", "verified")) AS used_count')
                        ->where('LOWER(TRIM(kode_voucher)) =', strtolower($code))
                        ->where('status', 'aktif')
                        ->first();

        if (!$voucher) return null;

        // Check expiry
        if (!empty($voucher['masa_berlaku'])) {
            $expiryTimestamp = strtotime($voucher['masa_berlaku'] . ' 23:59:59');
            if ($expiryTimestamp && $expiryTimestamp < time()) {
                return null; // Expired
            }
        }

        // Check quota
        $usedCount = (int)($voucher['used_count'] ?? 0);
        $maxQuota = (int)($voucher['kuota'] ?? 0);
        
        if ($maxQuota > 0 && $usedCount >= $maxQuota) {
            return null; // Quota exhausted
        }

        // Check if user already used this voucher for this event
        if ($userId > 0 && $eventId > 0) {
            $alreadyUsed = $this->db->table('pembayaran')
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('id_voucher', $voucher['id_voucher'])
                ->whereIn('status', ['pending', 'verified'])
                ->countAllResults();

            if ($alreadyUsed > 0) {
                return null; // Already used by this user for this event
            }
        }

        return $voucher;
    }

    /**
     * Get active vouchers with usage information
     */
    public function getActiveVouchers()
    {
        return $this->select('voucher.*, 
                            (SELECT COUNT(*) FROM pembayaran 
                             WHERE pembayaran.id_voucher = voucher.id_voucher 
                             AND pembayaran.status IN ("pending", "verified")) AS used_count')
                    ->where('status', 'aktif')
                    ->where('(masa_berlaku IS NULL OR masa_berlaku >= CURRENT_DATE)')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * ENHANCED: Reduce voucher quota with atomic operation
     */
    public function reduceQuota(int $voucherId): bool
    {
        if ($voucherId <= 0) return false;

        $this->db->transStart();

        try {
            // Lock the voucher row for update
            $voucher = $this->db->table($this->table)
                                ->where('id_voucher', $voucherId)
                                ->where('status', 'aktif')
                                ->get()
                                ->getRowArray();

            if (!$voucher) {
                $this->db->transRollback();
                return false;
            }

            $currentQuota = (int)($voucher['kuota'] ?? 0);
            
            if ($currentQuota > 0) {
                $newQuota = $currentQuota - 1;
                $updateData = ['kuota' => $newQuota];
                
                // Mark as exhausted if quota reaches 0
                if ($newQuota <= 0) {
                    $updateData['status'] = 'habis';
                }
                
                $this->db->table($this->table)
                         ->where('id_voucher', $voucherId)
                         ->update($updateData);
                
                log_message('info', "Voucher quota reduced: ID {$voucherId}, new quota: {$newQuota}");
            }

            $this->db->transComplete();
            return $this->db->transStatus() !== false;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Failed to reduce voucher quota: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get voucher statistics
     */
    public function getVoucherStats(): array
    {
        $stats = $this->db->query("
            SELECT 
                COUNT(*) as total_vouchers,
                COUNT(CASE WHEN status = 'aktif' THEN 1 END) as active_vouchers,
                COUNT(CASE WHEN status = 'habis' THEN 1 END) as exhausted_vouchers,
                COUNT(CASE WHEN status = 'nonaktif' THEN 1 END) as inactive_vouchers,
                COUNT(CASE WHEN masa_berlaku IS NOT NULL AND masa_berlaku < CURRENT_DATE THEN 1 END) as expired_vouchers,
                SUM(CASE WHEN tipe IN ('percentage', 'persen') THEN 0 ELSE nilai END) as total_nominal_value,
                AVG(CASE WHEN tipe IN ('percentage', 'persen') THEN nilai ELSE 0 END) as avg_percentage_discount
            FROM voucher
        ")->getRowArray();

        // Get usage statistics
        $usageStats = $this->db->query("
            SELECT 
                v.tipe,
                COUNT(p.id_pembayaran) as usage_count,
                SUM(CASE WHEN p.discount_amount IS NOT NULL THEN p.discount_amount ELSE 0 END) as total_discount_given,
                AVG(CASE WHEN p.discount_amount IS NOT NULL THEN p.discount_amount ELSE NULL END) as avg_discount_amount
            FROM voucher v
            LEFT JOIN pembayaran p ON p.id_voucher = v.id_voucher AND p.status = 'verified'
            GROUP BY v.tipe
        ")->getResultArray();

        return [
            'overview' => $stats,
            'by_type' => $usageStats
        ];
    }

    /**
     * Check and update expired vouchers
     */
    public function updateExpiredVouchers(): int
    {
        $expiredCount = $this->where('masa_berlaku IS NOT NULL')
                             ->where('masa_berlaku <', date('Y-m-d'))
                             ->where('status !=', 'kedaluwarsa')
                             ->set(['status' => 'kedaluwarsa'])
                             ->update();

        if ($expiredCount > 0) {
            log_message('info', "Updated {$expiredCount} expired vouchers");
        }

        return $this->db->affectedRows();
    }

    /**
     * Validate voucher code format
     */
    public function validateVoucherCode(string $code): array
    {
        $code = trim(strtoupper($code));
        $errors = [];

        if (empty($code)) {
            $errors[] = 'Kode voucher tidak boleh kosong';
            return ['valid' => false, 'errors' => $errors];
        }

        if (strlen($code) < 3) {
            $errors[] = 'Kode voucher minimal 3 karakter';
        }

        if (strlen($code) > 20) {
            $errors[] = 'Kode voucher maksimal 20 karakter';
        }

        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            $errors[] = 'Kode voucher hanya boleh mengandung huruf, angka, underscore, dan dash';
        }

        // Check if code already exists
        $existing = $this->where('UPPER(TRIM(kode_voucher))', $code)->first();
        if ($existing) {
            $errors[] = 'Kode voucher sudah digunakan';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'suggested_code' => empty($errors) ? $code : null
        ];
    }

    /**
     * Generate unique voucher code
     */
    public function generateUniqueCode(string $prefix = 'SNIA', int $length = 8): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $randomPart = strtoupper(substr(md5(uniqid() . time()), 0, $length - strlen($prefix) - 1));
            $code = $prefix . '_' . $randomPart;
            
            $exists = $this->where('UPPER(TRIM(kode_voucher))', strtoupper($code))->first();
            $attempt++;
            
        } while ($exists && $attempt < $maxAttempts);

        if ($exists) {
            // Fallback with timestamp
            $code = $prefix . '_' . date('Ymd') . '_' . strtoupper(substr(uniqid(), -4));
        }

        return $code;
    }

    /**
     * Create voucher with validation
     */
    public function createVoucher(array $data): array
    {
        // Validate required fields
        $required = ['kode_voucher', 'tipe', 'nilai'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return [
                'success' => false,
                'message' => 'Field required: ' . implode(', ', $missing)
            ];
        }

        // Validate voucher code
        $codeValidation = $this->validateVoucherCode($data['kode_voucher']);
        if (!$codeValidation['valid']) {
            return [
                'success' => false,
                'message' => implode(', ', $codeValidation['errors'])
            ];
        }

        // Validate voucher type and value
        $validTypes = ['percentage', 'persen', 'fixed', 'nominal'];
        if (!in_array(strtolower($data['tipe']), $validTypes)) {
            return [
                'success' => false,
                'message' => 'Tipe voucher tidak valid. Gunakan: percentage, persen, fixed, atau nominal'
            ];
        }

        $nilai = (int)($data['nilai'] ?? 0);
        if ($nilai <= 0) {
            return [
                'success' => false,
                'message' => 'Nilai voucher harus lebih besar dari 0'
            ];
        }

        // For percentage vouchers, limit to 100%
        if (in_array(strtolower($data['tipe']), ['percentage', 'persen']) && $nilai > 100) {
            return [
                'success' => false,
                'message' => 'Nilai persentase tidak boleh lebih dari 100%'
            ];
        }

        // Prepare data for insertion
        $insertData = [
            'kode_voucher' => strtoupper(trim($data['kode_voucher'])),
            'tipe' => strtolower($data['tipe']),
            'nilai' => $nilai,
            'kuota' => max(0, (int)($data['kuota'] ?? 0)),
            'masa_berlaku' => $data['masa_berlaku'] ?? null,
            'status' => 'aktif'
        ];

        try {
            $voucherId = $this->insert($insertData);
            
            if ($voucherId) {
                return [
                    'success' => true,
                    'message' => 'Voucher berhasil dibuat',
                    'voucher_id' => $voucherId,
                    'voucher_code' => $insertData['kode_voucher']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menyimpan voucher ke database'
                ];
            }

        } catch (\Exception $e) {
            log_message('error', 'Failed to create voucher: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get voucher usage history
     */
    public function getVoucherUsageHistory(int $voucherId): array
    {
        return $this->db->table('pembayaran p')
            ->select('
                p.id_pembayaran, p.tanggal_bayar, p.jumlah, 
                COALESCE(p.discount_amount, 0) as discount_amount, 
                p.status,
                u.nama_lengkap, u.email,
                e.title as event_title
            ')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->join('events e', 'e.id = p.event_id', 'left')
            ->where('p.id_voucher', $voucherId)
            ->orderBy('p.tanggal_bayar', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get voucher report for admin
     */
    public function getVoucherReport(string $startDate = '', string $endDate = ''): array
    {
        $builder = $this->db->table('voucher v')
            ->select('
                v.*,
                COUNT(p.id_pembayaran) as total_usage,
                SUM(CASE WHEN p.status = "verified" THEN COALESCE(p.discount_amount, 0) ELSE 0 END) as total_discount_given,
                SUM(CASE WHEN p.status = "pending" THEN 1 ELSE 0 END) as pending_usage,
                MAX(p.tanggal_bayar) as last_used
            ')
            ->join('pembayaran p', 'p.id_voucher = v.id_voucher', 'left')
            ->groupBy('v.id_voucher');

        if (!empty($startDate)) {
            $builder->where('p.tanggal_bayar >=', $startDate);
        }

        if (!empty($endDate)) {
            $builder->where('p.tanggal_bayar <=', $endDate . ' 23:59:59');
        }

        return $builder->orderBy('v.id_voucher', 'DESC')
                       ->get()
                       ->getResultArray();
    }
}