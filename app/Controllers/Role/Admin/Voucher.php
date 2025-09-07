<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\VoucherModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;

class Voucher extends BaseController
{
    protected $voucherModel;
    protected $pembayaranModel;
    protected $eventModel;
    protected $db;

    public function __construct()
    {
        $this->voucherModel = new VoucherModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Main index page with vouchers list and statistics
     */
    public function index()
    {
        try {
            // Get all vouchers with usage statistics
            $vouchers = $this->getVouchersWithStats();
            
            // Calculate summary statistics
            $stats = $this->calculateVoucherStatistics($vouchers);

            $data = [
                'vouchers' => $vouchers,
                'total_vouchers' => $stats['total_vouchers'],
                'active_vouchers' => $stats['active_vouchers'],
                'expired_vouchers' => $stats['expired_vouchers'],
                'used_vouchers' => $stats['used_vouchers']
            ];

            return view('role/admin/voucher/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Voucher index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data voucher.');
        }
    }

    /**
     * Get vouchers with usage statistics
     */
    private function getVouchersWithStats()
    {
        $vouchers = $this->voucherModel->orderBy('masa_berlaku', 'DESC')->findAll();
        
        foreach ($vouchers as &$voucher) {
            // Get usage count
            $usedCount = $this->pembayaranModel
                ->where('id_voucher', $voucher['id_voucher'])
                ->where('status', 'verified')
                ->countAllResults();
            
            $voucher['used_count'] = $usedCount;
            $voucher['remaining'] = max(0, $voucher['kuota'] - $usedCount);
            
            // Check if expired
            $voucher['is_expired'] = strtotime($voucher['masa_berlaku']) < time();
            
            // Update status if needed
            $this->updateVoucherStatus($voucher);
        }

        return $vouchers;
    }

    /**
     * Update voucher status based on conditions
     */
    private function updateVoucherStatus($voucher)
    {
        $currentStatus = $voucher['status'];
        $newStatus = $currentStatus;

        // Check if expired
        if (strtotime($voucher['masa_berlaku']) < time() && $currentStatus !== 'expired') {
            $newStatus = 'expired';
        }
        // Check if quota is exhausted
        else if ($voucher['remaining'] <= 0 && $currentStatus !== 'habis') {
            $newStatus = 'habis';
        }

        // Update status if changed
        if ($newStatus !== $currentStatus) {
            $this->voucherModel->update($voucher['id_voucher'], ['status' => $newStatus]);
        }
    }

    /**
     * Calculate voucher statistics
     */
    private function calculateVoucherStatistics($vouchers)
    {
        $stats = [
            'total_vouchers' => count($vouchers),
            'active_vouchers' => 0,
            'expired_vouchers' => 0,
            'used_vouchers' => 0
        ];

        foreach ($vouchers as $voucher) {
            switch ($voucher['status']) {
                case 'aktif':
                    $stats['active_vouchers']++;
                    break;
                case 'expired':
                    $stats['expired_vouchers']++;
                    break;
                case 'habis':
                    $stats['used_vouchers']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Store new voucher
     */
    public function store()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'kode_voucher' => 'required|min_length[3]|max_length[50]|is_unique[voucher.kode_voucher]',
            'tipe' => 'required|in_list[percentage,fixed]',
            'nilai' => 'required|numeric|greater_than[0]',
            'kuota' => 'required|integer|greater_than[0]',
            'masa_berlaku' => 'required|valid_date'
        ];

        // Additional validation for percentage
        $tipe = $this->request->getPost('tipe');
        if ($tipe === 'percentage') {
            $rules['nilai'] .= '|less_than_equal_to[100]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Validate expiration date
        $masaBerlaku = $this->request->getPost('masa_berlaku');
        if (strtotime($masaBerlaku) <= time()) {
            return redirect()->back()->withInput()->with('error', 'Masa berlaku harus di masa depan.');
        }

        try {
            $voucherData = [
                'kode_voucher' => strtoupper(trim($this->request->getPost('kode_voucher'))),
                'tipe' => $this->request->getPost('tipe'),
                'nilai' => $this->request->getPost('nilai'),
                'kuota' => $this->request->getPost('kuota'),
                'masa_berlaku' => $masaBerlaku,
                'status' => 'aktif'
            ];

            if (!$this->voucherModel->save($voucherData)) {
                throw new \Exception('Gagal menyimpan voucher: ' . implode(', ', $this->voucherModel->errors()));
            }

            $newVoucherId = $this->voucherModel->getInsertID();
            
            // Log activity
            $this->logActivity(session('id_user'), "Created new voucher: {$voucherData['kode_voucher']} (ID: {$newVoucherId})");

            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil ditambahkan!');

        } catch (\Exception $e) {
            log_message('error', 'Voucher creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Get voucher data for editing
     */
    public function edit($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return $this->response->setJSON(['error' => 'Voucher tidak ditemukan.']);
        }

        return $this->response->setJSON($voucher);
    }

    /**
     * Update existing voucher
     */
    public function update($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->back()->with('error', 'Voucher tidak ditemukan.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'kode_voucher' => "required|min_length[3]|max_length[50]|is_unique[voucher.kode_voucher,id_voucher,$id]",
            'tipe' => 'required|in_list[percentage,fixed]',
            'nilai' => 'required|numeric|greater_than[0]',
            'kuota' => 'required|integer|greater_than[0]',
            'masa_berlaku' => 'required|valid_date',
            'status' => 'required|in_list[aktif,nonaktif,expired,habis]'
        ];

        // Additional validation for percentage
        $tipe = $this->request->getPost('tipe');
        if ($tipe === 'percentage') {
            $rules['nilai'] .= '|less_than_equal_to[100]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Check if voucher is being used
        $usageCount = $this->pembayaranModel->where('id_voucher', $id)->countAllResults();
        
        // Don't allow critical changes if voucher is already used
        if ($usageCount > 0) {
            $originalVoucher = $voucher;
            $newKuota = $this->request->getPost('kuota');
            
            if ($newKuota < $usageCount) {
                return redirect()->back()->withInput()->with('error', "Kuota tidak boleh kurang dari jumlah penggunaan ({$usageCount}).");
            }
        }

        try {
            $voucherData = [
                'kode_voucher' => strtoupper(trim($this->request->getPost('kode_voucher'))),
                'tipe' => $this->request->getPost('tipe'),
                'nilai' => $this->request->getPost('nilai'),
                'kuota' => $this->request->getPost('kuota'),
                'masa_berlaku' => $this->request->getPost('masa_berlaku'),
                'status' => $this->request->getPost('status')
            ];

            if (!$this->voucherModel->update($id, $voucherData)) {
                throw new \Exception('Gagal memperbarui voucher: ' . implode(', ', $this->voucherModel->errors()));
            }

            // Log activity
            $this->logActivity(session('id_user'), "Updated voucher: {$voucherData['kode_voucher']} (ID: {$id})");

            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil diperbarui!');

        } catch (\Exception $e) {
            log_message('error', 'Voucher update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Toggle voucher status
     */
    public function toggleStatus($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->back()->with('error', 'Voucher tidak ditemukan.');
        }

        // Don't allow status change if expired or quota exhausted
        if (in_array($voucher['status'], ['expired', 'habis'])) {
            return redirect()->back()->with('error', 'Tidak dapat mengubah status voucher yang expired atau habis kuota.');
        }

        $newStatus = $voucher['status'] === 'aktif' ? 'nonaktif' : 'aktif';

        try {
            if ($this->voucherModel->update($id, ['status' => $newStatus])) {
                // Log activity
                $this->logActivity(session('id_user'), "Changed voucher '{$voucher['kode_voucher']}' status to {$newStatus}");
                
                $message = $newStatus === 'aktif' ? 'Voucher berhasil diaktifkan!' : 'Voucher berhasil dinonaktifkan!';
                return redirect()->back()->with('success', $message);
            }
            
            return redirect()->back()->with('error', 'Gagal mengubah status voucher.');

        } catch (\Exception $e) {
            log_message('error', 'Toggle voucher status error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete voucher (soft delete by changing status)
     */
    public function delete($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->back()->with('error', 'Voucher tidak ditemukan.');
        }

        // Check if voucher is being used
        $usageCount = $this->pembayaranModel->where('id_voucher', $id)->countAllResults();
        
        if ($usageCount > 0) {
            return redirect()->back()->with('error', "Tidak dapat menghapus voucher yang sudah digunakan ({$usageCount} kali).");
        }

        try {
            if ($this->voucherModel->delete($id)) {
                // Log activity
                $this->logActivity(session('id_user'), "Deleted voucher: {$voucher['kode_voucher']} (ID: {$id})");
                
                return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil dihapus!');
            }
            
            return redirect()->back()->with('error', 'Gagal menghapus voucher.');

        } catch (\Exception $e) {
            log_message('error', 'Delete voucher error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error menghapus voucher: ' . $e->getMessage());
        }
    }

    /**
     * Force delete voucher (permanent deletion)
     */
    public function forceDelete($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->back()->with('error', 'Voucher tidak ditemukan.');
        }

        // Check usage
        $usageCount = $this->pembayaranModel->where('id_voucher', $id)->countAllResults();
        
        if ($usageCount > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus permanen voucher yang memiliki riwayat penggunaan.');
        }

        $this->db->transStart();

        try {
            // Force delete
            if (!$this->voucherModel->delete($id, true)) {
                throw new \Exception('Failed to permanently delete voucher');
            }

            // Log activity
            $this->logActivity(session('id_user'), "Force deleted voucher: {$voucher['kode_voucher']} (ID: {$id})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil dihapus permanen!');

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Force delete voucher error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Voucher detail page
     */
    public function detail($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->to('admin/voucher')->with('error', 'Voucher tidak ditemukan.');
        }

        try {
            // Get usage history with user and event details
            $usageHistory = $this->getVoucherUsageHistory($id);
            
            // Calculate statistics
            $totalUsed = count($usageHistory);
            $remaining = max(0, $voucher['kuota'] - $totalUsed);
            $totalDiscount = array_sum(array_column($usageHistory, 'discount_amount'));

            $data = [
                'voucher' => $voucher,
                'usage_history' => $usageHistory,
                'total_used' => $totalUsed,
                'remaining' => $remaining,
                'total_discount' => $totalDiscount
            ];

            return view('role/admin/voucher/detail', $data);

        } catch (\Exception $e) {
            log_message('error', 'Voucher detail error: ' . $e->getMessage());
            return redirect()->to('admin/voucher')->with('error', 'Terjadi kesalahan saat memuat detail voucher.');
        }
    }

    /**
     * Get voucher usage history with details
     */
    private function getVoucherUsageHistory($voucherId)
    {
        return $this->db->table('pembayaran')
            ->select('
                pembayaran.*,
                users.nama_lengkap,
                users.email,
                events.title as event_title,
                pembayaran.original_amount - pembayaran.jumlah as discount_amount
            ')
            ->join('users', 'users.id_user = pembayaran.id_user')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_voucher', $voucherId)
            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Generate random voucher code
     */
    public function generateCode()
    {
        $prefix = 'SNIA';
        $randomString = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
        $code = $prefix . $randomString;
        
        // Ensure uniqueness
        $attempts = 0;
        while ($this->voucherModel->where('kode_voucher', $code)->first() && $attempts < 10) {
            $randomString = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
            $code = $prefix . $randomString;
            $attempts++;
        }

        return $this->response->setJSON(['code' => $code]);
    }

    /**
     * Validate voucher for payment
     */
    public function validateVoucher()
    {
        $kodeVoucher = $this->request->getPost('kode_voucher');
        $userId = $this->request->getPost('user_id');
        $eventId = $this->request->getPost('event_id');

        if (!$kodeVoucher) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Kode voucher tidak boleh kosong.'
            ]);
        }

        $voucher = $this->voucherModel->where('kode_voucher', strtoupper($kodeVoucher))->first();

        if (!$voucher) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Kode voucher tidak ditemukan.'
            ]);
        }

        // Check status
        if ($voucher['status'] !== 'aktif') {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Voucher tidak aktif.'
            ]);
        }

        // Check expiration
        if (strtotime($voucher['masa_berlaku']) < time()) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Voucher sudah expired.'
            ]);
        }

        // Check quota
        $usedCount = $this->pembayaranModel
            ->where('id_voucher', $voucher['id_voucher'])
            ->where('status', 'verified')
            ->countAllResults();

        if ($usedCount >= $voucher['kuota']) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Kuota voucher sudah habis.'
            ]);
        }

