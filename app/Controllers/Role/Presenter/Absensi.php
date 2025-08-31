<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbsensiModel;
use App\Models\PembayaranModel;

class Absensi extends BaseController
{
    protected $absensiModel;
    protected $pembayaranModel;
    protected $db;

    public function __construct()
    {
        $this->absensiModel = new AbsensiModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        // Check if user has verified payment
        $verifiedPayment = $this->pembayaranModel->where('id_user', $userId)
                                                ->where('status', 'verified')
                                                ->first();
        
        if (!$verifiedPayment) {
            return redirect()->to('presenter/dashboard')->with('error', 'Anda harus menyelesaikan pembayaran terlebih dahulu');
        }

        // Get user's attendance records
        $absensi = $this->absensiModel->where('id_user', $userId)->findAll();

        $data = [
            'absensi' => $absensi,
            'hasVerifiedPayment' => true
        ];

        return view('role/presenter/absensi/index', $data);
    }

    public function scan()
    {
        $userId = session('id_user');
        
        // Check if user has verified payment
        $verifiedPayment = $this->pembayaranModel->where('id_user', $userId)
                                                ->where('status', 'verified')
                                                ->first();
        
        if (!$verifiedPayment) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pembayaran belum terverifikasi'
            ]);
        }

        $qrCode = $this->request->getPost('qr_code');
        
        if (!$qrCode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'QR Code tidak valid'
            ]);
        }

        // Check if user already scanned today
        $today = date('Y-m-d');
        $existingAbsensi = $this->absensiModel->where('id_user', $userId)
                                             ->where('DATE(waktu_scan)', $today)
                                             ->first();
        
        if ($existingAbsensi) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda sudah absen hari ini'
            ]);
        }

        // Validate QR code (should match today's date or event code)
        $expectedQrCode = 'SNIA_' . date('Ymd');
        
        if ($qrCode !== $expectedQrCode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'QR Code tidak valid untuk hari ini'
            ]);
        }

        // Record attendance
        $data = [
            'id_user' => $userId,
            'qr_code' => $qrCode,
            'status' => 'hadir',
            'waktu_scan' => date('Y-m-d H:i:s')
        ];

        if ($this->absensiModel->insert($data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Absensi berhasil dicatat'
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Gagal mencatat absensi'
        ]);
    }
}