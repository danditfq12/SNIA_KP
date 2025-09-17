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

    /** INDEX: daftar event (tersedia & ditutup), status singkat per-event + UI (chip+CTA) */
    public function index()
    {
        $userId = (int) session()->get('id_user');
        $q      = trim($this->request->getGet('q') ?? '');

        // Event aktif (untuk box "Tersedia")
        $available = $this->eventModel->getEventsWithOpenRegistration();

        // Semua event (untuk pisahkan yg ditutup)
        $all = $this->eventModel->orderBy('event_date', 'DESC')->findAll();

        // Filter pencarian (opsional)
        if ($q !== '') {
            $filterBy = function(array $rows) use ($q) {
                return array_values(array_filter($rows, function($e) use ($q) {
                    $hay = strtolower(($e['title'] ?? '') . ' ' . ($e['description'] ?? '') . ' ' . ($e['location'] ?? ''));
                    return str_contains($hay, strtolower($q));
                }));
            };
            $available = $filterBy($available);
            $all       = $filterBy($all);
        }

        // Daftar id event yang masih buka
        $availableIds = array_column($available, 'id');

        // Status flow + UI per event
        $statusIndex = [];
        foreach ($all as $ev) {
            $flow   = $this->computeFlowStatus((int)$ev['id'], $userId);
            $pay    = $this->getLatestPayment((int)$ev['id'], $userId);
            $payId  = $pay['id_pembayaran'] ?? null;
            $isOpen = in_array($ev['id'], $availableIds, true);

            $statusIndex[(int)$ev['id']] = $flow + [
                'ui' => $this->buildIndexUi((int)$ev['id'], $flow, $payId, $isOpen),
            ];
        }

        // Event ditutup = all - available
        $closed = array_values(array_filter($all, function($e) use ($availableIds){
            return !in_array($e['id'], $availableIds, true);
        }));

        return view('role/presenter/events/index', [
            'title'       => 'Event',
            'available'   => $available,
            'closed'      => $closed,
            'statusIndex' => $statusIndex,
            'q'           => $q,
        ]);
    }

    /** DETAIL: informasi lengkap + CTA sesuai state (tidak diubah) */
    public function detail($id)
    {
        $userId = (int) session()->get('id_user');
        $event  = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to('/presenter/events')->with('error','Event tidak ditemukan.');
        }

        $reg = $this->regModel->findUserReg($event['id'], $userId);

        $abstrak = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $event['id'])
            ->orderBy('id_abstrak','DESC')
            ->first();

        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $event['id'])
            ->orderBy('id_pembayaran', 'DESC')
            ->first();

        $flow = $this->computeFlowStatus($event['id'], $userId);

        return view('role/presenter/events/detail', [
            'title'   => 'Detail Event',
            'event'   => $event,
            'reg'     => $reg,
            'abstrak' => $abstrak,
            'payment' => $payment,
            'flow'    => $flow,
            'price'   => $this->eventModel->getEventPrice($event['id'], 'presenter', 'offline'),
            'isOpen'  => $this->eventModel->isRegistrationOpen($event['id']),
        ]);
    }

    /** REGISTER */
    public function register($id)
    {
        $userId = (int) session()->get('id_user');

        if (!$this->eventModel->isRegistrationOpen((int)$id)) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $regId = $this->regModel->createPresenterRegistration((int)$id, $userId);
        if ($regId) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('success', 'Berhasil mendaftar. Silakan kirim abstrak.');
        }

        return redirect()->to('/presenter/events/detail/'.$id)->with('error','Gagal mendaftar.');
    }

    /** Batalkan pendaftaran (hanya kalau belum kirim abstrak) */
    public function cancel($id)
    {
        $userId = (int) session()->get('id_user');
        $reg    = $this->regModel->findUserReg((int)$id, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$id)->with('error','Pendaftaran tidak ditemukan.');
        }

        $hasAbstract = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$id)
            ->countAllResults() > 0;

        if ($hasAbstract) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error','Tidak dapat membatalkan karena Anda sudah mengunggah abstrak.');
        }

        $this->regModel->delete($reg['id']);
        return redirect()->to('/presenter/events')->with('success','Pendaftaran dibatalkan.');
    }

    /** ===== Helpers ===== */

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

    /** Menyusun status flow untuk index & detail (cover canceled/expired) */
    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $reg = $this->regModel->findUserReg($eventId, $userId);

        // default (belum daftar)
        $state = 'belum_daftar';
        $label = 'Belum terdaftar';
        $hint  = 'Klik Daftar untuk mulai';
        $can   = ['register' => true];

        if ($reg) {
            $ab   = $this->getLatestAbstract($eventId, $userId);
            $pay  = $this->getLatestPayment($eventId, $userId);

            $hadir = $this->absensiModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'hadir')
                ->countAllResults() > 0;

            if (!$ab) {
                $state = 'upload_abstrak';
                $label = 'Silakan upload abstrak';
                $hint  = 'Wajib sebelum pembayaran';
                $can   = ['upload' => true, 'cancel' => true];
            } else {
                switch (strtolower($ab['status'])) {
                    case 'menunggu':
                    case 'sedang_direview':
                        $state = 'menunggu_abstrak';
                        $label = 'Menunggu hasil abstrak';
                        $hint  = 'Tunggu ACC/revisi/ditolak';
                        $can   = ['view_abstrak' => true];
                        break;

                    case 'revisi':
                        $state = 'revisi_abstrak';
                        $label = 'Revisi abstrak';
                        $hint  = 'Silakan unggah ulang dokumen revisi';
                        $can   = ['reupload' => true];
                        break;

                    case 'ditolak':
                        $state = 'abstrak_ditolak';
                        $label = 'Abstrak ditolak';
                        $hint  = 'Anda dapat kirim ulang abstrak baru';
                        $can   = ['upload' => true];
                        break;

                    case 'diterima':
                        if (!$pay) {
                            $state = 'bayar';
                            $label = 'Silakan lakukan pembayaran';
                            $hint  = 'Pembayaran digital via Midtrans';
                            $can   = ['pay' => true];
                        } else {
                            $pstat = strtolower($pay['status'] ?? '');
                            switch ($pstat) {
                                case 'pending':
                                    $state = 'pembayaran_pending';
                                    $label = 'Pembayaran pending';
                                    $hint  = 'Sedang diproses / menunggu verifikasi';
                                    $can   = ['pay_detail' => true];
                                    break;

                                case 'rejected':
                                case 'ditolak':
                                    $state = 'pembayaran_ditolak';
                                    $label = 'Pembayaran ditolak';
                                    $hint  = 'Periksa catatan & lakukan ulang pembayaran';
                                    $can   = ['pay_reupload' => true];
                                    break;

                                case 'canceled':
                                    $state = 'pembayaran_dibatalkan';
                                    $label = 'Pembayaran dibatalkan';
                                    // sesuai permintaan: keterangannya jadi "Lakukan pembayaran lagi"
                                    $hint  = 'Lakukan pembayaran lagi';
                                    $can   = ['pay' => true];
                                    break;

                                case 'expired':
                                    $state = 'pembayaran_kedaluwarsa';
                                    $label = 'Pembayaran kedaluwarsa';
                                    $hint  = 'Lakukan pembayaran lagi';
                                    $can   = ['pay' => true];
                                    break;

                                case 'verified':
                                    if ($hadir) {
                                        $state = 'sudah_absen';
                                        $label = 'Sudah absen';
                                        $hint  = 'Terima kasih telah hadir';
                                        $can   = ['absen_detail' => true];
                                    } else {
                                        $state = 'siap_absen';
                                        $label = 'Pembayaran diterima';
                                        $hint  = 'Anda sudah benar-benar terdaftar';
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
                        break;
                }
            }
        }

        return [
            'state' => $state,
            'label' => $label,
            'hint'  => $hint,
            'can'   => $can,
            'reg'   => $reg,
        ];
    }

    /** Build UI (chip + CTA) untuk index card per event */
    private function buildIndexUi(int $eventId, array $flow, ?int $latestPaymentId, bool $isOpen): array
    {
        $state = $flow['state'] ?? 'belum_daftar';
        $label = $flow['label'] ?? '';
        $hint  = $flow['hint']  ?? '';

        // chip class mapping
        $chipClass = match ($state) {
            'upload_abstrak', 'bayar'                  => 'bg-primary-subtle text-primary',
            'menunggu_abstrak', 'pembayaran_pending'   => 'bg-warning-subtle text-warning',
            'revisi_abstrak'                           => 'bg-info-subtle text-info',
            'abstrak_ditolak', 'pembayaran_ditolak'    => 'bg-danger-subtle text-danger',
            'pembayaran_dibatalkan', 'pembayaran_kedaluwarsa'
                                                      => 'bg-secondary-subtle text-secondary',
            'siap_absen', 'sudah_absen'                => 'bg-success-subtle text-success',
            default                                    => 'bg-secondary-subtle text-secondary',
        };

        // default: sembunyikan chip kalau belum daftar
        $showChip = $state !== 'belum_daftar';

        // CTA mapping
        $cta = [
            'visible'  => false,
            'label'    => 'Detail Event',
            'url'      => site_url('presenter/events/detail/'.$eventId),
            'class'    => 'btn-outline-primary',
            'disabled' => false,
        ];

        $setCta = function(string $label, string $url, string $class = 'btn-primary', bool $disabled = false) use (&$cta) {
            $cta['visible']  = true;
            $cta['label']    = $label;
            $cta['url']      = $url;
            $cta['class']    = $class;
            $cta['disabled'] = $disabled;
        };

        switch ($state) {
            case 'belum_daftar':
                if ($isOpen) {
                    $setCta('Daftar', site_url('presenter/events/register/'.$eventId), 'btn-primary');
                } else {
                    // pendaftaran tutup → jangan tampilkan CTA kedua (hanya tombol Detail)
                    $cta['visible'] = false;
                }
                break;

            case 'upload_abstrak':
                $setCta('Upload Abstrak', site_url('presenter/abstrak/create/'.$eventId), 'btn-primary');
                break;

            case 'menunggu_abstrak':
                $setCta('Lihat Abstrak', site_url('presenter/abstrak'), 'btn-outline-primary');
                break;

            case 'revisi_abstrak':
                $setCta('Re-upload Abstrak', site_url('presenter/abstrak/create/'.$eventId), 'btn-warning');
                break;

            case 'abstrak_ditolak':
                $setCta('Upload Abstrak Baru', site_url('presenter/abstrak/create/'.$eventId), 'btn-danger');
                break;

            case 'bayar':
                $setCta('Lakukan Pembayaran', site_url('presenter/pembayaran/instruction/'.$eventId), 'btn-primary');
                break;

            case 'pembayaran_pending':
                $detailUrl = $latestPaymentId ? site_url('presenter/pembayaran/detail/'.$latestPaymentId) : site_url('presenter/pembayaran');
                $setCta('Cek Status Pembayaran', $detailUrl, 'btn-warning');
                break;

            case 'pembayaran_ditolak':
                $setCta('Bayar Ulang', site_url('presenter/pembayaran/instruction/'.$eventId), 'btn-danger');
                break;

            case 'pembayaran_dibatalkan':
                // permintaan khusus: wording "Lakukan Pembayaran Lagi"
                $setCta('Lakukan Pembayaran Lagi', site_url('presenter/pembayaran/instruction/'.$eventId), 'btn-secondary');
                break;

            case 'pembayaran_kedaluwarsa':
                $setCta('Lakukan Pembayaran Lagi', site_url('presenter/pembayaran/instruction/'.$eventId), 'btn-secondary');
                break;

            case 'siap_absen':
            case 'sudah_absen':
                $setCta('Lihat Event', site_url('presenter/events/detail/'.$eventId), 'btn-success');
                break;

            default:
                // fallback
                $cta['visible'] = false;
                break;
        }

        return [
            'show_chip'  => $showChip,
            'chip_label' => $label,
            'chip_hint'  => $hint,
            'chip_class' => $chipClass,
            'cta'        => $cta,
        ];
    }
}