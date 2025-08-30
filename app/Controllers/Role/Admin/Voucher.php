<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\VoucherModel;
use App\Models\PembayaranModel;

class Voucher extends BaseController
{
    protected $voucherModel;
    protected $pembayaranModel;

    public function __construct()
    {
        $this->voucherModel = new VoucherModel();
        $this->pembayaranModel = new PembayaranModel();
    }

    public function index()
    {
        $vouchers = $this->voucherModel->orderBy('masa_berlaku', 'DESC')->findAll();
        
        // Add usage statistics for each voucher
        foreach ($vouchers as &$voucher) {
            $voucher['used_count'] = $this->pembayaranModel->where('id_voucher', $voucher['id_voucher'])
                                                          ->countAllResults();
            $voucher['remaining'] = max(0, $voucher['kuota'] - $voucher['used_count']);
            
            // Check if expired
            $voucher['is_expired'] = strtotime($voucher['masa_berlaku']) < time();
            
            // Auto-update status if needed
            if ($voucher['is_expired'] && $voucher['status'] === 'aktif') {
                $this->voucherModel->update($voucher['id_voucher'], ['status' => 'expired']);
                $voucher['status'] = 'expired';
            } elseif ($voucher['remaining'] <= 0 && $voucher['status'] === 'aktif') {
                $this->voucherModel->update($voucher['id_voucher'], ['status' => 'habis']);
                $voucher['status'] = 'habis';
            }
        }

        $data = [
            'vouchers' => $vouchers,
            'total_vouchers' => count($vouchers),
            'active_vouchers' => count(array_filter($vouchers, fn($v) => $v['status'] === 'aktif')),
            'expired_vouchers' => count(array_filter($vouchers, fn($v) => $v['status'] === 'expired')),
            'used_vouchers' => count(array_filter($vouchers, fn($v) => $v['status'] === 'habis'))
        ];

        return view('role/admin/voucher/index', $data);
    }

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

