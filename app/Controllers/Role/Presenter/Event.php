<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Event extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected PembayaranModel $pembayaranModel;
    protected AbsensiModel $absensiModel;

    public function __construct()
    {
        $this->eventModel      = new EventModel();
        $this->regModel        = new EventRegistrationModel();
        $this->abstrakModel    = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
        helper(['date','text']);
    }

    /* ========================= INDEX ========================= */
    public function index()
    {
        $userId = (int) session()->get('id_user');
        $q      = trim((string) ($this->request->getGet('q') ?? ''));

        // Ambil semua event aktif
        $all = $this->eventModel
            ->where('is_active', true)
            ->orderBy('event_date','ASC')
            ->orderBy('event_time','ASC')
            ->findAll();

        // Filter pencarian sederhana (judul + deskripsi + lokasi)
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $all = array_values(array_filter($all, function($e) use ($needle){
                $hay = mb_strtolower(
                    ($e['title'] ?? '').' '.
                    ($e['description'] ?? '').' '.
                    ($e['location'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        $todo    = [];
        $history = [];
        $now     = time();

        foreach ($all as $ev) {
            // Terapkan aturan drop (deadline abstrak / full paper)
            $this->enforceDeadlinePolicies($ev, $userId);

            $id     = (int) $ev['id'];
            $isOpen = $this->eventModel->isRegistrationOpen($id);

            // Event sudah selesai?
            $eventEndDate = $ev['event_end_date'] ?? $ev['event_date'] ?? null;
            $eventEndTime = $ev['event_end_time'] ?? $ev['event_time'] ?? '23:59:59';
            $eventEndTs   = $eventEndDate ? strtotime($eventEndDate.' '.$eventEndTime) : null;
            $isOver       = $eventEndTs ? ($now > $eventEndTs) : false;

            // Status registrasi user
            $reg   = $this->regModel->findUserReg($id, $userId);
            $isReg = (bool) $reg;

            // Flow status (label, hint, state)
            $flow = $this->computeFlowStatus($id, $userId);

            // Pembayaran terakhir (untuk CTA)
            $pay = $this->getLatestPayment($id, $userId);
            $ui  = $this->buildIndexUi($id, $flow, $pay['id_pembayaran'] ?? null, $isOpen);

            // Tambah confirm khusus tombol "Daftar"
            if (stripos((string)($ui['cta']['label'] ?? ''), 'daftar') !== false) {
                $ui['cta']['confirm'] = 'Daftar ke event ini sekarang?';
            }

            // ================== INFORMASI GELOMBANG ==================
            $waves     = $this->eventModel->getWaves($id);          // semua wave dari registration_waves
            $currWave  = $this->eventModel->getCurrentWave($id);    // wave yang sedang aktif (kalau ada)
            $waveNum   = null;
            $waveActive= false;
            $regDL     = null;

            if ($currWave) {
                // Kalau ada gelombang aktif: pakai nomor & deadline wave aktif
                $waveNum    = $currWave['wave_number'] ?? ($currWave['wave'] ?? null);
                $waveActive = true;
                $regDL      = $currWave['registration_deadline'] ?? null;
            } elseif (!empty($waves) && is_array($waves)) {
                // Tidak ada wave aktif: ambil dari wave terakhir (untuk info kapan pendaftaran berakhir)
                $lastWave   = end($waves);
                $waveNum    = $lastWave['wave_number'] ?? ($lastWave['wave'] ?? null);
                $waveActive = false;
                $regDL      = $lastWave['registration_deadline'] ?? null;
            }

            // ================== CARD DATA UNTUK VIEW ==================
            $card = [
                'id'         => $id,
                'title'      => $ev['title'] ?? '-',
                'format'     => $ev['format'] ?? '',
                'event_date' => $ev['event_date'] ?? null,
                'event_time' => $ev['event_time'] ?? null,

                // dipakai chip "deadline pendaftaran"
                'registration_deadline' => $regDL,

                // dipakai chip "Gel. X"
                'wave_num'     => $waveNum,
                'wave_active'  => $waveActive,

                'is_registered' => $isReg,
                'is_closed'     => !$isOpen,   // untuk icon lock / lightning
                'is_over'       => $isOver,    // untuk tab Riwayat

                'status_label' => $flow['label'] ?? '',
                'status_badge' => $this->stateToBadge($flow['state'] ?? 'secondary'),
                'hint'         => $flow['hint'] ?? '',

                'primary_url'      => $ui['cta']['url']   ?? site_url('presenter/events/detail/'.$id),
                'primary_text'     => $ui['cta']['label'] ?? 'Detail Event',
                'primary_class'    => $ui['cta']['class'] ?? 'btn-outline-primary',
                'primary_confirm'  => $ui['cta']['confirm'] ?? null,
                'primary_disabled' => !empty($ui['cta']['disabled']),

                'detail_url'       => site_url('presenter/events/detail/'.$id),
            ];

            // ================== PISAH TODO vs RIWAYAT ==================
            if (($flow['state'] ?? '') === 'sudah_absen') {
                // Sudah selesai + sudah absen -> Riwayat
                $history[] = $card;
                continue;
            }

            if ($isOver) {
                // Event sudah lewat -> Riwayat
                $history[] = $card;
                continue;
            }

            if ($isReg) {
                // Masih berjalan & user sudah terdaftar -> To-do
                $todo[] = $card;
                continue;
            }

            if (!$isOpen) {
                // Pendaftaran tutup & user tidak terdaftar -> Riwayat
                $history[] = $card;
            } else {
                // Pendaftaran buka & user belum daftar -> To-do
                $todo[] = $card;
            }
        }

        return view('role/presenter/events/index', [
            'title'         => 'Event',
            'todoEvents'    => $todo,
            'historyEvents' => $history,
            'q'             => $q,
        ]);
    }

    /* ========================= DETAIL ========================= */
    public function detail($id)
    {
        $userId = (int) session()->get('id_user');
        $event  = $this->eventModel->find((int)$id);
        if (!$event || !($event['is_active'] ?? false)) {
            return redirect()->to('/presenter/events')
                ->with('error', 'Event tidak ditemukan atau tidak aktif.');
        }

        $this->enforceDeadlinePolicies($event, $userId);

        $reg = $this->regModel->findUserReg((int)$event['id'], $userId);

        $abstrak = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$event['id'])
            ->orderBy('id_abstrak','DESC')
            ->first();

        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$event['id'])
            ->orderBy('id_pembayaran','DESC')
            ->first();

        $flow = $this->computeFlowStatus((int)$event['id'], $userId);

        // --- Kontributor (pastikan presenter utama muncul) ---
        $contributors = [];
        if ($reg) {
            if (method_exists($this->regModel, 'getContributors')) {
                $contributors = $this->regModel->getContributors((int)$event['id'], $userId) ?? [];
            } else {
                $raw = $reg['contributors'] ?? $reg['kontributor'] ?? $reg['coauthors_json'] ?? null;
                if ($raw) {
                    $tmp = is_array($raw) ? $raw : json_decode((string)$raw, true);
                    if (is_array($tmp)) {
                        $contributors = $tmp;
                    }
                }
            }
            $main = [
                'role'     => 'Presenter Utama',
                'nama'     => $reg['nama']      ?? ($reg['full_name'] ?? (session()->get('nama') ?? '')),
                'email'    => $reg['email']     ?? (session()->get('email') ?? ''),
                'afiliasi' => $reg['afiliasi']  ?? ($reg['institution'] ?? ''),
                'negara'   => $reg['negara']    ?? ($reg['country'] ?? ''),
            ];
            array_unshift($contributors, $main);
        }

        // --- Info gelombang (selaras dengan Audience) ---
        $currentWave = $this->eventModel->getCurrentWave((int)$event['id']);
        $waveInfo = null;
        if ($currentWave) {
            $waveInfo = [
                'wave_number' => $currentWave['wave_number'] ?? ($currentWave['wave'] ?? null),
                'start'       => $currentWave['registration_start'] ?? null,
                'deadline'    => $currentWave['registration_deadline'] ?? null,
            ];
        }

        $allWaves = $this->eventModel->getWaves((int)$event['id']);

        // Ambil "batas akhir registrasi" dari gelombang terakhir (untuk tampilan)
        $lastRegDeadline = null;
        if (!empty($allWaves) && is_array($allWaves)) {
            $last = end($allWaves);
            $lastRegDeadline = $last['registration_deadline'] ?? null;
        }

        // --- Actions (dipakai view) ---
        $actions = $this->buildDetailActions((int)$event['id'], $userId, $reg, $abstrak, $payment);

        // Harga default (misal offline) — masih menggunakan getEventPrice (sudah pakai wave aktif)
        $defaultPrice = $this->eventModel->getEventPrice((int)$event['id'], 'presenter', 'offline');

        return view('role/presenter/events/detail', [
            'title'                 => 'Detail Event',
            'event'                 => $event,
            'reg'                   => $reg,
            'abstrak'               => $abstrak,
            'payment'               => $payment,
            'flow'                  => $flow,
            'price'                 => $defaultPrice,
            'isOpen'                => $this->eventModel->isRegistrationOpen((int)$event['id']),
            'registration_deadline' => $lastRegDeadline,
            'abstract_deadline'     => $event['abstract_deadline'] ?? null,
            'full_paper_deadline'   => $event['full_paper_deadline'] ?? null,
            'contributors'          => $contributors,
            'actions'               => $actions,
            'eligible_to_pay'       => $this->isEligibleToPay((int)$event['id'], $userId),
            'waveInfo'              => $waveInfo,
            'allWaves'              => $allWaves,
        ]);
    }

    /* ========================= HALAMAN PILIH MODE ========================= */
    public function showRegistrationForm(int $id)
    {
        $userId = (int)(session()->get('id_user') ?? 0);
        if ($userId <= 0) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $event = $this->eventModel->find($id);
        if (!$event || !($event['is_active'] ?? false)) {
            return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan atau tidak aktif.');
        }

        if (!$this->eventModel->isRegistrationOpen($id)) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Pendaftaran event telah ditutup.');
        }

        // Cek apakah sudah terdaftar (dan belum drop)
        $reg = $this->regModel->findUserReg($id, $userId);
        if ($reg && empty($reg['is_dropped'])) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('warning', 'Kamu sudah terdaftar pada event ini.');
        }

        // Normalisasi format event (offline / online / both)
        $formatNorm = $this->normalizeFormat($event['format'] ?? 'offline');

        // ====== MODE KEHADIRAN SESUAI EVENT ======
        // 1) coba pakai helper EventModel
        $options = [];
        if (method_exists($this->eventModel, 'getParticipationOptions')) {
            $options = (array) $this->eventModel->getParticipationOptions($id, 'presenter');
        }

        // 2) bersihkan dan lower-case
        $options = array_values(array_unique(array_map('strtolower', $options)));

        // 3) sinkronkan dengan format event (override kalau perlu)
        if ($formatNorm === 'offline') {
            $options = ['offline'];
        } elseif ($formatNorm === 'online') {
            $options = ['online'];
        } else { // both / hybrid
            if (empty($options)) {
                $options = ['offline', 'online'];
            } else {
                if (!in_array('offline', $options, true)) $options[] = 'offline';
                if (!in_array('online',  $options, true)) $options[] = 'online';
            }
        }

        // Harga per mode (pakai helper getPrice)
        $modePricing = [];
        foreach ($options as $mode) {
            $modePricing[$mode] = $this->getPrice($id, $mode, 'presenter');
        }

        // Matrix harga semua role/mode (opsional, kalau dipakai di view)
        $pricing = $this->eventModel->getPricingMatrix($id);

        // Info gelombang aktif
        $currentWave = $this->eventModel->getCurrentWave($id);
        $waveInfo = null;
        if ($currentWave) {
            $waveInfo = [
                'wave_number' => $currentWave['wave_number'] ?? ($currentWave['wave'] ?? null),
                'start'       => $currentWave['registration_start'] ?? null,
                'deadline'    => $currentWave['registration_deadline'] ?? null,
            ];
        }

        return view('role/presenter/events/register', [
            'title'       => 'Daftar sebagai Presenter',
            'event'       => $event,
            'options'     => $options,       // contoh: ['online'] / ['offline'] / ['online','offline']
            'modePricing' => $modePricing,   // harga tiap mode
            'pricing'     => $pricing,
            'waveInfo'    => $waveInfo,
        ]);
    }

    /* ========================= REGISTER (PAKAI WAVE + MODE SESUAI EVENT) ========================= */
    public function register($id)
    {
        $id      = (int) $id;
        $userId  = (int)(session()->get('id_user') ?? 0);

        if ($userId <= 0) {
            return redirect()->to('/auth/login')->with('error','Silakan login terlebih dahulu.');
        }

        $event = $this->eventModel->find($id);
        if (!$event || !($event['is_active'] ?? false)) {
            return redirect()->to('/presenter/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }

        if (!$this->eventModel->isRegistrationOpen($id)) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error','Pendaftaran event telah ditutup.');
        }

        // Cek apakah sudah ada registrasi aktif
        $existingReg = $this->regModel->findUserReg($id, $userId);
        if ($existingReg && empty($existingReg['is_dropped'])) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('warning','Kamu sudah terdaftar pada event ini.');
        }

        // ====== MODE KEHADIRAN YANG DIPILIH USER ======
        $mode  = (string)$this->request->getPost('mode_kehadiran');

        // Normalisasi format event
        $formatNorm = $this->normalizeFormat($event['format'] ?? 'offline');

        // Ambil daftar mode yang valid (harus sama logikanya dengan showRegistrationForm)
        $validModes = [];
        if (method_exists($this->eventModel, 'getParticipationOptions')) {
            $validModes = (array) $this->eventModel->getParticipationOptions($id, 'presenter');
        }
        $validModes = array_values(array_unique(array_map('strtolower', $validModes)));

        if ($formatNorm === 'offline') {
            $validModes = ['offline'];
        } elseif ($formatNorm === 'online') {
            $validModes = ['online'];
        } else { // both / hybrid
            if (empty($validModes)) {
                $validModes = ['offline','online'];
            } else {
                if (!in_array('offline', $validModes, true)) $validModes[] = 'offline';
                if (!in_array('online',  $validModes, true)) $validModes[] = 'online';
            }
        }

        if (!in_array($mode, $validModes, true)) {
            return redirect()->back()->withInput()
                ->with('error','Mode kehadiran tidak valid untuk event ini.');
        }

        // CEK KUOTA (kalau EventModel mendukung)
        if (method_exists($this->eventModel, 'hasReachedMaxParticipants')
            && $this->eventModel->hasReachedMaxParticipants($id, $mode)) {

            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error','Kuota presenter untuk mode ini sudah penuh.');
        }

        // ====== HARGA SESUAI MODE & WAVE ======
        $price = $this->getPrice($id, $mode, 'presenter');
        if ($price <= 0) {
            return redirect()->back()->withInput()
                ->with('error','Harga untuk mode kehadiran ini belum diatur. Silakan hubungi admin.');
        }

        // Hapus pendaftaran "yatim" yang sudah didrop
        $this->cleanupOrphanReg($id, $userId);

        // Buat registrasi dasar
        $regId = $this->regModel->createPresenterRegistration($id, $userId);

        if (!$regId) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Gagal menyimpan pendaftaran presenter.');
        }

        // Simpan mode & jumlah_bayar
        $this->regModel->update($regId, [
            'mode_kehadiran' => $mode,
            'jumlah_bayar'   => $price,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/presenter/kontributor/start/'.$id)
            ->with('success', 'Berhasil terdaftar. Lengkapi data kontributor terlebih dahulu.');
    }

    /* ========================= CANCEL (HARD DROP USER) ========================= */
    public function cancel($id)
    {
        $userId = (int) session()->get('id_user');
        $id     = (int) $id;

        $reg = $this->regModel->findUserReg($id, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Pendaftaran tidak ditemukan.');
        }

        $abstract = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $id)
            ->orderBy('id_abstrak','DESC')
            ->first();

        $absStatus = strtolower($abstract['status'] ?? '');
        $fpStatus  = strtolower($abstract['full_paper_status'] ?? '');

        $isWaiting = function(?string $v): bool {
            if ($v === null || $v === '') return true; // baru upload / belum dinilai
            $v = strtolower($v);
            return in_array($v, ['menunggu','pending','sedang_direview','uploaded','revision','revisi'], true);
        };

        $hasAbs        = !empty($abstract);
        $absWaiting    = $hasAbs ? $isWaiting($absStatus) : false; // harus sudah upload abstrak
        $hasFP         = $this->hasFullPaper($abstract);
        $fpWaiting     = $hasFP ? $isWaiting($fpStatus) : true;    // belum upload FP = dianggap belum direview
        $canHardDrop   = ($hasAbs && $absWaiting && $fpWaiting);

        if ($canHardDrop) {
            // 1) Hapus pembayaran non-verified
            $pays = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $id)
                ->findAll();
            foreach ($pays as $p) {
                $st = strtolower($p['status'] ?? '');
                if ($st !== 'verified') {
                    $this->pembayaranModel->delete((int)$p['id_pembayaran']);
                }
            }

            // 2) Hapus absensi yang bukan 'hadir'
            $this->absensiModel
                ->where('id_user', $userId)
                ->where('event_id', $id)
                ->where('status !=', 'hadir')
                ->delete();

            // 3) Hapus semua abstrak (beserta metadata FP)
            $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $id)
                ->delete();

            // 4) Hapus pendaftaran
            $this->regModel->delete((int)$reg['id']);

            return redirect()->to('/presenter/events')
                ->with('success', 'Pendaftaran dibatalkan. Data abstrak/FP dan pembayaran (non-verified) dihapus karena belum direview.');
        }

        // Jika abstrak sudah dinilai (accepted/rejected), tidak boleh hard drop
        $hasAbstractReviewed = in_array($absStatus, ['diterima','accepted','acc','approved','ditolak','rejected'], true);
        if ($hasAbstractReviewed) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Tidak bisa membatalkan karena abstrak Anda sudah direview.');
        }

        // Fallback: hapus pendaftaran saja jika aman
        $this->regModel->delete((int)$reg['id']);
        return redirect()->to('/presenter/events')->with('success', 'Pendaftaran dibatalkan.');
    }

    /* ========================= HELPERS ========================= */

    /**
     * Normalisasi format event jadi: offline / online / both (hybrid)
     */
    private function normalizeFormat(?string $format): string
    {
        $f = strtolower(trim($format ?? ''));
        return match ($f) {
            'offline', 'onsite'                         => 'offline',
            'online', 'virtual'                         => 'online',
            'both', 'hybrid', 'mixed', 'mix',
            'offline-online', 'offline_online', 'all'   => 'both',
            default                                     => 'offline',
        };
    }

    /**
     * Ambil harga dengan fallback: wave → matrix → getEventPrice
     */
    private function getPrice(int $eventId, string $mode, string $userRole = 'presenter'): int
    {
        try {
            $mode = strtolower($mode);
            $userRole = strtolower($userRole);

            // 1. Cek wave aktif
            $wave = $this->eventModel->getCurrentWave($eventId);
            if ($wave && is_array($wave)) {
                $priceKey = $userRole . '_fee_' . $mode;
                $price = (int)($wave[$priceKey] ?? 0);
                if ($price > 0) {
                    return $price;
                }
            }

            // 2. Cek matrix harga
            $pricing = $this->eventModel->getPricingMatrix($eventId);
            if (!empty($pricing[$userRole][$mode])) {
                $price = (int)$pricing[$userRole][$mode];
                if ($price > 0) {
                    return $price;
                }
            }

            // 3. Fallback ke getEventPrice (misal pakai kolom lama di tabel events)
            $price = (int)$this->eventModel->getEventPrice($eventId, $userRole, $mode);
            if ($price > 0) {
                return $price;
            }

            return 0;
        } catch (\Exception $e) {
            log_message('error', 'Error getting price (Presenter Event): ' . $e->getMessage());
            return 0;
        }
    }

    private function getLatestAbstract(int $eventId, int $userId): ?array
    {
        return $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('id_abstrak','DESC')
            ->first();
    }

    private function getLatestPayment(int $eventId, int $userId): ?array
    {
        return $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('id_pembayaran','DESC')
            ->first();
    }

    /**
     * Cek apakah peserta sudah diizinkan membayar (LoA keluar).
     * Mengacu ke kolom eligible_to_pay di submissions / abstrak.
     */
    private function isEligibleToPay(int $eventId, int $userId): bool
    {
        $db = \Config\Database::connect();

        // Prioritas di submissions (flow baru full paper)
        if ($db->tableExists('submissions')) {
            $row = $db->table('submissions')
                ->select('eligible_to_pay')
                ->where('user_id', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id', 'DESC')
                ->get()->getRowArray();
            if ($row !== null && array_key_exists('eligible_to_pay', $row)) {
                $v = $row['eligible_to_pay'];
                return $v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true';
            }
        }

        // Fallback ke tabel abstrak kalau ada kolomnya
        if ($db->tableExists('abstrak')) {
            $row = $db->table('abstrak')
                ->select('eligible_to_pay')
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_abstrak', 'DESC')
                ->get()->getRowArray();
            if ($row !== null && array_key_exists('eligible_to_pay', $row)) {
                $v = $row['eligible_to_pay'];
                return $v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true';
            }
        }

        return false;
    }

    private function isContributorCompleted(?array $reg): bool
    {
        if (!$reg) return false;

        foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
            if (array_key_exists($f, $reg)) {
                $v = $reg[$f];
                if ($v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true') {
                    return true;
                }
            }
        }
        if (!empty($reg['afiliasi'])) return true;
        return false;
    }

    private function hasFullPaper(?array $abstract): bool
    {
        if (!$abstract) return false;
        if (!empty($abstract['full_paper_path']))   return true;
        if (!empty($abstract['full_paper_status'])) return true;
        return false;
    }

    private function cleanupOrphanReg(int $eventId, int $userId): void
    {
        $reg = $this->regModel->findUserReg($eventId, $userId);
        if ($reg && !empty($reg['is_dropped'])) {
            $this->regModel->delete((int)$reg['id']);
        }
    }

    private function enforceDeadlinePolicies(array $event, int $userId): void
    {
        $eventId = (int) $event['id'];
        $now     = time();

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) return;

        $abstract    = $this->getLatestAbstract($eventId, $userId);
        $hasAbstract = !empty($abstract);
        $hasFP       = $this->hasFullPaper($abstract);

        $absDeadline = !empty($event['abstract_deadline'])
            ? strtotime($event['abstract_deadline'])
            : null;
        $fpDeadline  = !empty($event['full_paper_deadline'])
            ? strtotime($event['full_paper_deadline'])
            : null;

        // Untuk "registration_deadline" sekarang kita ambil dari gelombang terakhir
        $regDL = null;
        $waves = $this->eventModel->getWaves($eventId);
        if (!empty($waves) && is_array($waves)) {
            $lastWave = end($waves);
            if (!empty($lastWave['registration_deadline'])) {
                $regDL = strtotime($lastWave['registration_deadline']);
            }
        }

        $payload = [];

        if ($absDeadline && $now > $absDeadline && !$hasAbstract) {
            $payload['is_dropped']  = 1;
            $payload['drop_reason'] = 'abstract_deadline_passed';
        } elseif ($fpDeadline && $now > $fpDeadline && $hasAbstract && !$hasFP) {
            $payload['is_dropped']  = 1;
            $payload['drop_reason'] = 'fullpaper_deadline_passed';
        } elseif ($regDL && $now > $regDL && $hasAbstract && !$hasFP) {
            $payload['is_dropped']  = 1;
            $payload['drop_reason'] = 'registration_closed_no_fullpaper';
        }

        if (!empty($payload)) {
            $this->regModel->update((int)$reg['id'], $payload);
        }
    }

    /**
     * Flow high-level:
     * - belum_daftar
     * - lengkapi_kontributor
     * - upload_abstrak / abstrak_ditolak / menunggu_abstrak
     * - upload_fullpaper / fp_menunggu_review / fp_perbaikan
     * - menunggu_loa
     * - bayar / pembayaran_xxx
     * - siap_absen / sudah_absen
     */
    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $event = $this->eventModel->find($eventId);
        $reg   = $this->regModel->findUserReg($eventId, $userId);

        $state = 'belum_daftar';
        $label = 'Belum terdaftar';
        $hint  = 'Klik Daftar untuk mulai';
        $can   = ['register' => true];

        if (!$reg) {
            return compact('state','label','hint','can') + ['reg'=>null];
        }

        if (!$this->isContributorCompleted($reg)) {
            $state='lengkapi_kontributor';
            $label='Lengkapi data kontributor';
            $hint='Wajib sebelum mengirim abstrak';
            $can=['goto_kontributor'=>true,'cancel'=>true];
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        $abstract = $this->getLatestAbstract($eventId, $userId);
        $hasAbs   = !empty($abstract);
        $hasFP    = $this->hasFullPaper($abstract);
        $pay      = $this->getLatestPayment($eventId, $userId);
        $eligible = $this->isEligibleToPay($eventId, $userId);

        if (!$hasAbs) {
            $state='upload_abstrak';
            $label='Upload abstrak';
            $hint='Simpan & lanjut ke Full Paper';
            $can=['upload'=>true,'cancel'=>true];
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        $absStatus = strtolower($abstract['status'] ?? '');

        // === ABS DITOLAK: final, tidak bisa upload lagi di event ini ===
        if ($absStatus === 'ditolak') {
            $state = 'abstrak_ditolak';
            $label = 'Abstrak ditolak';
            $hint  = 'Anda tidak dapat melanjutkan event ini. Silakan mengikuti event lain.';
            $can   = ['view_abstrak' => true]; // hanya lihat abstrak, tidak ada upload baru
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        if ($absStatus === 'diterima') {
            if (!$hasFP) {
                $state='upload_fullpaper';
                $label='Upload Full Paper';
                $hint=!empty($event['full_paper_deadline'])
                    ? ('Batas: '.date('d M Y H:i', strtotime($event['full_paper_deadline'])))
                    : 'Silakan unggah Full Paper';
                $can=['upload_fullpaper'=>true];
                return compact('state','label','hint','can')+['reg'=>$reg];
            }

            $fpStatus = strtolower($abstract['full_paper_status'] ?? 'uploaded');

            // Revisi / ditolak -> perbaikan FP
            if (in_array($fpStatus, ['revision','revisi','rejected','ditolak'], true)) {
                $state='fp_perbaikan';
                $label=($fpStatus==='revision' || $fpStatus==='revisi')
                    ? 'Revisi Full Paper'
                    : 'Full Paper ditolak';
                $hint='Silakan upload ulang Full Paper';
                $can=['reupload_fullpaper'=>true];
                return compact('state','label','hint','can')+['reg'=>$reg];
            }

            // Accepted -> cek LOA (eligible_to_pay) & pembayaran
            $isAccepted = in_array($fpStatus, ['accepted','acc','approved','diterima'], true);

            if ($isAccepted && empty($pay)) {
                if (!$eligible) {
                    $state = 'menunggu_loa';
                    $label = 'Menunggu LoA';
                    $hint  = 'Full paper diterima. Tunggu Letter of Acceptance dari panitia sebelum melakukan pembayaran.';
                    $can   = ['view_fullpaper' => true];
                    return compact('state','label','hint','can')+['reg'=>$reg];
                }

                // eligible_to_pay = true => boleh langsung bayar
                $state='bayar';
                $label='Silakan lakukan pembayaran';
                $hint='Pembayaran dibuka setelah LoA diterbitkan.';
                $can=['pay'=>true];
                return compact('state','label','hint','can')+['reg'=>$reg];
            }

            // masih uploaded / menunggu review
            if (!$isAccepted) {
                $state='fp_menunggu_review';
                $label='Menunggu review Full Paper';
                $hint='Tunggu keputusan reviewer.';
                $can=[];
                // jangan return dulu: kalau sudah ada pembayaran (kasus lama) akan diproses di blok pembayaran di bawah
            }
        } else {
            $state='menunggu_abstrak';
            $label='Menunggu hasil review abstrak';
            $hint='Tunggu ACC';
            $can=['view_abstrak'=>true];
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        // Jika sudah ada pembayaran, status flow ditentukan oleh pembayaran
        if (!empty($pay)) {
            $pstat = strtolower($pay['status'] ?? '');
            switch ($pstat) {
                case 'pending':
                    $state='pembayaran_pending';
                    $label='Pembayaran pending';
                    $hint='Sedang diproses';
                    $can=['pay_detail'=>true];
                    break;
                case 'rejected':
                case 'ditolak':
                    $state='pembayaran_ditolak';
                    $label='Pembayaran ditolak';
                    $hint='Perbaiki & bayar ulang';
                    $can=['pay_reupload'=>true];
                    break;
                case 'canceled':
                case 'expired':
                    $state='pembayaran_kedaluwarsa';
                    $label='Pembayaran kedaluwarsa';
                    $hint='Lakukan pembayaran lagi';
                    $can=['pay'=>true];
                    break;
                case 'verified':
                    $hadir = $this->absensiModel
                        ->where('id_user',$userId)
                        ->where('event_id',$eventId)
                        ->where('status','hadir')
                        ->countAllResults() > 0;
                    if ($hadir) {
                        $state='sudah_absen';
                        $label='Sudah absen';
                        $hint='Terima kasih telah hadir';
                        $can=['absen_detail'=>true];
                    } else {
                        $state='siap_absen';
                        $label='Pembayaran diterima';
                        $hint='Anda sudah terdaftar, jangan lupa absen';
                        $can=['absen'=>true,'documents'=>true];
                    }
                    break;
                default:
                    $state='pembayaran_pending';
                    $label='Pembayaran diproses';
                    $hint='Status akan diperbarui';
                    $can=['pay_detail'=>true];
                    break;
            }
        }

        return compact('state','label','hint','can')+['reg'=>$reg];
    }

    private function buildIndexUi(int $eventId, array $flow, ?int $latestPaymentId, bool $isOpen): array
    {
        $state = $flow['state'] ?? 'belum_daftar';
        $label = $flow['label'] ?? '';
        $hint  = $flow['hint']  ?? '';

        $chipClass = match ($state) {
            'lengkapi_kontributor','upload_abstrak','upload_fullpaper','bayar'
                => 'bg-primary-subtle text-primary',
            'menunggu_abstrak','fp_menunggu_review','pembayaran_pending','menunggu_loa'
                => 'bg-warning-subtle text-warning',
            'fp_perbaikan','abstrak_ditolak','pembayaran_ditolak'
                => 'bg-danger-subtle text-danger',
            'pembayaran_kedaluwarsa'
                => 'bg-secondary-subtle text-secondary',
            'siap_absen','sudah_absen'
                => 'bg-success-subtle text-success',
            default
                => 'bg-secondary-subtle text-secondary',
        };

        $cta = [
            'visible'  => false,
            'label'    => 'Detail Event',
            'url'      => site_url('presenter/events/detail/'.$eventId),
            'class'    => 'btn-outline-primary',
            'disabled' => false,
        ];
        $set = function($label,$url,$class='btn-primary') use (&$cta){
            $cta['visible'] = true;
            $cta['label']   = $label;
            $cta['url']     = $url;
            $cta['class']   = $class;
        };

        switch ($state) {
            case 'belum_daftar':
                if ($isOpen) {
                    // diarahkan ke form pilih mode
                    $set('Daftar', site_url('presenter/events/showRegistrationForm/'.$eventId));
                }
                break;
            case 'lengkapi_kontributor':
                $set('Lengkapi Kontributor', site_url('presenter/kontributor/start/'.$eventId));
                break;
            case 'upload_abstrak':
                $set('Upload Abstrak', site_url('presenter/abstrak/create/'.$eventId));
                break;
            case 'menunggu_abstrak':
                $set('Lihat Abstrak', site_url('presenter/abstrak'));
                break;
            case 'abstrak_ditolak':
                // Tidak boleh upload ulang; hanya lihat abstrak
                $set('Lihat Abstrak', site_url('presenter/abstrak'), 'btn-outline-danger');
                break;
            case 'upload_fullpaper':
                $set('Upload Full Paper', site_url('presenter/fullpaper'));
                break;
            case 'fp_perbaikan':
                $set('Upload Ulang Full Paper', site_url('presenter/fullpaper'), 'btn-danger');
                break;
            case 'fp_menunggu_review':
                $set('Cek Full Paper', site_url('presenter/fullpaper'), 'btn-outline-secondary');
                break;
            case 'menunggu_loa':
                $set('Lihat Status Full Paper', site_url('presenter/fullpaper'), 'btn-outline-secondary');
                break;
            case 'bayar':
                $set('Lakukan Pembayaran', site_url('presenter/pembayaran/instruction/'.$eventId), 'btn-warning');
                break;
            case 'pembayaran_pending':
                $set(
                    'Cek Status Pembayaran',
                    $latestPaymentId
                        ? site_url('presenter/pembayaran/detail/'.$latestPaymentId)
                        : site_url('presenter/pembayaran'),
                    'btn-outline-secondary'
                );
                break;
            case 'pembayaran_ditolak':
            case 'pembayaran_kedaluwarsa':
                $set('Bayar Ulang', site_url('presenter/pembayaran/instruction/'.$eventId), 'btn-danger');
                break;
            case 'siap_absen':
            case 'sudah_absen':
                $set('Lihat Event', site_url('presenter/events/detail/'.$eventId), 'btn-success');
                break;
            default:
                break;
        }

        return [
            'show_chip'  => $state !== 'belum_daftar',
            'chip_label' => $label,
            'chip_hint'  => $hint,
            'chip_class' => $chipClass,
            'cta'        => $cta,
        ];
    }

    private function buildDetailActions(int $eventId, int $userId, ?array $reg, ?array $abstrak, ?array $payment): array
    {
        $primary = null;
        $secondary = null;

        // flags
        $isReg = (bool)$reg;
        $kontributorDone = $this->isContributorCompleted($reg);
        $abStatus = strtolower($abstrak['status'] ?? '');
        $fpStatus = strtolower($abstrak['full_paper_status'] ?? '');
        $hasFP    = $this->hasFullPaper($abstrak);
        $payStatus= strtolower($payment['status'] ?? '');
        $eligible = $this->isEligibleToPay($eventId, $userId);

        // helper waiting
        $isWaiting = function($v){
            if ($v === null || $v === '') return true;
            $v = strtolower((string)$v);
            return in_array($v, ['menunggu','pending','sedang_direview','uploaded','revision','revisi'], true);
        };

        if (!$isReg) {
            $primary = [
                'label'   => 'Daftar',
                'url'     => site_url('presenter/events/showRegistrationForm/'.$eventId),
                'class'   => 'btn-primary',
                'confirm' => 'Daftar ke event ini sekarang?',
            ];
        } else {
            if (!$kontributorDone) {
                $primary = [
                    'label' => 'Lengkapi Kontributor',
                    'url'   => site_url('presenter/kontributor/start/'.$eventId),
                    'class' => 'btn-primary',
                ];
            } else {
                if ($abStatus === '') {
                    $primary = [
                        'label' => 'Upload Abstrak',
                        'url'   => site_url('presenter/abstrak/create/'.$eventId),
                        'class' => 'btn-primary',
                    ];
                } elseif ($abStatus === 'ditolak') {
                    // Abstrak sudah ditolak final => hanya bisa lihat
                    $absId = (int)($abstrak['id_abstrak'] ?? 0);
                    $primary = [
                        'label' => 'Lihat Abstrak',
                        'url'   => $absId
                            ? site_url('presenter/abstrak/detail/'.$absId)
                            : site_url('presenter/abstrak'),
                        'class' => 'btn-outline-danger',
                    ];
                } elseif ($abStatus === 'diterima') {
                    if (!$hasFP) {
                        $primary = [
                            'label' => 'Upload Full Paper',
                            'url'   => site_url('presenter/fullpaper/create/'.$eventId),
                            'class' => 'btn-primary',
                        ];
                    } else {
                        $isRevision = in_array($fpStatus, ['revisi','revision','ditolak','rejected'], true);
                        $isAccepted = in_array($fpStatus, ['diterima','accepted','acc','approved'], true);

                        if ($isRevision) {
                            $primary = [
                                'label' => 'Upload Ulang Full Paper',
                                'url'   => site_url('presenter/fullpaper/create/'.$eventId),
                                'class' => 'btn-warning',
                            ];
                        } elseif ($isAccepted) {
                            // Belum ada record pembayaran
                            if (!$payment || $payStatus === '') {
                                if (!$eligible) {
                                    $primary = [
                                        'label' => 'Menunggu LoA',
                                        'url'   => site_url('presenter/fullpaper/detail/'.$eventId),
                                        'class' => 'btn-outline-secondary',
                                    ];
                                } else {
                                    $primary = [
                                        'label' => 'Lanjutkan Pembayaran',
                                        'url'   => site_url('presenter/pembayaran/instruction/'.$eventId),
                                        'class' => 'btn-success',
                                    ];
                                }
                            } elseif ($payStatus === 'pending') {
                                $primary = [
                                    'label' => 'Cek Status Pembayaran',
                                    'url'   => site_url('presenter/pembayaran'),
                                    'class' => 'btn-outline-success',
                                ];
                            } elseif (in_array($payStatus, ['rejected','ditolak','canceled','expired'], true)) {
                                $primary = [
                                    'label' => 'Bayar Ulang',
                                    'url'   => site_url('presenter/pembayaran/instruction/'.$eventId),
                                    'class' => 'btn-danger',
                                ];
                            } elseif ($payStatus === 'verified') {
                                $primary = [
                                    'label' => 'Buka Halaman Absensi',
                                    'url'   => site_url('presenter/absensi'),
                                    'class' => 'btn-info',
                                ];
                            }
                        }
                    }
                }
            }

            // Secondary (Batalkan) — hanya jika: sudah upload abstrak & abstrak waiting & (FP waiting atau belum diupload)
            $hasAbs      = !empty($abstrak);
            $absWaiting  = $hasAbs ? $isWaiting($abStatus) : false;
            $fpWaiting   = $hasFP ? $isWaiting($fpStatus) : true; // belum upload FP = baru/menunggu
            if ($hasAbs && $absWaiting && $fpWaiting) {
                $secondary = [
                    'label'   => 'Batalkan Pendaftaran',
                    'url'     => site_url('presenter/events/cancel/'.$eventId),
                    'class'   => 'btn-outline-danger',
                    'confirm' => 'Batalkan pendaftaran? Data abstrak/FP & pembayaran non-verified akan dihapus.',
                ];
            }
        }

        return [
            'primary'   => $primary,
            'secondary' => $secondary,
        ];
    }

    private function stateToBadge(string $state): string
    {
        return match ($state) {
            'lengkapi_kontributor','upload_abstrak','upload_fullpaper','bayar'
                => 'info',
            'menunggu_abstrak','fp_menunggu_review','pembayaran_pending','menunggu_loa'
                => 'warning',
            'fp_perbaikan','abstrak_ditolak','pembayaran_ditolak'
                => 'danger',
            'pembayaran_kedaluwarsa'
                => 'secondary',
            'siap_absen','sudah_absen'
                => 'success',
            default
                => 'secondary',
        };
    }
}
