<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\FasilitasBenefitModel;

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
    try {
        log_message('debug', '=== Event::index() START ===');
        
        $userId = (int) session()->get('id_user');
        log_message('debug', "User ID: {$userId}");
        
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        log_message('debug', "Search query: {$q}");

        $all = $this->eventModel->orderBy('event_date','DESC')->findAll();
        log_message('debug', "Events found: " . count($all));

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $all = array_values(array_filter($all, function($e) use ($needle){
                $hay = mb_strtolower(($e['title'] ?? '').' '.($e['description'] ?? '').' '.($e['location'] ?? ''));
                return str_contains($hay, $needle);
            }));
            log_message('debug', "After filter: " . count($all));
        }

        $todo = []; 
        $history = [];
        $now = strtotime('today');

        foreach ($all as $ev) {
            try {
                $id = (int) $ev['id'];
                log_message('debug', "Processing event ID: {$id}");
                
                $this->enforceDeadlinePolicies($ev, $userId);

                $isOpen  = $this->eventModel->isRegistrationOpen($id);
                $evTs    = !empty($ev['event_date']) ? strtotime((string)$ev['event_date']) : null;
                $isOver  = $evTs ? ($evTs < $now) : false;

                $reg     = $this->regModel->findUserReg($id, $userId);
                $isReg   = (bool) $reg;

                $flow    = $this->computeFlowStatus($id, $userId);
                $pay     = $this->getLatestPayment($id, $userId);
                $ui      = $this->buildIndexUi($id, $flow, $pay['id_pembayaran'] ?? null, $isOpen);

                // Rest of card building...
                $card = [
                    'id'                    => $id,
                    'title'                 => $ev['title'] ?? '-',
                    'format'                => $ev['format'] ?? '',
                    'event_date'            => $ev['event_date'] ?? null,
                    'event_time'            => $ev['event_time'] ?? null,
                    'registration_deadline' => $ev['registration_deadline'] ?? null,
                    'is_registered' => $isReg,
                    'is_closed'     => !$isOpen,
                    'is_over'       => $isOver,
                    'status_label'  => $flow['label'] ?? '',
                    'status_badge'  => $this->stateToBadge($flow['state'] ?? 'secondary'),
                    'hint'          => $flow['hint'] ?? '',
                    'primary_url'   => $ui['cta']['url']   ?? site_url('presenter/events/detail/'.$id),
                    'primary_text'  => $ui['cta']['label'] ?? 'Detail Event',
                    'primary_class' => $ui['cta']['class'] ?? 'btn-outline-primary',
                    'primary_confirm'=> $ui['cta']['confirm'] ?? null,
                    'primary_disabled' => !empty($ui['cta']['disabled']),
                    'detail_url'    => site_url('presenter/events/detail/'.$id),
                ];

                if (($flow['state'] ?? '') === 'sudah_absen') { $history[] = $card; continue; }
                if ($isOver) { $history[] = $card; continue; }
                if ($isReg)  { $todo[]    = $card; continue; }
                if (!$isOpen){ $history[] = $card; } else { $todo[] = $card; }
                
                log_message('debug', "Event {$id} processed successfully");
                
            } catch (\Exception $e) {
                log_message('error', "Error processing event {$id}: " . $e->getMessage());
                // Skip event yang error, lanjut ke event berikutnya
                continue;
            }
        }

        log_message('debug', "Todo: " . count($todo) . ", History: " . count($history));
        log_message('debug', '=== Event::index() END ===');

        return view('role/presenter/events/index', [
            'title'         => 'Event',
            'todoEvents'    => $todo,
            'historyEvents' => $history,
            'q'             => $q,
        ]);

    } catch (\Exception $e) {
        log_message('error', '=== FATAL ERROR in Event::index() ===');
        log_message('error', 'Message: ' . $e->getMessage());
        log_message('error', 'File: ' . $e->getFile() . ':' . $e->getLine());
        log_message('error', 'Trace: ' . $e->getTraceAsString());
        
        return $this->response->setJSON([
            'error' => true,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ])->setStatusCode(500);
    }
}

    /* ========================= DETAIL ========================= */
    public function detail($id)
    {
        $userId = (int) session()->get('id_user');
        $event  = $this->eventModel->find((int)$id);
        if (!$event) {
            return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        $this->enforceDeadlinePolicies($event, $userId);

        $reg = $this->regModel->findUserReg((int)$event['id'], $userId);

        $abstrak = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$event['id'])
            ->orderBy('id_abstrak','DESC')->first();

        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$event['id'])
            ->orderBy('id_pembayaran','DESC')->first();

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
                    if (is_array($tmp)) $contributors = $tmp;
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

        // --- Fetch Facilities from Database ---
        $fasilitasModel = new \App\Models\FasilitasBenefitModel();
        $fasilitasOnlineData = $fasilitasModel->getFasilitasByType((int)$event['id'], 'presenter_online');
        $fasilitasOfflineData = $fasilitasModel->getFasilitasByType((int)$event['id'], 'presenter_offline');

        // Parse JSON if needed
        $fasilitasOnline = [];
        if (!empty($fasilitasOnlineData['fasilitas'])) {
            $parsed = is_string($fasilitasOnlineData['fasilitas']) 
                ? json_decode($fasilitasOnlineData['fasilitas'], true) 
                : $fasilitasOnlineData['fasilitas'];
            $fasilitasOnline = is_array($parsed) ? $parsed : [];
        }

        $fasilitasOffline = [];
        if (!empty($fasilitasOfflineData['fasilitas'])) {
            $parsed = is_string($fasilitasOfflineData['fasilitas']) 
                ? json_decode($fasilitasOfflineData['fasilitas'], true) 
                : $fasilitasOfflineData['fasilitas'];
            $fasilitasOffline = is_array($parsed) ? $parsed : [];
        }

        // --- Actions (dipakai view) ---
        $actions = $this->buildDetailActions((int)$event['id'], $userId, $reg, $abstrak, $payment);

        return view('role/presenter/events/detail', [
            'title'                 => 'Detail Event',
            'event'                 => $event,
            'reg'                   => $reg,
            'abstrak'               => $abstrak,
            'payment'               => $payment,
            'flow'                  => $flow,
            'price'                 => $this->eventModel->getEventPrice((int)$event['id'], 'presenter', 'offline'),
            'isOpen'                => $this->eventModel->isRegistrationOpen((int)$event['id']),
            'registration_deadline' => $event['registration_deadline'] ?? null,
            'abstract_deadline'     => $event['abstract_deadline'] ?? null,
            'full_paper_deadline'   => $event['full_paper_deadline'] ?? null,
            'contributors'          => $contributors,
            'actions'               => $actions,
            'eligible_to_pay'       => $this->isEligibleToPay((int)$event['id'], $userId),
            'fasilitasOnline'       => $fasilitasOnline,
            'fasilitasOffline'      => $fasilitasOffline,
        ]);
    }

    /* ========================= REGISTER (GET - Pilih Mode) WITH FACILITIES ========================= */
    public function register($id)
    {
        $userId = (int) session()->get('id_user');
        $eventId = (int) $id;

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        if (!$this->eventModel->isRegistrationOpen($eventId)) {
            return redirect()->to('/presenter/events/detail/'.$eventId)
                ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        // Cek apakah sudah terdaftar
        $existingReg = $this->regModel->findUserReg($eventId, $userId);
        if ($existingReg && empty($existingReg['is_dropped'])) {
            return redirect()->to('/presenter/events/detail/'.$eventId)
                ->with('info', 'Anda sudah terdaftar di event ini.');
        }

        $this->cleanupOrphanReg($eventId, $userId);

        // Tentukan opsi mode berdasarkan format event
        $format = strtolower($event['format'] ?? '');
        $options = [];
        if (in_array($format, ['online','both'], true)) $options[] = 'online';
        if (in_array($format, ['offline','both'], true)) $options[] = 'offline';

        // Get pricing untuk presenter dari wave aktif
        $pricing = [
            'presenter' => [
                'online'  => $this->eventModel->getEventPrice($eventId, 'presenter', 'online'),
                'offline' => $this->eventModel->getEventPrice($eventId, 'presenter', 'offline'),
            ]
        ];

        // Get wave info untuk ditampilkan
        $waveInfo = null;
        if (method_exists($this->eventModel, 'getCurrentWave')) {
            $activeWave = $this->eventModel->getCurrentWave($eventId);
            if ($activeWave) {
                $waveInfo = [
                    'wave_number' => $activeWave['wave_number'] ?? 1,
                    'deadline'    => $activeWave['registration_deadline'] ?? ($event['registration_deadline'] ?? null),
                ];
            }
        }

        // ========== AMBIL FASILITAS DARI DATABASE ==========
        $fasilitasModel = new FasilitasBenefitModel();
        
        $fasilitasOnline = [];
        $fasilitasOffline = [];
        
        // Ambil fasilitas presenter online
        $facilityOnline = $fasilitasModel->getFasilitasByType($eventId, 'presenter_online');
        if ($facilityOnline && !empty($facilityOnline['fasilitas'])) {
            $fasilitasOnline = is_array($facilityOnline['fasilitas']) 
                ? $facilityOnline['fasilitas'] 
                : json_decode($facilityOnline['fasilitas'], true);
        }
        
        // Ambil fasilitas presenter offline
        $facilityOffline = $fasilitasModel->getFasilitasByType($eventId, 'presenter_offline');
        if ($facilityOffline && !empty($facilityOffline['fasilitas'])) {
            $fasilitasOffline = is_array($facilityOffline['fasilitas']) 
                ? $facilityOffline['fasilitas'] 
                : json_decode($facilityOffline['fasilitas'], true);
        }

        return view('role/presenter/events/register', [
            'title'            => 'Pilih Mode Kehadiran - Presenter',
            'event'            => $event,
            'options'          => $options,
            'pricing'          => $pricing,
            'waveInfo'         => $waveInfo,
            'fasilitasOnline'  => $fasilitasOnline,
            'fasilitasOffline' => $fasilitasOffline,
        ]);
    }

    /* ========================= REGISTER POST (Simpan Mode & Lanjut Kontributor) ========================= */
    public function registerPost($id)
    {
        $userId = (int) session()->get('id_user');
        $eventId = (int) $id;

        if (!$this->eventModel->isRegistrationOpen($eventId)) {
            return redirect()->to('/presenter/events/detail/'.$eventId)
                ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $mode = strtolower(trim($this->request->getPost('mode_kehadiran') ?? ''));
        if (!in_array($mode, ['online','offline'], true)) {
            return redirect()->back()->withInput()
                ->with('error', 'Mode kehadiran tidak valid.');
        }

        $this->cleanupOrphanReg($eventId, $userId);

        // Simpan registrasi dengan mode yang dipilih
        $data = [
            'id_event'       => $eventId,
            'id_user'        => $userId,
            'mode_kehadiran' => $mode,
            'status'         => 'menunggu_pembayaran',
            'qr_token'       => bin2hex(random_bytes(16)),
        ];

        $this->regModel->insert($data);
        $regId = $this->regModel->getInsertID();

        if ($regId) {
            return redirect()->to('/presenter/kontributor/start/'.$eventId)
                ->with('success', 'Pendaftaran berhasil! Silakan lengkapi data kontributor.');
        }

        return redirect()->to('/presenter/events/detail/'.$eventId)
            ->with('error', 'Gagal mendaftar. Silakan coba lagi.');
    }

    /* ========================= CANCEL (HARD DROP USER) ========================= */
    public function cancel($id)
    {
        $userId = (int) session()->get('id_user');
        $id     = (int) $id;

        $reg = $this->regModel->findUserReg($id, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$id)->with('error', 'Pendaftaran tidak ditemukan.');
        }

        $abstract = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $id)
            ->orderBy('id_abstrak','DESC')
            ->first();

        $absStatus = strtolower($abstract['status'] ?? '');
        $fpStatus  = strtolower($abstract['full_paper_status'] ?? '');

        $isWaiting = function(?string $v): bool {
            if ($v === null || $v === '') return true;
            $v = strtolower($v);
            return in_array($v, ['menunggu','pending','sedang_direview','uploaded','revision','revisi'], true);
        };

        $hasAbs        = !empty($abstract);
        $absWaiting    = $hasAbs ? $isWaiting($absStatus) : false;
        $hasFP         = $this->hasFullPaper($abstract);
        $fpWaiting     = $hasFP ? $isWaiting($fpStatus) : true;
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

        $hasAbstractReviewed = in_array($absStatus, ['diterima','accepted','acc','approved','ditolak','rejected'], true);
        if ($hasAbstractReviewed) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Tidak bisa membatalkan karena abstrak Anda sudah direview.');
        }

        $this->regModel->delete((int)$reg['id']);
        return redirect()->to('/presenter/events')->with('success', 'Pendaftaran dibatalkan.');
    }

    /* ========================= HELPERS ========================= */

    private function getLatestAbstract(int $eventId, int $userId): ?array
    {
        return $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('id_abstrak','DESC')->first();
    }

    private function getLatestPayment(int $eventId, int $userId): ?array
    {
        return $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('id_pembayaran','DESC')->first();
    }

    private function isEligibleToPay(int $eventId, int $userId): bool
    {
        $db = \Config\Database::connect();

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
                if ($v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true') return true;
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

        $absDeadline = !empty($event['abstract_deadline'])    ? strtotime($event['abstract_deadline'])    : null;
        $fpDeadline  = !empty($event['full_paper_deadline'])  ? strtotime($event['full_paper_deadline'])  : null;
        $regDL       = !empty($event['registration_deadline'])? strtotime($event['registration_deadline']) : null;

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

    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $event = $this->eventModel->find($eventId);
        $reg   = $this->regModel->findUserReg($eventId, $userId);

        $state = 'belum_daftar';
        $label = 'Belum terdaftar';
        $hint  = 'Klik Daftar untuk mulai';
        $can   = ['register' => true];

        if (!$reg) return compact('state','label','hint','can') + ['reg'=>null];

        if (!$this->isContributorCompleted($reg)) {
            $state='lengkapi_kontributor'; $label='Lengkapi data kontributor'; $hint='Wajib sebelum mengirim abstrak';
            $can=['goto_kontributor'=>true,'cancel'=>true];
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        $abstract = $this->getLatestAbstract($eventId, $userId);
        $hasAbs   = !empty($abstract);
        $hasFP    = $this->hasFullPaper($abstract);
        $pay      = $this->getLatestPayment($eventId, $userId);
        $eligible = $this->isEligibleToPay($eventId, $userId);

        if (!$hasAbs) {
            $state='upload_abstrak'; $label='Upload abstrak'; $hint='Simpan & lanjut ke Full Paper';
            $can=['upload'=>true,'cancel'=>true];
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        $absStatus = strtolower($abstract['status'] ?? '');

        if ($absStatus === 'ditolak') {
            $state = 'abstrak_ditolak';
            $label = 'Abstrak ditolak';
            $hint  = 'Anda tidak dapat melanjutkan event ini. Silakan mengikuti event lain.';
            $can   = ['view_abstrak' => true];
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        if ($absStatus === 'diterima') {
            if (!$hasFP) {
                $state='upload_fullpaper'; $label='Upload Full Paper';
                $hint=!empty($event['full_paper_deadline']) ? ('Batas: '.date('d M Y H:i', strtotime($event['full_paper_deadline']))) : 'Silakan unggah Full Paper';
                $can=['upload_fullpaper'=>true];
                return compact('state','label','hint','can')+['reg'=>$reg];
            }

            $fpStatus = strtolower($abstract['full_paper_status'] ?? 'uploaded');

            if (in_array($fpStatus, ['revision','revisi','rejected','ditolak'], true)) {
                $state='fp_perbaikan';
                $label=($fpStatus==='revision' || $fpStatus==='revisi')?'Revisi Full Paper':'Full Paper ditolak';
                $hint='Silakan upload ulang Full Paper';
                $can=['reupload_fullpaper'=>true];
                return compact('state','label','hint','can')+['reg'=>$reg];
            }

            $isAccepted = in_array($fpStatus, ['accepted','acc','approved','diterima'], true);

            if ($isAccepted && empty($pay)) {
                if (!$eligible) {
                    $state = 'menunggu_loa';
                    $label = 'Menunggu LoA';
                    $hint  = 'Full paper diterima. Tunggu Letter of Acceptance dari panitia sebelum melakukan pembayaran.';
                    $can   = ['view_fullpaper' => true];
                    return compact('state','label','hint','can')+['reg'=>$reg];
                }

                $state='bayar'; $label='Silakan lakukan pembayaran';
                $hint='Pembayaran dibuka setelah LoA diterbitkan.';
                $can=['pay'=>true];
                return compact('state','label','hint','can')+['reg'=>$reg];
            }

            if (!$isAccepted) {
                $state='fp_menunggu_review';
                $label='Menunggu review Full Paper';
                $hint='Tunggu keputusan reviewer.';
                $can=[];
            }
        } else {
            $state='menunggu_abstrak'; $label='Menunggu hasil review abstrak'; $hint='Tunggu ACC';
            $can=['view_abstrak'=>true];
            return compact('state','label','hint','can')+['reg'=>$reg];
        }

        if (!empty($pay)) {
            $pstat = strtolower($pay['status'] ?? '');
            switch ($pstat) {
                case 'pending':
                    $state='pembayaran_pending'; $label='Pembayaran pending'; $hint='Sedang diproses'; $can=['pay_detail'=>true];
                    break;
                case 'rejected':
                case 'ditolak':
                    $state='pembayaran_ditolak'; $label='Pembayaran ditolak'; $hint='Perbaiki & bayar ulang'; $can=['pay_reupload'=>true];
                    break;
                case 'canceled':
                case 'expired':
                    $state='pembayaran_kedaluwarsa'; $label='Pembayaran kedaluwarsa'; $hint='Lakukan pembayaran lagi'; $can=['pay'=>true];
                    break;
                case 'verified':
                    $hadir = $this->absensiModel
                        ->where('id_user',$userId)->where('event_id',$eventId)->where('status','hadir')
                        ->countAllResults() > 0;
                    if ($hadir) {
                        $state='sudah_absen'; $label='Sudah absen'; $hint='Terima kasih telah hadir'; $can=['absen_detail'=>true];
                    } else {
                        $state='siap_absen'; $label='Pembayaran diterima'; $hint='Anda sudah terdaftar, jangan lupa absen'; $can=['absen'=>true,'documents'=>true];
                    }
                    break;
                default:
                    $state='pembayaran_pending'; $label='Pembayaran diproses'; $hint='Status akan diperbarui'; $can=['pay_detail'=>true];
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
            'lengkapi_kontributor','upload_abstrak','upload_fullpaper','bayar' => 'bg-primary-subtle text-primary',
            'menunggu_abstrak','fp_menunggu_review','pembayaran_pending','menunggu_loa' => 'bg-warning-subtle text-warning',
            'fp_perbaikan','abstrak_ditolak','pembayaran_ditolak'             => 'bg-danger-subtle text-danger',
            'pembayaran_kedaluwarsa'                                          => 'bg-secondary-subtle text-secondary',
            'siap_absen','sudah_absen'                                        => 'bg-success-subtle text-success',
            default                                                            => 'bg-secondary-subtle text-secondary',
        };

        $cta = [
            'visible'=>false,
            'label'=>'Detail Event',
            'url'=>site_url('presenter/events/detail/'.$eventId),
            'class'=>'btn-outline-primary',
            'disabled'=>false,
        ];
        $set = function($label,$url,$class='btn-primary') use (&$cta){ $cta['visible']=true; $cta['label']=$label; $cta['url']=$url; $cta['class']=$class; };

        switch ($state) {
            case 'belum_daftar':
                if ($isOpen) $set('Daftar', site_url('presenter/events/register/'.$eventId));
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
                    $latestPaymentId ? site_url('presenter/pembayaran/detail/'.$latestPaymentId) : site_url('presenter/pembayaran'),
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
        $primary = null; $secondary = null;

        $isReg = (bool)$reg;
        $kontributorDone = $this->isContributorCompleted($reg);
        $abStatus = strtolower($abstrak['status'] ?? '');
        $fpStatus = strtolower($abstrak['full_paper_status'] ?? '');
        $hasFP    = $this->hasFullPaper($abstrak);
        $payStatus= strtolower($payment['status'] ?? '');
        $eligible = $this->isEligibleToPay($eventId, $userId);

        $isWaiting = function($v){
            if ($v === null || $v === '') return true;
            $v = strtolower((string)$v);
            return in_array($v, ['menunggu','pending','sedang_direview','uploaded','revision','revisi'], true);
        };

        if (!$isReg) {
            $primary = [
                'label'   => 'Daftar',
                'url'     => site_url('presenter/events/register/'.$eventId),
                'class'   => 'btn-primary',
                'confirm' => 'Daftar ke event ini sekarang?'
            ];
        } else {
            if (!$kontributorDone) {
                $primary = ['label'=>'Lengkapi Kontributor', 'url'=>site_url('presenter/kontributor/start/'.$eventId), 'class'=>'btn-primary'];
            } else {
                if ($abStatus === '') {
                    $primary = ['label'=>'Upload Abstrak', 'url'=>site_url('presenter/abstrak/create/'.$eventId), 'class'=>'btn-primary'];
                } elseif ($abStatus === 'ditolak') {
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
                        $primary = ['label'=>'Upload Full Paper', 'url'=>site_url('presenter/fullpaper/create/'.$eventId), 'class'=>'btn-primary'];
                    } else {
                        $isRevision = in_array($fpStatus, ['revisi','revision','ditolak','rejected'], true);
                        $isAccepted = in_array($fpStatus, ['diterima','accepted','acc','approved'], true);

                        if ($isRevision) {
                            $primary = ['label'=>'Upload Ulang Full Paper', 'url'=>site_url('presenter/fullpaper/create/'.$eventId), 'class'=>'btn-warning'];
                        } elseif ($isAccepted) {
                            if (!$payment || $payStatus === '') {
                                if (!$eligible) {
                                    $primary = [
                                        'label' => 'Menunggu LoA',
                                        'url'   => site_url('presenter/fullpaper/detail/'.$eventId),
                                        'class' => 'btn-outline-secondary'
                                    ];
                                } else {
                                    $primary = [
                                        'label'=>'Lanjutkan Pembayaran',
                                        'url'=>site_url('presenter/pembayaran/instruction/'.$eventId),
                                        'class'=>'btn-success'
                                    ];
                                }
                            } elseif ($payStatus === 'pending') {
                                $primary = ['label'=>'Cek Status Pembayaran', 'url'=>site_url('presenter/pembayaran'), 'class'=>'btn-outline-success'];
                            } elseif (in_array($payStatus, ['rejected','ditolak','canceled','expired'], true)) {
                                $primary = ['label'=>'Bayar Ulang', 'url'=>site_url('presenter/pembayaran/instruction/'.$eventId), 'class'=>'btn-danger'];
                            } elseif ($payStatus === 'verified') {
                                $primary = ['label'=>'Buka Halaman Absensi', 'url'=>site_url('presenter/absensi'), 'class'=>'btn-info'];
                            }
                        }
                    }
                }
            }

            $hasAbs      = !empty($abstrak);
            $absWaiting  = $hasAbs ? $isWaiting($abStatus) : false;
            $fpWaiting   = $hasFP ? $isWaiting($fpStatus) : true;
            if ($hasAbs && $absWaiting && $fpWaiting) {
                $secondary = [
                    'label'   => 'Batalkan Pendaftaran',
                    'url'     => site_url('presenter/events/cancel/'.$eventId),
                    'class'   => 'btn-outline-danger',
                    'confirm' => 'Batalkan pendaftaran? Data abstrak/FP & pembayaran non-verified akan dihapus.'
                ];
            }
        }

        return ['primary' => $primary, 'secondary' => $secondary];
    }

    private function stateToBadge(string $state): string
    {
        return match ($state) {
            'lengkapi_kontributor','upload_abstrak','upload_fullpaper','bayar' => 'info',
            'menunggu_abstrak','fp_menunggu_review','pembayaran_pending','menunggu_loa' => 'warning',
            'fp_perbaikan','abstrak_ditolak','pembayaran_ditolak'             => 'danger',
            'pembayaran_kedaluwarsa'                                          => 'secondary',
            'siap_absen','sudah_absen'                                        => 'success',
            default                                                            => 'secondary',
        };
    }
}