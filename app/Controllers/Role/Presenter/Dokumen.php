<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Dokumen extends BaseController
{
    protected $dokumenModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $db;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel = new AbsensiModel();
        $this->db = \Config\Database::connect();
    }

    public function loa()
    {
        $userId = session('id_user');
        
        // Check if user has verified payment
        $verifiedPayment = $this->pembayaranModel->where('id_user', $userId)
                                                ->where('status', 'verified')
                                                ->first();
        
        if (!$verifiedPayment) {
            return redirect()->to('presenter/dashboard')->with('error', 'Anda harus menyelesaikan pembayaran terlebih dahulu');
        }

        // Get user's LOA documents
        $loaDokumen = $this->dokumenModel->where('id_user', $userId)
                                        ->where('tipe', 'loa')
                                        ->findAll();

        $data = [
            'loaDokumen' => $loaDokumen,
            'hasVerifiedPayment' => true
        ];

        return view('role/presenter/dokumen/loa', $data);
    }

    public function downloadLoa($filename)
    {
        $userId = session('id_user');
        
        // Check if user has verified payment
        $verifiedPayment = $this->pembayaranModel->where('id_user', $userId)
                                                ->where('status', 'verified')
                                                ->first();
        
        if (!$verifiedPayment) {
            return redirect()->to('presenter/dashboard')->with('error', 'Akses ditolak');
        }

        // Verify user owns this document
        $dokumen = $this->dokumenModel->where('id_user', $userId)
                                     ->where('tipe', 'loa')
                                     ->where('file_path', $filename)
                                     ->first();
        
        if (!$dokumen) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan');
        }

        $filepath = WRITEPATH . 'uploads/loa/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan');
        }

        return $this->response->download($filepath, null);
    }

    public function sertifikat()
    {
        $userId = session('id_user');
        
        // Check if user has attended (has absensi record)
        $hasAttended = $this->absensiModel->where('id_user', $userId)
                                         ->where('status', 'hadir')
                                         ->first();
        
        if (!$hasAttended) {
            return redirect()->to('presenter/dashboard')->with('error', 'Anda harus hadir di event untuk mendapatkan sertifikat');
        }

        // Get user's certificate documents
        $sertifikat = $this->dokumenModel->where('id_user', $userId)
                                        ->where('tipe', 'sertifikat')
                                        ->findAll();

        $data = [
            'sertifikat' => $sertifikat,
            'hasAttended' => true
        ];

        return view('role/presenter/dokumen/sertifikat', $data);
    }

    public function downloadSertifikat($filename)
    {
        $userId = session('id_user');
        
        // Check if user has attended
        $hasAttended = $this->absensiModel->where('id_user', $userId)
                                         ->where('status', 'hadir')
                                         ->first();
        
        if (!$hasAttended) {
            return redirect()->to('presenter/dashboard')->with('error', 'Akses ditolak');
        }

        // Verify user owns this document
        $dokumen = $this->dokumenModel->where('id_user', $userId)
                                     ->where('tipe', 'sertifikat')
                                     ->where('file_path', $filename)
                                     ->first();
        
        if (!$dokumen) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan');
        }

        $filepath = WRITEPATH . 'uploads/sertifikat/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan');
        }

        return $this->response->download($filepath, null);
    }
}