        // Check if user already used this voucher for this event
        if ($userId && $eventId) {
            $userUsage = $this->pembayaranModel
                ->where('id_voucher', $voucher['id_voucher'])
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->first();

            if ($userUsage) {
                return $this->response->setJSON([
                    'valid' => false,
                    'message' => 'Anda sudah menggunakan voucher ini untuk event ini.'
                ]);
            }
        }

        return $this->response->setJSON([
            'valid' => true,
            'voucher' => $voucher,
            'message' => 'Voucher valid dan dapat digunakan.'
        ]);
    }

    /**
     * Export vouchers data to CSV
     */
    public function export()
    {
        try {
            $vouchers = $this->getVouchersWithStats();
            
            $filename = 'vouchers_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($output, [
                'ID', 'Kode Voucher', 'Tipe', 'Nilai', 'Kuota', 
                'Digunakan', 'Sisa', 'Masa Berlaku', 'Status', 'Expired'
            ]);
            
            // CSV Data
            foreach ($vouchers as $voucher) {
                fputcsv($output, [
                    $voucher['id_voucher'],
                    $voucher['kode_voucher'],
                    ucfirst($voucher['tipe']),
                    $voucher['tipe'] === 'percentage' ? $voucher['nilai'] . '%' : 'Rp ' . number_format($voucher['nilai'], 0, ',', '.'),
                    $voucher['kuota'],
                    $voucher['used_count'],
                    $voucher['remaining'],
                    date('d/m/Y', strtotime($voucher['masa_berlaku'])),
                    ucfirst($voucher['status']),
                    $voucher['is_expired'] ? 'Ya' : 'Tidak'
                ]);
            }
            
            fclose($output);
            
            // Log activity
            $this->logActivity(session('id_user'), "Exported vouchers data (" . count($vouchers) . " records)");
            
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export vouchers error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data voucher.');
        }
    }

    /**
     * Log activity helper
     */
    private function logActivity($userId, $activity)
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}