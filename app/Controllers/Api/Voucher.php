<?php
namespace App\Controllers\Api;

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

    public function validateVoucher()  // Ganti nama method
    {
        $input = $this->request->getJSON(true);
        $kodeVoucher = $input['kode_voucher'] ?? '';
        $amount = $input['amount'] ?? 0;

        if (!$kodeVoucher || !$amount) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Voucher code and amount are required'
            ]);
        }

        $voucher = $this->voucherModel->where('kode_voucher', strtoupper($kodeVoucher))->first();
        
        if (!$voucher) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Voucher code not found'
            ]);
        }

        // Check status
        if ($voucher['status'] !== 'aktif') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Voucher is not active'
            ]);
        }

        // Check expiry
        if (strtotime($voucher['masa_berlaku']) < time()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Voucher has expired'
            ]);
        }

        // Check quota
        $usedCount = $this->pembayaranModel->where('id_voucher', $voucher['id_voucher'])->countAllResults();
        if ($usedCount >= $voucher['kuota']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Voucher quota has been exhausted'
            ]);
        }

        // Calculate discount
        $discount = 0;
        if ($voucher['tipe'] === 'percentage') {
            $discount = ($amount * $voucher['nilai'] / 100);
        } else {
            $discount = min($voucher['nilai'], $amount);
        }

        $finalAmount = max(0, $amount - $discount);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Voucher is valid and applied successfully',
            'voucher' => $voucher,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'formatted_discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
            'formatted_final_amount' => 'Rp ' . number_format($finalAmount, 0, ',', '.'),
            'remaining_quota' => $voucher['kuota'] - $usedCount
        ]);
    }
}