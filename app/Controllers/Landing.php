<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\VoucherModel;
use App\Models\EventPosterModel;
use App\Models\EventSpeakerModel;
use App\Models\EventSponsorModel;

class Landing extends BaseController
{
    protected $eventModel;
    protected $voucherModel;
    protected $posterModel;
    protected $speakerModel;
    protected $sponsorModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel   = new EventModel();
        $this->voucherModel = new VoucherModel();
        $this->posterModel  = new EventPosterModel();
        $this->speakerModel = new EventSpeakerModel();
        $this->sponsorModel = new EventSponsorModel();
        $this->db           = \Config\Database::connect();
    }

    public function index()
    {
        try {
            $today = date('Y-m-d');

            // 1. PRIORITAS: hanya event yang ditandai sebagai landing
            $activeEvent = $this->eventModel
                ->where('is_active', true)
                ->where('is_landing', true)
                ->first();

            /**
             * ✅ PERUBAHAN PENTING:
             * DULU: kalau $activeEvent kosong → fallback ke event terdekat
             * SEKARANG: kalau tidak ada event is_landing, biarkan NULL
             * => hasilnya: landing bisa benar-benar "tanpa event"
             */
            if (!$activeEvent) {
                $activeEvent = null;
            }

            $activeVouchers = [];
            $posterPath     = null;
            $speakers       = [];
            $sponsors       = [];

            if ($activeEvent) {
                // ================= VOUCHERS ======================
                $activeVouchers = $this->voucherModel
                    ->where('status', 'aktif')
                    ->where('masa_berlaku >=', $today)
                    ->where('kuota >', 0)
                    ->orderBy('masa_berlaku', 'ASC')
                    ->findAll();

                // Hitung sisa kuota
                foreach ($activeVouchers as &$voucher) {
                    $used = $this->db->table('pembayaran')
                        ->where('id_voucher', $voucher['id_voucher'])
                        ->where('status', 'verified')
                        ->countAllResults();

                    $voucher['used_count'] = $used;
                    $voucher['remaining']  = max(0, $voucher['kuota'] - $used);
                }
                unset($voucher);

                // ================= FORMAT TANGGAL =================
                $activeEvent['event_date_formatted'] = $this->formatDateIndo($activeEvent['event_date'] ?? null);

                // ================= EARLY BIRD =====================
                $waves = [];

                if (!empty($activeEvent['registration_waves'])) {
                    $waves = is_string($activeEvent['registration_waves'])
                        ? json_decode($activeEvent['registration_waves'], true)
                        : $activeEvent['registration_waves'];
                }

                $activeEvent['early_bird_presenter_fee']      = 0;
                $activeEvent['early_bird_audience_fee']       = 0;
                $activeEvent['early_bird_deadline']           = null;
                $activeEvent['early_bird_deadline_formatted'] = null;

                if (!empty($waves) && isset($waves[0])) {
                    $wave1 = $waves[0];

                    $activeEvent['early_bird_presenter_fee'] = $wave1['presenter_fee_offline'] ?? 0;
                    $activeEvent['early_bird_audience_fee']  = $wave1['audience_fee_offline'] ?? 0;

                    if (!empty($wave1['registration_deadline'])) {
                        $activeEvent['early_bird_deadline'] = $wave1['registration_deadline'];
                        $activeEvent['early_bird_deadline_formatted'] =
                            $this->formatDateIndo($wave1['registration_deadline']);
                    }
                }

                // ================= POSTER =========================
                $poster = $this->posterModel
                    ->where('event_id', $activeEvent['id'])
                    ->where('is_active', true)
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($poster) {
                    $posterPath = $poster['file_path']; // langsung dipakai di <img>
                }

                // fallback jika masih pakai kolom lama
                if (!$posterPath && !empty($activeEvent['poster'])) {
                    $posterPath = 'uploads/poster/' . $activeEvent['poster'];
                }

                // ================= SPEAKERS ========================
                $speakers = $this->speakerModel
                    ->where('event_id', $activeEvent['id'])
                    ->where('is_active', true)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();

                // ================= SPONSORS =======================
                $sponsors = $this->sponsorModel
                    ->where('event_id', $activeEvent['id'])
                    ->where('is_active', true)
                    ->orderBy('type', 'ASC')
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
            }

            return view('landing/index', [
                'activeEvent'    => $activeEvent,
                'activeVouchers' => $activeVouchers,
                'posterPath'     => $posterPath,
                'speakers'       => $speakers,
                'sponsors'       => $sponsors,
                'title'          => 'SNIA - Seminar Nasional Informatika',
            ]);

        } catch (\Throwable $e) {

            log_message('error', 'Landing page error: ' . $e->getMessage());

            return view('landing/index', [
                'activeEvent'    => null,
                'activeVouchers' => [],
                'posterPath'     => null,
                'speakers'       => [],
                'sponsors'       => [],
                'title'          => 'SNIA - Seminar Nasional Informatika',
            ]);
        }
    }

    private function formatDateIndo($date)
    {
        if (!$date) return '';

        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $ts = strtotime($date);
        return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
    }
}
