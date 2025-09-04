<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\UserModel;
use App\Models\VoucherModel;

class Pembayaran extends BaseController
{
    protected $pembayaranModel;
    protected $userModel;
    protected $voucherModel;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
        $this->userModel = new UserModel();
        $this->voucherModel = new VoucherModel();
    }

    public function index()
    {
        // Get pembayaran with user details
        $pembayarans = $this->pembayaranModel->getPembayaranWithUser();
        
        // Add voucher info if exists
        foreach ($pembayarans as &$pembayaran) {
            if ($pembayaran['id_voucher']) {
                $voucher = $this->voucherModel->find($pembayaran['id_voucher']);
                $pembayaran['voucher_info'] = $voucher;
            } else {
                $pembayaran['voucher_info'] = null;
            }
        }

        // Get statistics
        $data = [
            'pembayarans' => $pembayarans,
            'total_pembayaran' => $this->pembayaranModel->countAll(),
            'pembayaran_pending' => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
            'pembayaran_verified' => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
            'pembayaran_rejected' => $this->pembayaranModel->where('status', 'rejected')->countAllResults(),
            'total_revenue' => $this->pembayaranModel
                                   ->selectSum('jumlah')
                                   ->where('status', 'verified')
                                   ->first()['jumlah'] ?? 0,
        ];

        return view('role/admin/pembayaran/index', $data);
    }

    public function verifikasi($id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->find($id_pembayaran);
        
        if (!$pembayaran) {
            return redirect()->to('admin/pembayaran')->with('error', 'Pembayaran tidak ditemukan.');
        }

        $status = $this->request->getPost('status');
        $keterangan = $this->request->getPost('keterangan');

        // Validate status
        if (!in_array($status, ['verified', 'rejected'])) {
            return redirect()->back()->with('error', 'Status tidak valid.');
        }

        // Update pembayaran status
        $updateData = [
            'status' => $status,
            'verified_at' => ($status === 'verified') ? date('Y-m-d H:i:s') : null,
            'verified_by' => session('id_user'),
            'keterangan' => $keterangan
        ];

        if ($this->pembayaranModel->update($id_pembayaran, $updateData)) {
            // If verified and there's voucher, decrease voucher quota
            if ($status === 'verified' && $pembayaran['id_voucher']) {
                $voucher = $this->voucherModel->find($pembayaran['id_voucher']);
                if ($voucher && $voucher['kuota'] > 0) {
                    $this->voucherModel->update($pembayaran['id_voucher'], [
                        'kuota' => $voucher['kuota'] - 1
                    ]);
                    
                    // Deactivate voucher if quota is 0
                    if ($voucher['kuota'] - 1 <= 0) {
                        $this->voucherModel->update($pembayaran['id_voucher'], [
                            'status' => 'habis'
                        ]);
                    }
                }
            }

            $message = $status === 'verified' ? 'Pembayaran berhasil diverifikasi!' : 'Pembayaran berhasil ditolak!';
            return redirect()->to('admin/pembayaran')->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'Gagal memperbarui status pembayaran.');
        }
    }

    public function detail($id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->select('pembayaran.*, users.nama_lengkap, users.email, users.role')
                                           ->join('users', 'users.id_user = pembayaran.id_user')
                                           ->where('pembayaran.id_pembayaran', $id_pembayaran)
                                           ->first();

        if (!$pembayaran) {
            return redirect()->to('admin/pembayaran')->with('error', 'Pembayaran tidak ditemukan.');
        }

        // Get voucher info if exists
        $voucher = null;
        if ($pembayaran['id_voucher']) {
            $voucher = $this->voucherModel->find($pembayaran['id_voucher']);
        }

        // Get verification info
        $verifiedBy = null;
        if ($pembayaran['verified_by']) {
            $verifiedBy = $this->userModel->find($pembayaran['verified_by']);
        }

        $data = [
            'pembayaran' => $pembayaran,
            'voucher' => $voucher,
            'verified_by' => $verifiedBy
        ];

        return view('role/admin/pembayaran/detail', $data);
    }

    public function downloadBukti($id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->find($id_pembayaran);
        
        if (!$pembayaran) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Pembayaran tidak ditemukan.');
        }

        // Fix: Use the correct path to uploads folder
        $filePath = ROOTPATH . 'writable/uploads/pembayaran/' . $pembayaran['bukti_bayar'];
        
        // Check if file exists
        if (!file_exists($filePath)) {
            // Try alternative path in case uploads is in public
            $alternativePath = FCPATH . 'uploads/pembayaran/' . $pembayaran['bukti_bayar'];
            if (file_exists($alternativePath)) {
                $filePath = $alternativePath;
            } else {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('File bukti pembayaran tidak ditemukan.');
            }
        }

        // Get file info for proper headers
        $fileInfo = pathinfo($filePath);
        $mimeType = mime_content_type($filePath);
        
        // Set proper filename for download
        $downloadName = 'bukti_pembayaran_' . $id_pembayaran . '.' . $fileInfo['extension'];

        return $this->response->download($filePath, $downloadName);
    }

    public function export()
    {
        $pembayarans = $this->pembayaranModel->getPembayaranWithUser();
        
        $filename = 'laporan_pembayaran_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'ID Pembayaran',
            'Nama User',
            'Email',
            'Role',
            'Metode Pembayaran',
            'Jumlah',
            'Status',
            'Tanggal Bayar',
            'Tanggal Verifikasi',
            'Keterangan'
        ]);
        
        // CSV Data
        foreach ($pembayarans as $pembayaran) {
            fputcsv($output, [
                $pembayaran['id_pembayaran'],
                $pembayaran['nama_lengkap'],
                $pembayaran['email'],
                ucfirst($pembayaran['role']),
                $pembayaran['metode'],
                'Rp ' . number_format($pembayaran['jumlah'], 0, ',', '.'),
                ucfirst($pembayaran['status']),
                date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])),
                $pembayaran['verified_at'] ? date('d/m/Y H:i', strtotime($pembayaran['verified_at'])) : '-',
                $pembayaran['keterangan'] ?? '-'
            ]);
        }
        
        fclose($output);
    }

    public function statistik()
    {
        // Revenue per bulan (12 bulan terakhir)
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $revenue = $this->pembayaranModel
                           ->selectSum('jumlah')
                           ->where('status', 'verified')
                           ->where('DATE_FORMAT(tanggal_bayar, "%Y-%m")', $month)
                           ->first()['jumlah'] ?? 0;
            
            $revenueData[] = [
                'month' => date('M Y', strtotime($month . '-01')),
                'revenue' => $revenue
            ];
        }

        // Pembayaran per metode
        $metodePembayaran = $this->pembayaranModel
                                ->select('metode, COUNT(*) as total, SUM(jumlah) as total_amount')
                                ->where('status', 'verified')
                                ->groupBy('metode')
                                ->findAll();

        // Status distribution
        $statusData = [
            'pending' => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
            'verified' => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
            'rejected' => $this->pembayaranModel->where('status', 'rejected')->countAllResults(),
        ];

        return $this->response->setJSON([
            'revenue_chart' => $revenueData,
            'metode_pembayaran' => $metodePembayaran,
            'status_distribution' => $statusData
        ]);
    }
}