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
        helper(['date', 'text']);
    }

    /* ========================= INDEX ========================= */
    public function index()
    {
        $userId = (int) session()->get('id_user');
        $q      = trim((string) ($this->request->getGet('q') ?? ''));

        $available = $this->eventModel->getEventsWithOpenRegistration();
        $all       = $this->eventModel->orderBy('event_date', 'DESC')->findAll();

        if ($q !== '') {
            $filter = function(array $rows) use ($q) {
                $needle = mb_strtolower($q);
                return array_values(array_filter($rows, function($e) use ($needle) {
                    $hay = mb_strtolower(($e['title'] ?? '') . ' ' . ($e['description'] ?? '') . ' ' . ($e['location'] ?? ''));
                    return str_contains($hay, $needle);
                }));
            };
            $available = $filter($available);
            $all       = $filter($all);
        }

        $availableIds = array_column($available, 'id');
        $statusIndex  = [];

        foreach ($all as $ev) {
            // Terapkan policy deadline sebelum hitung flow
            $this->enforceDeadlinePolicies($ev, $userId);

            $flow   = $this->computeFlowStatus((int)$ev['id'], $userId);
            $pay    = $this->getLatestPayment((int)$ev['id'], $userId);
            $payId  = $pay['id_pembayaran'] ?? null;
            $isOpen = in_array($ev['id'], $availableIds, true);

            $statusIndex[(int)$ev['id']] = $flow + [
                'ui' => $this->buildIndexUi((int)$ev['id'], $flow, $payId, $isOpen),
            ];
        }

        $closed = array_values(array_filter($all, fn($e) => !in_array($e['id'], $availableIds, true)));

        return view('role/presenter/events/index', [
            'title'       => 'Event',
            'available'   => $available,
            'closed'      => $closed,
            'statusIndex' => $statusIndex,
            'q'           => $q,
        ]);
    }

    /* ========================= DETAIL ========================= */
    public function detail($id)
    {
        $userId = (int) session()->get('id_user');
        $event  = $this->eventModel->find((int)$id);
        if (!$event) {
            return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        // enforce deadline
        $this->enforceDeadlinePolicies($event, $userId);

        $reg = $this->regModel->findUserReg((int)$event['id'], $userId);

        $abstrak = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$event['id'])
            ->orderBy('id_abstrak', 'DESC')->first();

        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$event['id'])
            ->orderBy('id_pembayaran', 'DESC')->first();

        $flow = $this->computeFlowStatus((int)$event['id'], $userId);

        // ==== Ambil daftar kontributor (opsional) ====
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
        ]);
    }

    /* ========================= REGISTER ========================= */
    public function register($id)
    {
        $userId = (int) session()->get('id_user');

        if (!$this->eventModel->isRegistrationOpen((int)$id)) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        // bila ada reg orphan (pernah di-drop), bersihkan
        $this->cleanupOrphanReg((int)$id, $userId);

        $regId = $this->regModel->createPresenterRegistration((int)$id, $userId);
        if ($regId) {
            // langsung ke halaman kontributor (langkah 2)
            return redirect()->to('/presenter/kontributor/start/'.$id)
                ->with('success', 'Terdaftar. Lengkapi data kontributor terlebih dahulu.');
        }

        return redirect()->to('/presenter/events/detail/'.$id)->with('error', 'Gagal mendaftar.');
    }

    /* ========================= CANCEL ========================= */
    public function cancel($id)
    {
        $userId = (int) session()->get('id_user');
        $reg    = $this->regModel->findUserReg((int)$id, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$id)->with('error', 'Pendaftaran tidak ditemukan.');
        }

        $hasAbstract = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$id)
            ->countAllResults() > 0;

        if ($hasAbstract) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Tidak dapat membatalkan karena Anda sudah mengunggah abstrak.');
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
            ->orderBy('id_abstrak', 'DESC')->first();
    }

    private function getLatestPayment(int $eventId, int $userId): ?array
    {
        return $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('id_pembayaran', 'DESC')->first();
    }

    /**
     * KONTRIBUTOR dianggap lengkap jika:
     *  - ada salah satu flag boolean selesai (contributor_done / kontributor_done / profile_completed / is_profile_completed) bernilai true,
     *    ATAU
     *  - field 'afiliasi' di registrasi tidak kosong (minimal afiliasi).
     *
     * Ini menyamakan perilaku dengan controller Abstrak (yang mewajibkan afiliasi).
     */
    private function isContributorCompleted(?array $reg): bool
    {
        if (!$reg) return false;

        // 1) cek flag boolean selesai
        foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
            if (array_key_exists($f, $reg)) {
                $v = $reg[$f];
                if ($v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true') {
                    return true;
                }
            }
        }

        // 2) minimal afiliasi harus ada
        if (!empty($reg['afiliasi'])) return true;

        return false;
    }

    private function hasFullPaper(?array $abstract): bool
    {
        if (!$abstract) return false;
        if (!empty($abstract['full_paper_path'])) return true;
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

    /**
     * DEADLINE policy:
     * - Lewat abstract_deadline & belum upload abstrak → drop.
     * - Lewat full_paper_deadline & sudah ada abstrak tapi belum ada full paper → drop.
     * - Lewat registration_deadline & sudah ada abstrak tapi belum ada full paper → drop.
     */
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

        if ($absDeadline && $now > $absDeadline && !$hasAbstract) {
            $this->regModel->update((int)$reg['id'], ['is_dropped' => true, 'drop_reason' => 'abstract_deadline_passed']);
            $this->regModel->delete((int)$reg['id']);
            return;
        }

        if ($fpDeadline && $now > $fpDeadline && $hasAbstract && !$hasFP) {
            $this->regModel->update((int)$reg['id'], ['is_dropped' => true, 'drop_reason' => 'fullpaper_deadline_passed']);
            $this->regModel->delete((int)$reg['id']);
            return;
        }

        if ($regDL && $now > $regDL && $hasAbstract && !$hasFP) {
            $this->regModel->update((int)$reg['id'], ['is_dropped' => true, 'drop_reason' => 'registration_closed_no_fullpaper']);
            $this->regModel->delete((int)$reg['id']);
            return;
        }
    }

    /** Flow untuk UI (setelah enforceDeadlinePolicies) */
    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $event = $this->eventModel->find($eventId);
        $reg   = $this->regModel->findUserReg($eventId, $userId);

        $state = 'belum_daftar';
        $label = 'Belum terdaftar';
        $hint  = 'Klik Daftar untuk mulai';
        $can   = ['register' => true];

        if (!$reg) {
            return compact('state', 'label', 'hint', 'can') + ['reg' => null];
        }

        if (!$this->isContributorCompleted($reg)) {
            $state = 'lengkapi_kontributor';
            $label = 'Lengkapi data kontributor';
            $hint  = 'Wajib sebelum mengirim abstrak';
            $can   = ['goto_kontributor' => true, 'cancel' => true];
            return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
        }

        $abstract = $this->getLatestAbstract($eventId, $userId);
        $hasAbs   = !empty($abstract);
        $hasFP    = $this->hasFullPaper($abstract);
        $pay      = $this->getLatestPayment($eventId, $userId);

        if (!$hasAbs) {
            $state = 'upload_abstrak';
            $label = 'Upload abstrak';
            $hint  = 'Simpan & lanjut ke Full Paper';
            $can   = ['upload' => true, 'cancel' => true];
            return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
        }

        $absStatus = strtolower($abstract['status'] ?? '');

        if ($absStatus === 'ditolak') {
            $state = 'abstrak_ditolak';
            $label = 'Abstrak ditolak';
            $hint  = 'Silakan upload ulang abstrak';
            $can   = ['upload' => true];
            return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
        }

        if ($absStatus === 'diterima') {
            if (!$hasFP) {
                $state = 'upload_fullpaper';
                $label = 'Upload Full Paper';
                $hint  = !empty($event['full_paper_deadline'])
                    ? 'Batas: ' . date('d M Y H:i', strtotime($event['full_paper_deadline']))
                    : 'Silakan unggah Full Paper';
                $can   = ['upload_fullpaper' => true];
                return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
            }

            $fpStatus = strtolower($abstract['full_paper_status'] ?? 'uploaded');
            if (in_array($fpStatus, ['revision', 'revisi', 'rejected', 'ditolak'], true)) {
                $state = 'fp_perbaikan';
                $label = ($fpStatus === 'revision' || $fpStatus === 'revisi') ? 'Revisi Full Paper' : 'Full Paper ditolak';
                $hint  = 'Silakan upload ulang Full Paper';
                $can   = ['reupload_fullpaper' => true];
                return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
            }

            if (in_array($fpStatus, ['accepted', 'acc', 'approved'], true)) {
                if (!$pay) {
                    $state = 'bayar';
                    $label = 'Silakan lakukan pembayaran';
                    $hint  = 'Pembayaran digital';
                    $can   = ['pay' => true];
                    return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
                }
            }

            $state = 'fp_menunggu_review';
            $label = 'Menunggu review Full Paper';
            $hint  = 'Tunggu ACC dari reviewer';
            $can   = [];
        } else {
            $state = 'menunggu_abstrak';
            $label = 'Menunggu hasil review abstrak';
            $hint  = 'Tunggu ACC';
            $can   = ['view_abstrak' => true];
            return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
        }

        if (!empty($pay)) {
            $pstat = strtolower($pay['status'] ?? '');
            switch ($pstat) {
                case 'pending':
                    $state = 'pembayaran_pending';
                    $label = 'Pembayaran pending';
                    $hint  = 'Sedang diproses';
                    $can   = ['pay_detail' => true];
                    break;
                case 'rejected':
                case 'ditolak':
                    $state = 'pembayaran_ditolak';
                    $label = 'Pembayaran ditolak';
                    $hint  = 'Perbaiki & bayar ulang';
                    $can   = ['pay_reupload' => true];
                    break;
                case 'canceled':
                case 'expired':
                    $state = 'pembayaran_kedaluwarsa';
                    $label = 'Pembayaran kedaluwarsa';
                    $hint  = 'Lakukan pembayaran lagi';
                    $can   = ['pay' => true];
                    break;
                case 'verified':
                    $hadir = $this->absensiModel
                        ->where('id_user', $userId)
                        ->where('event_id', $eventId)
                        ->where('status', 'hadir')
                        ->countAllResults() > 0;
                    if ($hadir) {
                        $state = 'sudah_absen';
                        $label = 'Sudah absen';
                        $hint  = 'Terima kasih telah hadir';
                        $can   = ['absen_detail' => true];
                    } else {
                        $state = 'siap_absen';
                        $label = 'Pembayaran diterima';
                        $hint  = 'Anda sudah terdaftar';
                        $can   = ['absen' => true, 'documents' => true];
                    }
                    break;
                default:
                    $state = 'pembayaran_pending';
                    $label = 'Pembayaran diproses';
                    $hint  = 'Status akan diperbarui';
                    $can   = ['pay_detail' => true];
                    break;
            }
        }

        return compact('state', 'label', 'hint', 'can') + ['reg' => $reg];
    }

    /** Tombol di kartu index */
    private function buildIndexUi(int $eventId, array $flow, ?int $latestPaymentId, bool $isOpen): array
    {
        $state = $flow['state'] ?? 'belum_daftar';
        $label = $flow['label'] ?? '';
        $hint  = $flow['hint']  ?? '';

        $chipClass = match ($state) {
            'lengkapi_kontributor','upload_abstrak','upload_fullpaper','bayar' => 'bg-primary-subtle text-primary',
            'menunggu_abstrak','fp_menunggu_review','pembayaran_pending'      => 'bg-warning-subtle text-warning',
            'fp_perbaikan','abstrak_ditolak','pembayaran_ditolak'             => 'bg-danger-subtle text-danger',
            'pembayaran_kedaluwarsa'                                          => 'bg-secondary-subtle text-secondary',
            'siap_absen','sudah_absen'                                        => 'bg-success-subtle text-success',
            default                                                            => 'bg-secondary-subtle text-secondary',
        };

        $cta = [
            'visible'  => false,
            'label'    => 'Detail Event',
            'url'      => site_url('presenter/events/detail/'.$eventId),
            'class'    => 'btn-outline-primary',
            'disabled' => false,
        ];

        $set = function($label, $url, $class = 'btn-primary') use (&$cta) {
            $cta['visible'] = true; $cta['label'] = $label; $cta['url'] = $url; $cta['class'] = $class;
        };

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
                $set('Upload Ulang Abstrak', site_url('presenter/abstrak/create/'.$eventId), 'btn-danger');
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
            case 'bayar':
                $set('Lakukan Pembayaran', site_url('presenter/pembayaran/instruction/'.$eventId), 'btn-warning');
                break;
            case 'pembayaran_pending':
                $set('Cek Status Pembayaran',
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
}