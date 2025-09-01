<?php
namespace App\Controllers\Middleware;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;

class FeatureUnlock extends BaseController
{
    protected $pembayaranModel;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
    }

    public function process()
    {
        $userId = session('id_user');
        $userRole = session('role');
        
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
        }

        // Check if user has verified payment
        $verifiedPayment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('status', 'verified')
            ->first();

        $features = [];
        
        if ($verifiedPayment) {
            if ($userRole === 'presenter') {
                $features = [
                    'attendance_scanning' => true,
                    'loa_download' => true,
                    'certificate_download' => true,
                    'presenter_dashboard' => true,
                    'abstract_submission' => true
                ];
            } elseif ($userRole === 'audience') {
                $features = [
                    'attendance_scanning' => true,
                    'certificate_download' => true,
                    'audience_materials' => true
                ];
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'features_unlocked' => !empty($features),
                'available_features' => $features,
                'payment_verified' => $verifiedPayment !== null,
                'payment_date' => $verifiedPayment ? $verifiedPayment['verified_at'] : null
            ]
        ]);
    }
}