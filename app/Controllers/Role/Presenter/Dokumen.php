<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Dokumen extends BaseController
{
    protected $dokumenModel;
    protected $eventModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $db;

    public function __construct()
    {
        $this->dokumenModel   = new DokumenModel();
        $this->eventModel     = new EventModel();
        $this->pembayaranModel= new PembayaranModel();
        $this->absensiModel   = new AbsensiModel();
        $this->db             = \Config\Database::connect();
    }

    /** Tab LOA */
    public function loa()
    {
        $userId = (int) session('id_user');

        try {
            $loaDocs       = $this->dokumenModel->listLoaByUserEvent($userId);
            $eligibleEvents= $this->getEligibleEventsForLOA($userId);
        } catch (\Throwable $e) {
            log_message('error', 'LOA page error: '.$e->getMessage());
            // fallback aman agar halaman tetap terbuka
            $loaDocs = [];
            $eligibleEvents = [];
        }

        return view('role/presenter/dokumen/index', [
            'activeTab'        => 'loa',
            'loa_documents'    => $loaDocs,
            'eligible_loa'     => $eligibleEvents,
            'certificates'     => [],          // supaya view aman
            'eligible_cert'    => []
        ]);
    }

    /** Download LOA */
    public function downloadLoa($fileName)
    {
        $userId = (int) session('id_user');

        // pastikan milik user dan tipe = loa
        $doc = $this->dokumenModel
            ->where('id_user', $userId)
            ->where('tipe', 'loa')
            ->groupStart()
                ->where('file_path', $fileName)
                ->orLike('file_path', $fileName, 'both')
            ->groupEnd()
            ->first();

        if (!$doc) {
            return redirect()->back()->with('error', 'LOA tidak ditemukan atau akses ditolak.');
        }

        // dukung dua pola penyimpanan path
        $try = [
            WRITEPATH . 'uploads/loa/' . $fileName,
            WRITEPATH . 'uploads/' . $doc['file_path'],
        ];
        foreach ($try as $path) {
            if (is_file($path)) {
                return $this->response->download($path, null);
            }
        }
        return redirect()->back()->with('error', 'File LOA tidak ditemukan di server.');
    }

    /** Tab Sertifikat */
    public function sertifikat()
    {
        $userId = (int) session('id_user');

        try {
            $certs         = $this->dokumenModel->listSertifikatByUserEvent($userId);
            $eligibleEvents= $this->getEligibleEventsForCertificate($userId);
        } catch (\Throwable $e) {
            log_message('error', 'Certificate page error: '.$e->getMessage());
            $certs = [];
            $eligibleEvents = [];
        }

        return view('role/presenter/dokumen/index', [
            'activeTab'        => 'sertifikat',
            'certificates'     => $certs,
            'eligible_cert'    => $eligibleEvents,
            'loa_documents'    => [],
            'eligible_loa'     => []
        ]);
    }

    /** Download Sertifikat */
    public function downloadSertifikat($fileName)
    {
        $userId = (int) session('id_user');

        $doc = $this->dokumenModel
            ->where('id_user', $userId)
            ->where('tipe', 'sertifikat')
            ->groupStart()
                ->where('file_path', $fileName)
                ->orLike('file_path', $fileName, 'both')
            ->groupEnd()
            ->first();

        if (!$doc) {
            return redirect()->back()->with('error', 'Sertifikat tidak ditemukan atau akses ditolak.');
        }

        $try = [
            WRITEPATH . 'uploads/sertifikat/' . $fileName,
            WRITEPATH . 'uploads/' . $doc['file_path'],
        ];
        foreach ($try as $path) {
            if (is_file($path)) {
                return $this->response->download($path, null);
            }
        }
        return redirect()->back()->with('error', 'File sertifikat tidak ditemukan di server.');
    }

    /**
     * Event yang eligible untuk LOA:
     * - pembayaran verified
     * - abstrak status 'diacc'
     * (pakai subcount untuk cek apakah dokumen sudah ada)
     */
    private function getEligibleEventsForLOA(int $userId): array
    {
        return $this->db->query("
            SELECT 
                e.*,
                p.verified_at AS payment_verified_at,
                a.status      AS abstract_status,
                a.judul       AS abstract_title,
                (
                    SELECT COUNT(*) FROM dokumen d 
                    WHERE d.event_id = e.id AND d.id_user = p.id_user AND d.tipe = 'loa'
                ) AS loa_count
            FROM events e
            JOIN pembayaran p 
                ON p.event_id = e.id AND p.id_user = ?
            JOIN abstrak a
                ON a.event_id = e.id AND a.id_user = p.id_user
            WHERE p.status = 'verified'
              AND a.status = 'diacc'
            GROUP BY e.id, p.verified_at, a.status, a.judul
            ORDER BY e.event_date DESC
        ", [$userId])->getResultArray();
    }

    /**
     * Event yang eligible untuk Sertifikat:
     * - hadir (absensi = 'hadir')
     * - pembayaran verified
     */
    private function getEligibleEventsForCertificate(int $userId): array
    {
        return $this->db->query("
            SELECT 
                e.*,
                ab.waktu_scan AS attendance_time,
                p.verified_at AS payment_verified_at,
                (
                    SELECT COUNT(*) FROM dokumen d 
                    WHERE d.event_id = e.id AND d.id_user = ab.id_user AND d.tipe = 'sertifikat'
                ) AS certificate_count
            FROM events e
            JOIN absensi ab 
                ON ab.event_id = e.id AND ab.id_user = ?
            JOIN pembayaran p 
                ON p.event_id = e.id AND p.id_user = ab.id_user
            WHERE ab.status = 'hadir'
              AND p.status  = 'verified'
            GROUP BY e.id, ab.waktu_scan, p.verified_at
            ORDER BY e.event_date DESC
        ", [$userId])->getResultArray();
    }
}
