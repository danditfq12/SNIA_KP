<?php
namespace App\Controllers\Middleware;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;

class PaymentCheck extends BaseController
{
    protected $pembayaranModel;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
    }

    public function checkStatus()
    {
        $userId = session('id_user');
        
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
        }

        // Get user's payment status
        $payments = $this->pembayaranModel->where('id_user', $userId)->findAll();
        
        $hasVerifiedPayment = false;
        $pendingPayments = 0;
        
        foreach ($payments as $payment) {
            if ($payment['status'] === 'verified') {
                $hasVerifiedPayment = true;
            } elseif ($payment['status'] === 'pending') {
                $pendingPayments++;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'has_verified_payment' => $hasVerifiedPayment,
                'pending_payments' => $pendingPayments,
                'total_payments' => count($payments),
                'features_unlocked' => $hasVerifiedPayment
            ]
        ]);
    }
}