        // Additional validation for percentage type
        if ($this->request->getPost('tipe') === 'percentage') {
            $rules['nilai'] .= '|less_than_equal_to[100]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Check if expiry date is in the future
        $expiryDate = $this->request->getPost('masa_berlaku');
        if (strtotime($expiryDate) <= time()) {
            return redirect()->back()->withInput()->with('error', 'Masa berlaku harus di masa depan.');
        }

        $data = [
            'kode_voucher' => strtoupper($this->request->getPost('kode_voucher')),
            'tipe' => $this->request->getPost('tipe'),
            'nilai' => $this->request->getPost('nilai'),
            'kuota' => $this->request->getPost('kuota'),
            'masa_berlaku' => $expiryDate,
            'status' => 'aktif'
        ];

        if ($this->voucherModel->save($data)) {
            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil dibuat!');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat voucher.');
        }
    }

    public function edit($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->to('admin/voucher')->with('error', 'Voucher tidak ditemukan.');
        }

        return $this->response->setJSON($voucher);
    }

    public function update($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->to('admin/voucher')->with('error', 'Voucher tidak ditemukan.');
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

        // Additional validation for percentage type
        if ($this->request->getPost('tipe') === 'percentage') {
            $rules['nilai'] .= '|less_than_equal_to[100]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'kode_voucher' => strtoupper($this->request->getPost('kode_voucher')),
            'tipe' => $this->request->getPost('tipe'),
            'nilai' => $this->request->getPost('nilai'),
            'kuota' => $this->request->getPost('kuota'),
            'masa_berlaku' => $this->request->getPost('masa_berlaku'),
            'status' => $this->request->getPost('status')
        ];

        if ($this->voucherModel->update($id, $data)) {
            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil diupdate!');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate voucher.');
        }
    }

    public function delete($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->to('admin/voucher')->with('error', 'Voucher tidak ditemukan.');
        }

        // Check if voucher has been used
        $usageCount = $this->pembayaranModel->where('id_voucher', $id)->countAllResults();
        
        if ($usageCount > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus voucher yang sudah digunakan.');
        }

        if ($this->voucherModel->delete($id)) {
            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil dihapus!');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus voucher.');
        }
    }

    public function toggleStatus($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->back()->with('error', 'Voucher tidak ditemukan.');
        }

        // Don't allow status change for expired or used up vouchers
        if (in_array($voucher['status'], ['expired', 'habis'])) {
            return redirect()->back()->with('error', 'Tidak dapat mengubah status voucher yang sudah expired atau habis.');
        }

        $newStatus = $voucher['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        
        if ($this->voucherModel->update($id, ['status' => $newStatus])) {
            return redirect()->back()->with('success', 'Status voucher berhasil diubah!');
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status voucher.');
        }
    }

    public function detail($id)
    {
        $voucher = $this->voucherModel->find($id);
        
        if (!$voucher) {
            return redirect()->to('admin/voucher')->with('error', 'Voucher tidak ditemukan.');
        }

        // Get usage history
        $usageHistory = $this->pembayaranModel
                            ->select('pembayaran.*, users.nama_lengkap, users.email')
                            ->join('users', 'users.id_user = pembayaran.id_user')
                            ->where('pembayaran.id_voucher', $id)
                            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
                            ->findAll();

        // Calculate statistics
        $totalUsage = count($usageHistory);
        $totalDiscount = 0;
        
        foreach ($usageHistory as $usage) {
            if ($voucher['tipe'] === 'percentage') {
                $totalDiscount += ($usage['jumlah'] * $voucher['nilai'] / 100);
            } else {
                $totalDiscount += $voucher['nilai'];
            }
        }

        $data = [
            'voucher' => $voucher,
            'usage_history' => $usageHistory,
            'total_usage' => $totalUsage,
            'remaining_quota' => max(0, $voucher['kuota'] - $totalUsage),
            'total_discount_given' => $totalDiscount,
            'is_expired' => strtotime($voucher['masa_berlaku']) < time()
        ];

        return view('role/admin/voucher/detail', $data);
    }

    public function generateCode()
    {
        // Generate random voucher code
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // Check if code already exists
            $existing = $this->voucherModel->where('kode_voucher', $code)->first();
        } while ($existing);

        return $this->response->setJSON(['code' => $code]);
    }

    public function validateVoucher()
    {
        $code = $this->request->getPost('code');
        $amount = $this->request->getPost('amount');
        
        if (!$code || !$amount) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Kode voucher dan jumlah pembayaran diperlukan.'
            ]);
        }

        $voucher = $this->voucherModel->where('kode_voucher', strtoupper($code))->first();
        
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

        // Check expiry
        if (strtotime($voucher['masa_berlaku']) < time()) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Voucher sudah expired.'
            ]);
        }

        // Check quota
        $usedCount = $this->pembayaranModel->where('id_voucher', $voucher['id_voucher'])->countAllResults();
        if ($usedCount >= $voucher['kuota']) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Kuota voucher sudah habis.'
            ]);
        }

        // Calculate discount
        $discount = 0;
        if ($voucher['tipe'] === 'percentage') {
            $discount = ($amount * $voucher['nilai'] / 100);
        } else {
            $discount = min($voucher['nilai'], $amount); // Don't exceed total amount
        }

        return $this->response->setJSON([
            'valid' => true,
            'voucher' => $voucher,
            'discount' => $discount,
            'final_amount' => max(0, $amount - $discount),
            'message' => 'Voucher valid!'
        ]);
    }

    public function export()
    {
        $vouchers = $this->voucherModel->orderBy('masa_berlaku', 'DESC')->findAll();
        
        $filename = 'vouchers_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'Kode Voucher', 'Tipe', 'Nilai', 'Kuota', 'Masa Berlaku', 
            'Status', 'Digunakan', 'Sisa Kuota'
        ]);
        
        // CSV Data
        foreach ($vouchers as $voucher) {
            $usedCount = $this->pembayaranModel->where('id_voucher', $voucher['id_voucher'])
                                              ->countAllResults();
            
            fputcsv($output, [
                $voucher['kode_voucher'],
                $voucher['tipe'] === 'percentage' ? 'Persentase' : 'Fixed Amount',
                $voucher['tipe'] === 'percentage' ? $voucher['nilai'] . '%' : 'Rp ' . number_format($voucher['nilai'], 0, ',', '.'),
                $voucher['kuota'],
                date('d/m/Y', strtotime($voucher['masa_berlaku'])),
                ucfirst($voucher['status']),
                $usedCount,
                max(0, $voucher['kuota'] - $usedCount)
            ]);
        }
        
        fclose($output);
    }
}