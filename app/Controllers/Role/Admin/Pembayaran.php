<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\UserModel;
use App\Models\VoucherModel;
use App\Models\EventRegistrationModel;

class Pembayaran extends BaseController
{
    protected PembayaranModel $pembayaranModel;
    protected UserModel $userModel;
    protected VoucherModel $voucherModel;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
        $this->userModel       = new UserModel();
        $this->voucherModel    = new VoucherModel();
    }

    public function index()
    {
        // daftar pembayaran + info user & event
        $pembayarans = $this->pembayaranModel->getPembayaranWithUser();

        // sisipkan info voucher (jika ada)
        foreach ($pembayarans as &$row) {
            if (!empty($row['id_voucher'])) {
                $row['voucher_info'] = $this->voucherModel->find($row['id_voucher']);
            } else {
                $row['voucher_info'] = null;
            }
        }
        unset($row);

        // statistik ringkas
        $data = [
            'pembayarans'          => $pembayarans,
            'total_pembayaran'     => $this->pembayaranModel->countAll(),
            'pembayaran_pending'   => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
            'pembayaran_verified'  => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
            'pembayaran_rejected'  => $this->pembayaranModel->where('status', 'rejected')->countAllResults(),
            'total_revenue'        => $this->pembayaranModel
                                            ->selectSum('jumlah')
                                            ->where('status', 'verified')
                                            ->first()['jumlah'] ?? 0,
        ];

        return view('role/admin/pembayaran/index', $data);
    }

    public function verifikasi(int $id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->find($id_pembayaran);
        if (!$pembayaran) {
            return redirect()->to('admin/pembayaran')->with('error', 'Pembayaran tidak ditemukan.');
        }

        $status     = (string) $this->request->getPost('status');
        $keterangan = (string) $this->request->getPost('keterangan');

        if (!in_array($status, ['verified', 'rejected'], true)) {
            return redirect()->back()->with('error', 'Status tidak valid.');
        }

        // update status pembayaran
        $ok = $this->pembayaranModel->update($id_pembayaran, [
            'status'      => $status,
            'verified_at' => ($status === 'verified') ? date('Y-m-d H:i:s') : null,
            'verified_by' => (int) session('id_user'),
            'keterangan'  => $keterangan,
        ]);

        if (!$ok) {
            return redirect()->back()->with('error', 'Gagal memperbarui status pembayaran.');
        }

        // jika diverifikasi: tandai registrasi event menjadi lunas
        if ($status === 'verified') {
            $regM = new EventRegistrationModel();
            if (method_exists($regM, 'findUserReg')) {
                $reg = $regM->findUserReg((int) $pembayaran['event_id'], (int) $pembayaran['id_user']);
                if ($reg) {
                    $regM->markPaid((int) $reg['id']);
                }
            }

            // kurangi kuota voucher bila ada
            if (!empty($pembayaran['id_voucher'])) {
                $voucher = $this->voucherModel->find($pembayaran['id_voucher']);
                if ($voucher && (int) $voucher['kuota'] > 0) {
                    $sisa = (int) $voucher['kuota'] - 1;
                    $this->voucherModel->update($pembayaran['id_voucher'], ['kuota' => $sisa]);
                    if ($sisa <= 0) {
                        $this->voucherModel->update($pembayaran['id_voucher'], ['status' => 'habis']);
                    }
                }
            }
        }

        $msg = ($status === 'verified') ? 'Pembayaran berhasil diverifikasi!' : 'Pembayaran berhasil ditolak!';
        return redirect()->to('admin/pembayaran')->with('success', $msg);
    }

    public function detail(int $id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel
            ->select('pembayaran.*, users.nama_lengkap, users.email, users.role')
            ->join('users', 'users.id_user = pembayaran.id_user')
            ->where('pembayaran.id_pembayaran', $id_pembayaran)
            ->first();

        if (!$pembayaran) {
            return redirect()->to('admin/pembayaran')->with('error', 'Pembayaran tidak ditemukan.');
        }

        $voucher    = !empty($pembayaran['id_voucher']) ? $this->voucherModel->find($pembayaran['id_voucher']) : null;
        $verifiedBy = !empty($pembayaran['verified_by']) ? $this->userModel->find($pembayaran['verified_by']) : null;

        return view('role/admin/pembayaran/detail', [
            'pembayaran'  => $pembayaran,
            'voucher'     => $voucher,
            'verified_by' => $verifiedBy,
        ]);
    }

    public function downloadBukti(int $id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->find($id_pembayaran);
        if (!$pembayaran || empty($pembayaran['bukti_bayar'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Bukti pembayaran tidak tersedia.');
        }
        $pathA = WRITEPATH . 'uploads/bukti/' . $pembayaran['bukti_bayar'];
        $pathB = WRITEPATH . 'uploads/pembayaran/' . $pembayaran['bukti_bayar'];
        $file  = is_file($pathA) ? $pathA : $pathB;

        if (!is_file($file)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File bukti pembayaran tidak ditemukan.');
        }

        return $this->response->download($file, null)->setFileName($pembayaran['bukti_bayar']);
    }

    // FIXED: Method viewBukti yang diperbaiki
    public function viewBukti(int $id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->find($id_pembayaran);
        if (!$pembayaran || empty($pembayaran['bukti_bayar'])) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Bukti pembayaran tidak tersedia']);
        }

        // Coba beberapa lokasi file yang mungkin
        $possiblePaths = [
            WRITEPATH . 'uploads/bukti/' . $pembayaran['bukti_bayar'],
            WRITEPATH . 'uploads/pembayaran/' . $pembayaran['bukti_bayar'],
            FCPATH . 'uploads/bukti/' . $pembayaran['bukti_bayar'],
            FCPATH . 'uploads/pembayaran/' . $pembayaran['bukti_bayar'],
        ];

        $file = null;
        foreach ($possiblePaths as $path) {
            if (is_file($path)) {
                $file = $path;
                break;
            }
        }

        if (!$file) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'File bukti pembayaran tidak ditemukan']);
        }

        // Tentukan MIME type berdasarkan ekstensi
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeTypes = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'bmp'  => 'image/bmp'
        ];
        $mimeType = $mimeTypes[$extension] ?? 'image/png';
        
        // Set headers yang diperlukan
        $this->response->setHeader('Content-Type', $mimeType);
        $this->response->setHeader('Content-Length', (string)filesize($file));
        $this->response->setHeader('Content-Disposition', 'inline; filename="' . basename($file) . '"');
        $this->response->setHeader('Cache-Control', 'public, max-age=3600');
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        // Baca dan kirim file
        return $this->response->setBody(file_get_contents($file));
    }

    // TAMBAHAN: Method untuk debug lokasi file
    public function debugBukti(int $id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->find($id_pembayaran);
        if (!$pembayaran) {
            return $this->response->setJSON(['error' => 'Pembayaran tidak ditemukan']);
        }

        $possiblePaths = [
            'bukti_path_1' => WRITEPATH . 'uploads/bukti/' . ($pembayaran['bukti_bayar'] ?? 'no_file'),
            'bukti_path_2' => WRITEPATH . 'uploads/pembayaran/' . ($pembayaran['bukti_bayar'] ?? 'no_file'),
            'bukti_path_3' => FCPATH . 'uploads/bukti/' . ($pembayaran['bukti_bayar'] ?? 'no_file'),
            'bukti_path_4' => FCPATH . 'uploads/pembayaran/' . ($pembayaran['bukti_bayar'] ?? 'no_file'),
        ];

        $debugInfo = [
            'pembayaran_id' => $id_pembayaran,
            'bukti_bayar_name' => $pembayaran['bukti_bayar'] ?? 'NULL',
            'WRITEPATH' => WRITEPATH,
            'FCPATH' => FCPATH,
            'paths_check' => []
        ];

        foreach ($possiblePaths as $key => $path) {
            $debugInfo['paths_check'][$key] = [
                'path' => $path,
                'exists' => is_file($path),
                'readable' => is_file($path) ? is_readable($path) : false,
                'size' => is_file($path) ? filesize($path) : 0
            ];
        }

        return $this->response->setJSON($debugInfo);
    }

    // TAMBAHAN: Method untuk mengecek ketersediaan bukti
    public function checkBukti(int $id_pembayaran)
    {
        $pembayaran = $this->pembayaranModel->find($id_pembayaran);
        if (!$pembayaran || empty($pembayaran['bukti_bayar'])) {
            return $this->response->setJSON(['exists' => false, 'message' => 'No bukti_bayar data']);
        }

        $possiblePaths = [
            WRITEPATH . 'uploads/bukti/' . $pembayaran['bukti_bayar'],
            WRITEPATH . 'uploads/pembayaran/' . $pembayaran['bukti_bayar'],
            FCPATH . 'uploads/bukti/' . $pembayaran['bukti_bayar'],
            FCPATH . 'uploads/pembayaran/' . $pembayaran['bukti_bayar'],
        ];

        foreach ($possiblePaths as $path) {
            if (is_file($path)) {
                return $this->response->setJSON(['exists' => true, 'path_found' => true]);
            }
        }

        return $this->response->setJSON(['exists' => false, 'message' => 'File not found in any expected location']);
    }

    public function export()
    {
        $rows = $this->pembayaranModel->getPembayaranWithUser();
        $filename = 'laporan_pembayaran_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID Pembayaran','Nama User','Email','Role','Metode',
            'Jumlah','Status','Tanggal Bayar','Tanggal Verifikasi','Keterangan'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id_pembayaran'],
                $r['nama_lengkap'],
                $r['email'],
                ucfirst((string) $r['role']),
                $r['metode'],
                $r['jumlah'],
                ucfirst((string) $r['status']),
                $r['tanggal_bayar'],
                $r['verified_at'] ?: '-',
                $r['keterangan']  ?: '-',
            ]);
        }
        fclose($out);
        exit;
    }

    public function statistik()
    {
        $db       = \Config\Database::connect();
        $driver   = strtolower($db->DBDriver);

        // Revenue 12 bulan terakhir (Postgres/MySQL compatible)
        if ($driver === 'postgre') {
            $rev = $db->query("
                SELECT to_char(date_trunc('month', tanggal_bayar), 'Mon YYYY') AS label,
                       date_trunc('month', tanggal_bayar) AS bulan,
                       SUM(jumlah) AS revenue
                FROM pembayaran
                WHERE status = 'verified'
                  AND tanggal_bayar >= (current_date - INTERVAL '11 month')
                GROUP BY 1,2
                ORDER BY bulan
            ")->getResultArray();
        } else {
            // MySQL fallback
            $rev = $db->query("
                SELECT DATE_FORMAT(tanggal_bayar, '%b %Y') AS label,
                       DATE_FORMAT(tanggal_bayar, '%Y-%m-01') AS bulan,
                       SUM(jumlah) AS revenue
                FROM pembayaran
                WHERE status = 'verified'
                  AND tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
                GROUP BY 1,2
                ORDER BY bulan
            ")->getResultArray();
        }

        $metode = $this->pembayaranModel
            ->select('metode, COUNT(*) AS total, SUM(jumlah) AS total_amount')
            ->where('status', 'verified')
            ->groupBy('metode')
            ->findAll();

        $statusData = [
            'pending'  => $this->pembayaranModel->where('status','pending')->countAllResults(),
            'verified' => $this->pembayaranModel->where('status','verified')->countAllResults(),
            'rejected' => $this->pembayaranModel->where('status','rejected')->countAllResults(),
        ];

        return $this->response->setJSON([
            'revenue_chart'       => $rev,
            'metode_pembayaran'   => $metode,
            'status_distribution' => $statusData,
        ]);
    }
}