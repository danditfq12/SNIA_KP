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
        $this->voucherModel    = new VoucherModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel      = new EventModel();
        $this->db              = \Config\Database::connect();
    }

    public function index()
    {
        try {
            $vouchers = $this->getVouchersWithStats();
            $stats    = $this->calculateVoucherStatistics($vouchers);

            return view('role/admin/voucher/index', [
                'vouchers'         => $vouchers,
                'total_vouchers'   => $stats['total_vouchers'],
                'active_vouchers'  => $stats['active_vouchers'],
                'expired_vouchers' => $stats['expired_vouchers'],
                'used_vouchers'    => $stats['used_vouchers'],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Voucher index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data voucher.');
        }
    }

    private function getVouchersWithStats()
    {
        $vouchers = $this->voucherModel->orderBy('masa_berlaku', 'DESC')->findAll();

        foreach ($vouchers as &$v) {
            $usedCount = $this->pembayaranModel
                ->where('id_voucher', $v['id_voucher'])
                ->where('status', 'verified')
                ->countAllResults();

            $v['used_count'] = (int)$usedCount;
            $v['remaining']  = max(0, (int)$v['kuota'] - (int)$usedCount);
            $v['is_expired'] = strtotime($v['masa_berlaku']) < time();

            $this->updateVoucherStatus($v);
        }
        return $vouchers;
    }

    private function updateVoucherStatus($v)
    {
        $current = $v['status'];
        $next    = $current;

        if (strtotime($v['masa_berlaku']) < time() && $current !== 'expired') {
            $next = 'expired';
        } elseif ($v['remaining'] <= 0 && $current !== 'habis') {
            $next = 'habis';
        }

        if ($next !== $current) {
            $this->voucherModel->update($v['id_voucher'], ['status' => $next]);
        }
    }

    private function calculateVoucherStatistics($vouchers)
    {
        $stats = ['total_vouchers'=>count($vouchers),'active_vouchers'=>0,'expired_vouchers'=>0,'used_vouchers'=>0];
        foreach ($vouchers as $v) {
            if     ($v['status'] === 'aktif')   $stats['active_vouchers']++;
            elseif ($v['status'] === 'expired') $stats['expired_vouchers']++;
            elseif ($v['status'] === 'habis')   $stats['used_vouchers']++;
        }
        return $stats;
    }

    public function store()
    {
        $validation = \Config\Services::validation();
        $rules = [
            'kode_voucher' => 'required|min_length[3]|max_length[50]|is_unique[voucher.kode_voucher]',
            'tipe'         => 'required|in_list[percentage,fixed]',
            'nilai'        => 'required|numeric|greater_than[0]',
            'kuota'        => 'required|integer|greater_than[0]',
            'masa_berlaku' => 'required|valid_date',
        ];
        if ($this->request->getPost('tipe') === 'percentage') {
            $rules['nilai'] .= '|less_than_equal_to[100]';
        }
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $masaBerlaku = $this->request->getPost('masa_berlaku');
        if (strtotime($masaBerlaku) <= time()) {
            return redirect()->back()->withInput()->with('error', 'Masa berlaku harus di masa depan.');
        }

        try {
            $data = [
                'kode_voucher' => strtoupper(trim($this->request->getPost('kode_voucher'))),
                'tipe'         => $this->request->getPost('tipe'),
                'nilai'        => $this->request->getPost('nilai'),
                'kuota'        => $this->request->getPost('kuota'),
                'masa_berlaku' => $masaBerlaku,
                'status'       => 'aktif',
            ];
            if (!$this->voucherModel->save($data)) {
                throw new \Exception('Gagal menyimpan voucher: ' . implode(', ', $this->voucherModel->errors()));
            }
            $idBaru = $this->voucherModel->getInsertID();
            $this->logActivity(session('id_user'), "Created voucher {$data['kode_voucher']} (ID: {$idBaru})");
            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil ditambahkan!');
        } catch (\Exception $e) {
            log_message('error', 'Voucher creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $voucher = $this->voucherModel->find($id);
        if (!$voucher) return $this->response->setJSON(['error' => 'Voucher tidak ditemukan.']);
        return $this->response->setJSON($voucher);
    }

    public function update($id)
    {
        $voucher = $this->voucherModel->find($id);
        if (!$voucher) return redirect()->back()->with('error', 'Voucher tidak ditemukan.');

        $validation = \Config\Services::validation();
        $rules = [
            'kode_voucher' => "required|min_length[3]|max_length[50]|is_unique[voucher.kode_voucher,id_voucher,{$id}]",
            'tipe'         => 'required|in_list[percentage,fixed]',
            'nilai'        => 'required|numeric|greater_than[0]',
            'kuota'        => 'required|integer|greater_than[0]',
            'masa_berlaku' => 'required|valid_date',
            'status'       => 'required|in_list[aktif,nonaktif,expired,habis]',
        ];
        if ($this->request->getPost('tipe') === 'percentage') {
            $rules['nilai'] .= '|less_than_equal_to[100]';
        }
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $usageCount = $this->pembayaranModel->where('id_voucher', $id)->countAllResults();
        if ($usageCount > 0 && ((int)$this->request->getPost('kuota')) < $usageCount) {
            return redirect()->back()->withInput()->with('error', "Kuota tidak boleh kurang dari jumlah penggunaan ({$usageCount}).");
        }

        try {
            $data = [
                'kode_voucher' => strtoupper(trim($this->request->getPost('kode_voucher'))),
                'tipe'         => $this->request->getPost('tipe'),
                'nilai'        => $this->request->getPost('nilai'),
                'kuota'        => $this->request->getPost('kuota'),
                'masa_berlaku' => $this->request->getPost('masa_berlaku'),
                'status'       => $this->request->getPost('status'),
            ];
            if (!$this->voucherModel->update($id, $data)) {
                throw new \Exception('Gagal memperbarui voucher: ' . implode(', ', $this->voucherModel->errors()));
            }
            $this->logActivity(session('id_user'), "Updated voucher {$data['kode_voucher']} (ID: {$id})");
            return redirect()->to('admin/voucher')->with('success', 'Voucher berhasil diperbarui!');
        } catch (\Exception $e) {
            log_message('error', 'Voucher update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $v = $this->voucherModel->find($id);
        if (!$v) return redirect()->back()->with('error', 'Voucher tidak ditemukan.');
        if (in_array($v['status'], ['expired','habis'])) {
            return redirect()->back()->with('error', 'Tidak dapat mengubah status voucher yang expired atau habis kuota.');
        }
        $new = $v['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        try {
            $this->voucherModel->update($id, ['status'=>$new]);
            $this->logActivity(session('id_user'), "Changed voucher '{$v['kode_voucher']}' status to {$new}");
            return redirect()->back()->with('success', $new==='aktif'?'Voucher berhasil diaktifkan!':'Voucher berhasil dinonaktifkan!');
        } catch (\Exception $e) {
            log_message('error', 'Toggle voucher status error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $v = $this->voucherModel->find($id);
        if (!$v) return redirect()->back()->with('error', 'Voucher tidak ditemukan.');

        $usageCount = $this->pembayaranModel->where('id_voucher',$id)->countAllResults();
        if ($usageCount > 0) {
            return redirect()->back()->with('error', "Tidak dapat menghapus voucher yang sudah digunakan ({$usageCount} kali).");
        }

        try {
            if ($this->voucherModel->delete($id)) {
                $this->logActivity(session('id_user'), "Deleted voucher: {$v['kode_voucher']} (ID: {$id})");
                return redirect()->to('admin/voucher')->with('success','Voucher berhasil dihapus!');
            }
            return redirect()->back()->with('error','Gagal menghapus voucher.');
        } catch (\Exception $e) {
            log_message('error','Delete voucher error: '.$e->getMessage());
            return redirect()->back()->with('error','Error menghapus voucher: '.$e->getMessage());
        }
    }

    public function forceDelete($id)
    {
        $v = $this->voucherModel->find($id);
        if (!$v) return redirect()->back()->with('error', 'Voucher tidak ditemukan.');

        $usageCount = $this->pembayaranModel->where('id_voucher',$id)->countAllResults();
        if ($usageCount > 0) {
            return redirect()->back()->with('error','Tidak dapat menghapus permanen voucher yang memiliki riwayat penggunaan.');
        }

        $this->db->transStart();
        try {
            if (!$this->voucherModel->delete($id, true)) {
                throw new \Exception('Failed to permanently delete voucher');
            }
            $this->logActivity(session('id_user'), "Force deleted voucher: {$v['kode_voucher']} (ID: {$id})");
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');
            return redirect()->to('admin/voucher')->with('success','Voucher berhasil dihapus permanen!');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error','Force delete voucher error: '.$e->getMessage());
            return redirect()->back()->with('error','Error: '.$e->getMessage());
        }
    }

    public function detail($id)
    {
        $id = (int)$id;
        $voucher = $this->voucherModel->find($id);
        if (!$voucher) return redirect()->to('admin/voucher')->with('error','Voucher tidak ditemukan.');

        try {
            $usageHistory = $this->getVoucherUsageHistory($id);

            $totalUsed = (int)$this->pembayaranModel
                ->where('id_voucher', $id)
                ->where('status', 'verified')
                ->countAllResults();

            $remaining = max(0, ((int)$voucher['kuota']) - $totalUsed);

            // total diskon hanya transaksi verified; kompatibel utk skema kolom berbeda
            $row = $this->db->table('pembayaran p')
                ->select("
                    COALESCE(SUM(
                        CASE WHEN p.original_amount IS NOT NULL 
                             THEN (p.original_amount - p.jumlah)
                             ELSE COALESCE(p.discount_amount, 0)
                        END
                    ),0) AS total_disc
                ", false)
                ->where('p.id_voucher', $id)
                ->where('p.status', 'verified')
                ->get()->getRowArray();

            $totalDiscount = (int)($row['total_disc'] ?? 0);

            return view('role/admin/voucher/detail', [
                'title'          => 'Detail Voucher',
                'voucher'        => $voucher,
                'usage_history'  => $usageHistory,
                'total_used'     => $totalUsed,
                'remaining'      => $remaining,
                'total_discount' => $totalDiscount,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Voucher detail error: ' . $e->getMessage());
            return redirect()->to('admin/voucher')->with('error', 'Terjadi kesalahan saat memuat detail voucher.');
        }
    }

    private function getVoucherUsageHistory($voucherId)
    {
        return $this->db->table('pembayaran p')
            ->select("
                p.id_pembayaran, p.tanggal_bayar, p.jumlah,
                CASE WHEN p.original_amount IS NOT NULL 
                     THEN (p.original_amount - p.jumlah)
                     ELSE COALESCE(p.discount_amount,0)
                END AS discount_amount,
                p.status,
                u.nama_lengkap, u.email,
                e.title AS event_title
            ")
            ->join('users u',  'u.id_user = p.id_user', 'left')
            ->join('events e', 'e.id = p.event_id',     'left') // <– sesuaikan skema aslimu
            ->where('p.id_voucher', $voucherId)
            ->orderBy('p.tanggal_bayar','DESC')
            ->get()->getResultArray();
    }

    public function validateVoucher()
    {
        $kodeVoucher = $this->request->getPost('kode_voucher');
        $userId  = $this->request->getPost('user_id');
        $eventId = $this->request->getPost('event_id');

        if (!$kodeVoucher) {
            return $this->response->setJSON(['valid'=>false,'message'=>'Kode voucher tidak boleh kosong.']);
        }

        $voucher = $this->voucherModel->where('kode_voucher', strtoupper($kodeVoucher))->first();
        if (!$voucher) {
            return $this->response->setJSON(['valid'=>false,'message'=>'Kode voucher tidak ditemukan.']);
        }
        if ($voucher['status'] !== 'aktif') {
            return $this->response->setJSON(['valid'=>false,'message'=>'Voucher tidak aktif.']);
        }
        if (strtotime($voucher['masa_berlaku']) < time()) {
            return $this->response->setJSON(['valid'=>false,'message'=>'Voucher sudah expired.']);
        }

        $usedCount = $this->pembayaranModel
            ->where('id_voucher', $voucher['id_voucher'])
            ->where('status', 'verified')
            ->countAllResults();
        if ($usedCount >= $voucher['kuota']) {
            return $this->response->setJSON(['valid'=>false,'message'=>'Kuota voucher sudah habis.']);
        }

        if ($userId && $eventId) {
            $userUsage = $this->pembayaranModel
                ->where('id_voucher', $voucher['id_voucher'])
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->first();
            if ($userUsage) {
                return $this->response->setJSON(['valid'=>false,'message'=>'Anda sudah menggunakan voucher ini untuk event ini.']);
            }
        }

        return $this->response->setJSON([
            'valid'   => true,
            'voucher' => $voucher,
            'message' => 'Voucher valid dan dapat digunakan.'
        ]);
    }

    public function export()
    {
        try {
            $vouchers = $this->getVouchersWithStats();
            $filename = 'vouchers_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            $out = fopen('php://output','w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['ID','Kode Voucher','Tipe','Nilai','Kuota','Digunakan','Sisa','Masa Berlaku','Status','Expired']);
            foreach ($vouchers as $v) {
                fputcsv($out, [
                    $v['id_voucher'],
                    $v['kode_voucher'],
                    ucfirst($v['tipe']),
                    $v['tipe']==='percentage' ? $v['nilai'].'%' : 'Rp '.number_format($v['nilai'],0,',','.'),
                    $v['kuota'],
                    $v['used_count'],
                    $v['remaining'],
                    date('d/m/Y', strtotime($v['masa_berlaku'])),
                    ucfirst($v['status']),
                    $v['is_expired'] ? 'Ya' : 'Tidak',
                ]);
            }
            fclose($out);
            $this->logActivity(session('id_user'), "Exported vouchers data (".count($vouchers)." records)");
            exit;
        } catch (\Exception $e) {
            log_message('error', 'Export vouchers error: '.$e->getMessage());
            return redirect()->back()->with('error','Gagal export data voucher.');
        }
    }

    private function logActivity($userId, $activity)
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user'   => $userId,
                'aktivitas' => $activity,
                'waktu'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error','Failed to log activity: '.$e->getMessage());
        }
    }
}