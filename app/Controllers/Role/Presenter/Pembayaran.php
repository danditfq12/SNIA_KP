<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;
use App\Models\VoucherModel;
use App\Models\UserModel;
use App\Models\EventRegistrationModel;
use App\Models\FullPaperModel;
use App\Services\MidtransService;
use App\Services\NotificationService;

class Pembayaran extends BaseController
{
    protected EventModel $eventModel;
    protected PembayaranModel $payModel;
    protected AbstrakModel $absModel;
    protected VoucherModel $voucherModel;
    protected UserModel $userModel;
    protected EventRegistrationModel $regModel;
    protected FullPaperModel $fpModel;
    protected MidtransService $midtrans;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->eventModel           = new EventModel();
        $this->payModel             = new PembayaranModel();
        $this->absModel             = new AbstrakModel();
        $this->voucherModel         = new VoucherModel();
        $this->userModel            = new UserModel();
        $this->regModel             = new EventRegistrationModel();
        $this->fpModel              = new FullPaperModel();
        $this->midtrans             = new MidtransService();
        $this->notificationService  = new NotificationService();
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? session('id') ?? 0);
    }

    /* ========================================================================
     *  HARGA (disamakan polanya dengan Event::getPrice)
     * ===================================================================== */

    /**
     * Ambil harga dengan urutan:
     * 1. Wave aktif → kolom: {role}_fee_{mode}
     * 2. Pricing Matrix (EventModel::getPricingMatrix)
     * 3. EventModel::getEventPrice
     * 4. Rekaman registrasi (jumlah_bayar) untuk mode_kehadiran yang sama
     */
    private function getPrice(int $eventId, string $mode, string $userRole = 'presenter'): int
    {
        try {
            $mode     = strtolower($mode);
            $userRole = strtolower($userRole);

            // 1) Wave aktif
            $wave = $this->eventModel->getCurrentWave($eventId);
            if ($wave && is_array($wave)) {
                $priceKey = $userRole . '_fee_' . $mode; // contoh: presenter_fee_offline
                $price    = (int)($wave[$priceKey] ?? 0);
                if ($price > 0) {
                    log_message('info', "[Presenter] Price from wave: {$price} ({$priceKey}) event={$eventId}");
                    return $price;
                }
            }

            // 2) Pricing matrix
            if (method_exists($this->eventModel, 'getPricingMatrix')) {
                $pricing = $this->eventModel->getPricingMatrix($eventId);
                if (!empty($pricing[$userRole][$mode])) {
                    $price = (int)$pricing[$userRole][$mode];
                    if ($price > 0) {
                        log_message('info', "[Presenter] Price from pricingMatrix: {$price} ({$userRole}/{$mode}) event={$eventId}");
                        return $price;
                    }
                }
            }

            // 3) getEventPrice (fallback)
            if (method_exists($this->eventModel, 'getEventPrice')) {
                $price = (int)$this->eventModel->getEventPrice($eventId, $userRole, $mode);
                if ($price > 0) {
                    log_message('info', "[Presenter] Price from getEventPrice: {$price} ({$userRole}/{$mode}) event={$eventId}");
                    return $price;
                }
            }

            // 4) Fallback dari record registrasi untuk user + mode yang sama
            $reg = $this->regModel
                ->where('id_event', $eventId)
                ->where('id_user', $this->uid())
                ->where('mode_kehadiran', $mode)
                ->orderBy('id', 'DESC')
                ->first();

            if ($reg && !empty($reg['jumlah_bayar'])) {
                $price = (int)$reg['jumlah_bayar'];
                if ($price > 0) {
                    log_message('info', "[Presenter] Price from registration.jumlah_bayar: {$price} (mode={$mode}) event={$eventId}");
                    return $price;
                }
            }

            log_message('error', "[Presenter] No valid price found (event={$eventId}, mode={$mode}, role={$userRole})");
            return 0;

        } catch (\Exception $e) {
            log_message('error', '[Presenter] getPrice error: ' . $e->getMessage());
            return 0;
        }
    }

    private function formatRupiah(int $n): string
    {
        return 'Rp ' . number_format($n, 0, ',', '.');
    }

    private function applyVoucherDiscount(int $basePrice, ?array $voucher): int
    {
        if (!$voucher) return $basePrice;
        $tipe  = strtolower($voucher['tipe'] ?? '');
        $nilai = (int)($voucher['nilai'] ?? 0);

        if (in_array($tipe, ['percentage','persen'], true)) {
            $discount = min(100, $nilai);
            return max(0, $basePrice - (int)floor($basePrice * $discount / 100));
        }

        return max(0, $basePrice - $nilai);
    }

    private function findValidVoucher(string $code, int $userId, int $eventId): ?array
    {
        $code = trim($code);
        if ($code === '') return null;

        $voucher = $this->voucherModel
            ->where('LOWER(kode_voucher) =', strtolower($code))
            ->where('status', 'aktif')
            ->first();

        if (!$voucher) return null;

        // expiry
        $exp = $voucher['masa_berlaku'] ?? null;
        if (!empty($exp)) {
            $endTs = strtotime(date('Y-m-d 23:59:59', strtotime($exp)));
            if ($endTs && $endTs < time()) return null;
        }

        // quota
        $kuota = (int)($voucher['kuota'] ?? 0);
        if ($kuota > 0) {
            $used = $this->payModel
                ->where('id_voucher', $voucher['id_voucher'])
                ->whereIn('status', ['pending','verified'])
                ->countAllResults();
            if ($used >= $kuota) return null;
        }

        // already used by this user for this event?
        $existing = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('id_voucher', $voucher['id_voucher'])
            ->whereIn('status', ['pending','verified'])
            ->first();

        if ($existing) return null;

        return $voucher;
    }

    /**
     * Guard: pembayaran hanya diizinkan jika:
     * - Ada baris FP terbaru untuk (user,event)
     * - full_paper_path TIDAK kosong
     * - full_paper_status === 'ACCEPTED'
     */
    private function ensureAccepted(int $userId, int $eventId): bool
    {
        $row = $this->fpModel->getLatestRowByUserEvent($userId, $eventId);
        if (!$row) return false;

        $path   = trim((string)($row['full_paper_path'] ?? ''));
        $status = strtoupper((string)($row['full_paper_status'] ?? 'NONE'));

        if ($path === '') return false;
        return $status === FullPaperModel::STATUS_ACCEPTED; // 'ACCEPTED'
    }

    /* ========================================================================
     *  INDEX
     * ===================================================================== */
    public function index()
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        // Sinkron pending + cleanup
        $this->syncPendingPayments($userId);
        $this->cleanupFailedPayments($userId);

        $payments = $this->payModel
            ->select("pembayaran.*, e.title AS event_title, e.event_date, e.event_time, e.format")
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.id_pembayaran', 'DESC')
            ->findAll();

        $eventIds     = array_unique(array_map('intval', array_filter(array_column($payments, 'event_id'))));
        $events       = [];
        $flowStatuses = [];

        if (!empty($eventIds)) {
            $eventRows = $this->eventModel->whereIn('id', $eventIds)->findAll();
            foreach ($eventRows as $ev) {
                $events[$ev['id']]       = $ev;
                $flowStatuses[$ev['id']] = $this->computeFlowStatus($ev['id'], $userId);
            }
        }

        $enhancedPayments = [];
        foreach ($payments as $p) {
            $eventId         = (int)($p['event_id'] ?? 0);
            $p['event']      = $events[$eventId]       ?? null;
            $p['flow']       = $flowStatuses[$eventId] ?? null;

            $method            = strtolower((string)($p['metode'] ?? ''));
            $p['method_label'] = $method ? strtoupper($method) : 'MIDTRANS';
            $p['method_icon']  = $method === 'midtrans' ? 'bi bi-credit-card-2-front' : 'bi bi-wallet2';
            $p['method_badge'] = $method === 'midtrans' ? 'bg-primary' : 'bg-secondary';

            $amount                = (int)($p['jumlah'] ?? 0);
            $p['jumlah_formatted'] = $this->formatRupiah($amount);

            $tanggal           = $p['tanggal_bayar'] ?? $p['created_at'] ?? $p['updated_at'] ?? null;
            $p['tanggal_date'] = $tanggal ? date('d M Y', strtotime($tanggal)) : '-';
            $p['tanggal_time'] = $tanggal ? date('H:i', strtotime($tanggal)) : '';

            $status            = strtolower((string)($p['status'] ?? ''));
            $p['status_badge'] = match ($status) {
                'verified' => 'bg-success',
                'pending'  => 'bg-warning',
                'canceled' => 'bg-danger',
                'expired'  => 'bg-dark',
                default    => 'bg-secondary',
            };

            $p['participation_type'] = $p['participation_type'] ?? ($p['participation'] ?? null);
            $p['participation']      = $p['participation']      ?? $p['participation_type'];

            if (empty($p['event_title']) && !empty($p['event']['title'])) {
                $p['event_title'] = $p['event']['title'];
            }

            // tombol next action (opsional dipakai di view)
            $p['next_action'] = $this->getNextAction($p, $p['flow']);

            $enhancedPayments[] = $p;
        }

        // Tagihan yang masih perlu bayar (FP ACC & belum ada payment pending/verified)
        $eventsNeedingPayment = $this->getEventsNeedingPayment($userId);

        $dueTotal = 0;
        foreach ($eventsNeedingPayment as $bill) {
            $dueTotal += (int)($bill['amount'] ?? 0);
        }

        $dueStats = [
            'count'           => count($eventsNeedingPayment),
            'total'           => $dueTotal,
            'total_formatted' => $this->formatRupiah($dueTotal),
        ];

        return view('role/presenter/pembayaran/index', [
            'title'               => 'Pembayaran',
            'eventsNeedingPayment'=> $eventsNeedingPayment,
            'allPayments'         => $enhancedPayments,
            'dueStats'            => $dueStats,
        ]);
    }

    /* ========================================================================
     *  Helper: daftar event yang HARUS bayar (berdasar registrasi aktif + mode)
     * ===================================================================== */
    private function getEventsNeedingPayment(int $userId): array
    {
        $regs = $this->regModel->listByUser($userId) ?? [];
        if (empty($regs)) return [];

        $rows = [];
        foreach ($regs as $r) {
            // skip kalau sudah di-drop
            if (!empty($r['is_dropped'])) {
                continue;
            }

            $eventId = (int)($r['id_event'] ?? 0);
            if ($eventId <= 0) continue;

            // full paper ACC
            $fp = $this->fpModel->getLatestRowByUserEvent($userId, $eventId);
            if (!$fp) continue;

            $path   = trim((string)($fp['full_paper_path'] ?? ''));
            $status = strtoupper((string)($fp['full_paper_status'] ?? 'NONE'));

            if ($path === '' || $status !== FullPaperModel::STATUS_ACCEPTED) continue;

            // Skip jika sudah ada pembayaran pending / verified
            $existing = $this->payModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->whereIn('status', ['pending', 'verified'])
                ->first();
            if ($existing) continue;

            $ev = $this->eventModel->find($eventId);
            if (!$ev) continue;

            // Gunakan mode dari registrasi untuk harga
            $mode   = $r['mode_kehadiran'] ?? 'offline';
            $amount = $this->getPrice($eventId, $mode, 'presenter');

            $eventDate = $ev['event_date'] ?? null;

            $rows[] = [
                'event_id'         => $eventId,
                'title'            => (string)($ev['title'] ?? '-'),
                'event_date'       => $eventDate,
                'event_date_fmt'   => $eventDate ? date('d M Y', strtotime($eventDate)) : '-',
                'amount'           => $amount,
                'amount_formatted' => $this->formatRupiah($amount),
                'mode_kehadiran'   => $mode,
                'pay_url'          => site_url('presenter/pembayaran/instruction/'.$eventId),
            ];
        }

        usort($rows, fn($a,$b) => strtotime($a['event_date'] ?? '2100-01-01') <=> strtotime($b['event_date'] ?? '2100-01-01'));
        return $rows;
    }

    /* ========================================================================
     *  Flow status (dipake di index / event)
     * ===================================================================== */
    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $reg  = $this->regModel->findUserReg($eventId, $userId);
        $ab   = $this->absModel
                    ->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->orderBy('id_abstrak','DESC')
                    ->first();
        $pay  = $this->payModel
                    ->where('id_user',$userId)
                    ->where('event_id',$eventId)
                    ->orderBy('id_pembayaran','DESC')
                    ->first();

        $state = 'belum_daftar';
        $label = 'Belum terdaftar';
        $hint  = 'Klik Daftar untuk mulai';
        $can   = ['register' => true];

        if (!$reg) {
            return compact('state','label','hint','can') + ['reg'=>$reg];
        }

        if (!$ab) {
            return [
                'state' => 'upload_abstrak',
                'label' => 'Silakan upload abstrak',
                'hint'  => 'Wajib sebelum full paper & pembayaran',
                'can'   => ['upload' => true, 'cancel' => true],
                'reg'   => $reg,
            ];
        }

        switch (strtolower($ab['status'] ?? '')) {
            case 'menunggu':
            case 'sedang_direview':
                return ['state'=>'menunggu_abstrak','label'=>'Menunggu hasil abstrak','hint'=>'Tunggu ACC/revisi/ditolak','can'=>['view_abstrak'=>true],'reg'=>$reg];
            case 'revisi':
                return ['state'=>'revisi_abstrak','label'=>'Revisi abstrak','hint'=>'Unggah revisi abstrak','can'=>['reupload'=>true],'reg'=>$reg];
            case 'ditolak':
                return ['state'=>'abstrak_ditolak','label'=>'Abstrak ditolak','hint'=>'Kirim ulang abstrak baru','can'=>['upload'=>true],'reg'=>$reg];
            case 'diterima':
                $fpRow  = $this->fpModel->getLatestRowByUserEvent($userId, $eventId);
                $fpStat = strtoupper((string)($fpRow['full_paper_status'] ?? 'NONE'));

                if (!$fpRow) {
                    return ['state'=>'upload_fullpaper','label'=>'Silakan upload Full Paper','hint'=>'Wajib sebelum pembayaran','can'=>['upload_fullpaper'=>true],'reg'=>$reg];
                }
                if (in_array($fpStat, ['NONE','UPLOADED'], true)) {
                    return ['state'=>'menunggu_fullpaper','label'=>'Full Paper sedang diproses','hint'=>'Tunggu review','can'=>['view_fullpaper'=>true],'reg'=>$reg];
                }
                if ($fpStat === 'REVISION') {
                    return ['state'=>'revisi_fullpaper','label'=>'Revisi Full Paper','hint'=>'Unggah ulang dokumen revisi','can'=>['reupload_fullpaper'=>true],'reg'=>$reg];
                }
                if ($fpStat === 'REJECTED') {
                    return ['state'=>'fullpaper_ditolak','label'=>'Full Paper ditolak','hint'=>'Unggah ulang sesuai masukan reviewer','can'=>['upload_fullpaper'=>true],'reg'=>$reg];
                }
                if ($fpStat === 'ACCEPTED') {
                    if (!$pay) {
                        return ['state'=>'bayar','label'=>'Silakan lakukan pembayaran','hint'=>'Pembayaran digital via Midtrans','can'=>['pay'=>true],'reg'=>$reg];
                    }
                    if ($pay['status'] === 'pending') {
                        return ['state'=>'pembayaran_pending','label'=>'Menunggu verifikasi pembayaran','hint'=>'Pembayaran sedang diproses','can'=>['pay_detail'=>true],'reg'=>$reg];
                    }
                    if ($pay['status'] === 'canceled') {
                        return ['state'=>'pembayaran_dibatalkan','label'=>'Pembayaran dibatalkan','hint'=>'Silakan bayar ulang','can'=>['pay'=>true],'reg'=>$reg];
                    }
                    if ($pay['status'] === 'verified') {
                        return ['state'=>'siap_absen','label'=>'Siap untuk event','hint'=>'Pembayaran terverifikasi, siap absen','can'=>['absen'=>true,'documents'=>true],'reg'=>$reg];
                    }
                }
                return ['state'=>'menunggu_fullpaper','label'=>'Full Paper diproses','hint'=>'Tunggu ACC/revisi/ditolak','can'=>['view_fullpaper'=>true],'reg'=>$reg];
        }

        return compact('state','label','hint','can') + ['reg'=>$reg];
    }

    private function getNextAction(array $payment, ?array $flow): array
    {
        $status    = strtolower($payment['status'] ?? 'pending');
        $flowState = $flow['state'] ?? 'unknown';

        return match ($status) {
            'pending' => [
                'label'=>'Cek Status',
                'url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                'class'=>'btn-warning',
                'icon'=>'bi-clock'
            ],
            'verified' => $flowState === 'siap_absen'
                ? [
                    'label'=>'Lihat Event',
                    'url'=>site_url('presenter/events/detail/' . $payment['event_id']),
                    'class'=>'btn-success',
                    'icon'=>'bi-calendar-check'
                  ]
                : [
                    'label'=>'Detail',
                    'url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                    'class'=>'btn-outline-success',
                    'icon'=>'bi-check-circle'
                  ],
            'canceled','expired' => $flowState === 'bayar'
                ? [
                    'label'=>'Bayar Ulang',
                    'url'=>site_url('presenter/pembayaran/instruction/' . $payment['event_id']),
                    'class'=>'btn-primary',
                    'icon'=>'bi-arrow-repeat'
                  ]
                : [
                    'label'=>'Detail',
                    'url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                    'class'=>'btn-outline-danger',
                    'icon'=>'bi-x-circle'
                  ],
            default => [
                'label'=>'Detail',
                'url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                'class'=>'btn-outline-secondary',
                'icon'=>'bi-eye'
            ],
        };
    }

    /* ========================================================================
     *  Cleanup abandoned payments
     * ===================================================================== */
    private function cleanupFailedPayments(int $userId): void
    {
        try {
            $this->payModel
                ->where('id_user', $userId)
                ->groupStart()
                    ->where('status', 'draft')
                    ->orWhere('status', 'canceled')
                    ->orGroupStart()
                        ->where('status', 'expired')
                        ->groupStart()
                            ->where('midtrans_snap_token IS NULL')
                            ->where('midtrans_raw_response IS NULL')
                        ->groupEnd()
                    ->groupEnd()
                ->groupEnd()
                ->where('tanggal_bayar <', date('Y-m-d H:i:s', strtotime('-5 minutes')))
                ->delete();

            log_message('info', "[Presenter] Cleaned up abandoned payments for user {$userId}");
        } catch (\Exception $e) {
            log_message('error', '[Presenter] cleanupFailedPayments error: ' . $e->getMessage());
        }
    }

    /* ========================================================================
     *  Sinkronisasi pending payments (Midtrans)
     * ===================================================================== */
    private function syncPendingPayments($userId)
    {
        try {
            $pendingPayments = $this->payModel
                ->where('id_user', $userId)
                ->where('status', 'pending')
                ->where('metode', 'midtrans')
                ->whereNotNull('midtrans_order_id')
                ->where('tanggal_bayar >', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->findAll();

            foreach ($pendingPayments as $payment) {
                $orderId = $payment['midtrans_order_id'];
                if (empty($orderId)) continue;

                try {
                    $statusData        = $this->midtrans->getTransactionStatus($orderId);
                    $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
                    $fraudStatus       = strtolower($statusData['fraud_status'] ?? '');
                    $newStatus         = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

                    if ($newStatus !== 'pending') {
                        $updateData = [
                            'status'               => $newStatus,
                            'midtrans_raw_response'=> json_encode($statusData),
                            'keterangan'           => "Auto sync: {$transactionStatus}",
                            'updated_at'           => date('Y-m-d H:i:s'),
                        ];
                        if ($newStatus === 'verified') {
                            $updateData['verified_at']          = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            $updateData['auto_verified']        = true;
                            $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            if (!empty($payment['id_voucher'])) {
                                $this->voucherModel->reduceQuota($payment['id_voucher']);
                            }
                        }
                        $this->payModel->update($payment['id_pembayaran'], $updateData);
                    }
                } catch (\Exception $e) {
                    log_message('error', "[Presenter] Failed to sync payment {$orderId}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            log_message('error', '[Presenter] syncPendingPayments error: ' . $e->getMessage());
        }
    }

    private function mapMidtransStatus($transactionStatus, $fraudStatus)
    {
        switch ($transactionStatus) {
            case 'settlement': return 'verified';
            case 'capture':
                if ($fraudStatus === 'accept' || empty($fraudStatus)) return 'verified';
                if ($fraudStatus === 'challenge') return 'pending';
                return 'canceled';
            case 'pending': return 'pending';
            case 'deny':
            case 'cancel':
            case 'failure': return 'canceled';
            case 'expire':  return 'expired';
            default:        return 'pending';
        }
    }

    /* ========================================================================
     *  INSTRUCTION (param = event_id, check FP ACC & registrasi aktif)
     * ===================================================================== */
    public function instruction(int $eventId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan.');

        if (!$this->ensureAccepted($userId, $eventId)) {
            return redirect()->to('/presenter/events/detail/' . $eventId)
                ->with('error', 'Instruksi pembayaran muncul setelah FULL PAPER diterima (ACC).');
        }

        // Cek registrasi untuk ambil mode_kehadiran & pastikan belum drop
        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg || !empty($reg['is_dropped'])) {
            return redirect()->to('/presenter/events/detail/'.$eventId)
                ->with('error', 'Data registrasi presenter tidak ditemukan / sudah dibatalkan.');
        }

        $mode = $reg['mode_kehadiran'] ?? 'offline';

        // Cek pembayaran existing
        $existingPayment = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->orderBy('tanggal_bayar','DESC')
            ->first();

        if ($existingPayment) {
            return redirect()->to('/presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('message', 'Anda sudah memiliki pembayaran untuk event ini.');
        }

        // Hitung base price pakai getPrice (wave aware)
        $basePrice = $this->getPrice($eventId, $mode, 'presenter');

        return view('role/presenter/pembayaran/instruction', [
            'title'              => 'Instruksi Pembayaran',
            'event'              => $event,
            'basePrice'          => $basePrice,
            'mode_kehadiran'     => $mode,
            'midtrans_client_key'=> $this->midtrans->getClientKey(),
            'is_production'      => $this->midtrans->isProduction(),
        ]);
    }

    /* ========================================================================
     *  VALIDASI VOUCHER
     * ===================================================================== */
    public function validateVoucher()
    {
        $userId  = $this->uid();
        $eventId = (int) ($this->request->getPost('event_id') ?? $this->request->getGet('event_id'));
        $code    = (string) ($this->request->getPost('kode_voucher') ?? $this->request->getGet('kode_voucher'));

        if (!$eventId) {
            return $this->response->setJSON([
                'ok'=>false,
                'message'=>'Event tidak valid.',
                'token'=>csrf_hash()
            ]);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON([
                'ok'=>false,
                'message'=>'Event tidak ditemukan.',
                'token'=>csrf_hash()
            ]);
        }

        // Ambil registrasi untuk tahu mode
        $reg  = $this->regModel->findUserReg($eventId, $userId);
        $mode = $reg['mode_kehadiran'] ?? 'offline';

        $basePrice = $this->getPrice($eventId, $mode, 'presenter');
        $voucher   = $this->findValidVoucher($code, $userId, $eventId);

        if (!$voucher) {
            return $this->response->setJSON([
                'ok'=>false,
                'message'=>'Voucher tidak valid/kedaluwarsa/habis kuota',
                'token'=>csrf_hash(),
            ]);
        }

        $finalPrice = $this->applyVoucherDiscount($basePrice, $voucher);

        return $this->response->setJSON([
            'ok'          => true,
            'message'     => 'Voucher berhasil diterapkan',
            'final_price' => $finalPrice,
            'voucher_id'  => (int)$voucher['id_voucher'],
            'code'        => $voucher['kode_voucher'],
            'token'       => csrf_hash(),
        ]);
    }

    /* ========================================================================
     *  CREATE (halaman pilihan metode) – pakai event_id + mode_kehadiran
     * ===================================================================== */
    public function create(int $eventId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/pembayaran')->with('error', 'Event tidak ditemukan.');

        if (!$this->ensureAccepted($userId, $eventId)) {
            return redirect()->to('/presenter/events/detail/' . $eventId)
                ->with('error', 'Pembayaran hanya setelah FULL PAPER diterima (ACC).');
        }

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg || !empty($reg['is_dropped'])) {
            return redirect()->to('/presenter/events/detail/' . $eventId)
                ->with('error','Data registrasi presenter tidak ditemukan / sudah dibatalkan.');
        }

        $mode = $reg['mode_kehadiran'] ?? 'offline';

        $existingPayment = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->orderBy('tanggal_bayar','DESC')
            ->first();

        if ($existingPayment) {
            return redirect()->to('/presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('message', 'Anda sudah memiliki pembayaran untuk event ini.');
        }

        $amount = $this->getPrice($eventId, $mode, 'presenter');
        $user   = $this->userModel->find($userId);

        return view('role/presenter/pembayaran/create', [
            'title'              => 'Pilihan Pembayaran',
            'event'              => $event,
            'amount'             => $amount,
            'mode_kehadiran'     => $mode,
            'user'               => $user,
            'midtrans_client_key'=> $this->midtrans->getClientKey(),
            'is_production'      => $this->midtrans->isProduction(),
        ]);
    }

    /* ========================================================================
     *  PROCESS PAYMENT (AJAX) – pakai getPrice + mode_kehadiran registrasi
     * ===================================================================== */
    public function processPayment()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success'=>false,'message'=>'Invalid request method']);
        }

        $userId = $this->uid();
        if (!$userId) {
            return $this->response->setJSON(['success'=>false,'message'=>'Please login']);
        }

        $eventId     = (int) $this->request->getPost('event_id');
        $voucherCode = trim((string) $this->request->getPost('voucher_code'));

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['success'=>false,'message'=>'Event not found']);
        }

        if (!$this->ensureAccepted($userId, $eventId)) {
            return $this->response->setJSON(['success'=>false,'message'=>'Full Paper belum diterima (ACC)']);
        }

        // Ambil registrasi → mode
        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg || !empty($reg['is_dropped'])) {
            return $this->response->setJSON(['success'=>false,'message'=>'Registrasi presenter tidak ditemukan / sudah dibatalkan.']);
        }

        $mode = $reg['mode_kehadiran'] ?? 'offline';

        // Cek existing payment
        $existingPayment = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->orderBy('tanggal_bayar', 'DESC')
            ->first();

        if ($existingPayment) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda sudah memiliki pembayaran untuk event ini.',
                'redirect_url' => site_url('presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
            ]);
        }

        $user       = $this->userModel->find($userId);
        $basePrice  = $this->getPrice($eventId, $mode, 'presenter');
        $finalPrice = $basePrice;
        $voucherId  = null;

        if ($voucherCode !== '') {
            $voucher = $this->findValidVoucher($voucherCode, $userId, $eventId);
            if ($voucher) {
                $finalPrice = $this->applyVoucherDiscount($basePrice, $voucher);
                $voucherId  = (int)$voucher['id_voucher'];
            }
        }

        // Amount must be > 0
        if ($finalPrice <= 0) {
            log_message('error', "[Presenter] Invalid amount for event {$eventId}, mode {$mode}: {$finalPrice}");
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Harga event tidak valid. Silakan hubungi admin.'
            ]);
        }

        try {
            $userDetails = [
                'nama_lengkap' => $user['nama_lengkap'] ?? 'User',
                'email'        => $user['email'] ?? '',
                'no_hp'        => $user['no_hp'] ?? '',
            ];
            $eventDetails = ['title' => $event['title'] ?? 'Event Registration'];

            log_message('info', "[Presenter] Creating Midtrans payment - Amount: {$finalPrice}, Mode: {$mode}, Event: {$eventId}, User: {$userId}");

            $midtransResponse = $this->midtrans->createEventPayment(
                $eventId,
                $userId,
                'presenter',
                $mode,
                $finalPrice,
                $userDetails,
                $eventDetails
            );

            if (!isset($midtransResponse['snap_token']) || !isset($midtransResponse['order_id'])) {
                throw new \Exception('Failed to create payment token');
            }

            $paymentData = [
                'id_user'             => $userId,
                'event_id'            => $eventId,
                'jumlah'              => $finalPrice,
                'participation_type'  => $mode,
                'metode'              => 'midtrans',
                'status'              => 'pending',
                'midtrans_order_id'   => $midtransResponse['order_id'],
                'midtrans_snap_token' => $midtransResponse['snap_token'],
                'id_voucher'          => $voucherId,
                'original_amount'     => $basePrice,
                'discount_amount'     => $basePrice - $finalPrice,
                'tanggal_bayar'       => date('Y-m-d H:i:s'),
            ];

            if (method_exists($this->payModel, 'createMidtransPayment')) {
                $this->payModel->createMidtransPayment($paymentData);
            } else {
                $this->payModel->insert($paymentData);
            }

            $paymentId = $this->payModel->getInsertID();

            try {
                $this->notificationService->notify(
                    $userId,
                    'payment',
                    'Pembayaran Dibuat',
                    "Pembayaran untuk event \"{$event['title']}\" telah dibuat. Silakan selesaikan pembayaran.",
                    site_url('presenter/pembayaran/detail/' . $paymentId)
                );
            } catch (\Exception $e) {
                log_message('warning', 'Failed to send notification: ' . $e->getMessage());
            }

            return $this->response->setJSON([
                'success'        => true,
                'payment_method' => 'midtrans',
                'snap_token'     => $midtransResponse['snap_token'],
                'order_id'       => $midtransResponse['order_id'],
                'payment_id'     => $paymentId,
                'redirect_url'   => site_url('presenter/pembayaran/detail/' . $paymentId)
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Presenter] Payment creation error: ' . $e->getMessage());
            return $this->response->setJSON(['success'=>false,'message'=>'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /* ========================================================================
     *  FINISH CALLBACK
     * ===================================================================== */
    public function finish()
    {
        $orderId = $this->request->getGet('order_id');
        if (!$orderId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Parameter pembayaran tidak valid');
        }

        $payment = $this->payModel->getByMidtransOrderId($orderId);
        if (!$payment) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data pembayaran tidak ditemukan');
        }

        if ((int)$payment['id_user'] !== $this->uid()) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Akses tidak diizinkan');
        }

        try {
            $statusData = $this->midtrans->getTransactionStatus($orderId);
            if (isset($statusData['transaction_status'])) {
                $transactionStatus = strtolower($statusData['transaction_status']);
                $fraudStatus       = strtolower($statusData['fraud_status'] ?? '');
                $newStatus         = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

                if ($newStatus !== $payment['status']) {
                    $updateData = [
                        'status'               => $newStatus,
                        'midtrans_raw_response'=> json_encode($statusData),
                        'keterangan'           => "Finish callback: {$transactionStatus}",
                        'midtrans_payment_type'=> $statusData['payment_type'] ?? null,
                        'updated_at'           => date('Y-m-d H:i:s'),
                    ];
                    if ($newStatus === 'verified') {
                        $updateData['verified_at']          = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                        $updateData['auto_verified']        = true;
                        $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                        if (!empty($payment['id_voucher'])) {
                            $this->voucherModel->reduceQuota($payment['id_voucher']);
                        }
                    }
                    $this->payModel->update($payment['id_pembayaran'], $updateData);
                }

                $effectiveStatus = $newStatus ?? $payment['status'];
                $message = match($effectiveStatus) {
                    'verified' => 'Pembayaran Anda telah berhasil dan diverifikasi!',
                    'pending'  => 'Pembayaran sedang diproses. Status akan diperbarui otomatis.',
                    default    => 'Pembayaran dibatalkan/gagal. Anda dapat mencoba lagi.'
                };
                $alertType = match($effectiveStatus) {
                    'verified' => 'success',
                    'pending'  => 'info',
                    default    => 'warning'
                };
            } else {
                $message   = 'Pembayaran sedang diproses. Status akan diperbarui secara otomatis.';
                $alertType = 'info';
            }
        } catch (\Exception $e) {
            log_message('error', '[Presenter] Payment finish callback error: ' . $e->getMessage());
            $message   = 'Pembayaran telah dibuat. Mohon periksa status pembayaran Anda.';
            $alertType = 'info';
        }

        return redirect()->to('/presenter/pembayaran/detail/' . $payment['id_pembayaran'])->with($alertType, $message);
    }

    /* ========================================================================
     *  DETAIL
     * ===================================================================== */
    public function detail(int $id)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $row = $this->payModel->find($id);
        if (!$row || (int)$row['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data pembayaran tidak ditemukan.');
        }

        // Sync status jika pending Midtrans
        if ($row['status'] === 'pending'
            && $row['metode'] === 'midtrans'
            && !empty($row['midtrans_order_id'])) {
            $this->syncSinglePayment($row);
            $row = $this->payModel->find($id);
        }

        $event   = $this->eventModel->select('id,title,event_date,event_time')->find((int)$row['event_id']);
        $voucher = !empty($row['id_voucher']) ? $this->voucherModel->find($row['id_voucher']) : null;

        return view('role/presenter/pembayaran/detail', [
            'title'   => 'Detail Pembayaran',
            'pay'     => $row,
            'event'   => $event,
            'voucher' => $voucher,
        ]);
    }

    private function syncSinglePayment($payment)
    {
        try {
            if (empty($payment['midtrans_order_id'])) return;

            $statusData        = $this->midtrans->getTransactionStatus($payment['midtrans_order_id']);
            $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
            $fraudStatus       = strtolower($statusData['fraud_status'] ?? '');
            $newStatus         = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

            if ($newStatus !== $payment['status']) {
                $updateData = [
                    'status'               => $newStatus,
                    'midtrans_raw_response'=> json_encode($statusData),
                    'keterangan'           => "Auto sync: {$transactionStatus}",
                    'updated_at'           => date('Y-m-d H:i:s'),
                ];
                if ($newStatus === 'verified') {
                    $updateData['verified_at']          = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    $updateData['auto_verified']        = true;
                    $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    if (!empty($payment['id_voucher'])) {
                        $this->voucherModel->reduceQuota($payment['id_voucher']);
                    }
                }
                $this->payModel->update($payment['id_pembayaran'], $updateData);
            }
        } catch (\Exception $e) {
            log_message('error', '[Presenter] Single payment sync error: ' . $e->getMessage());
        }
    }

    /* ========================================================================
     *  CHECK STATUS MANUAL
     * ===================================================================== */
    public function checkStatus($paymentId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $payment = $this->payModel->find($paymentId);
        if (!$payment || (int)$payment['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data tidak ditemukan');
        }

        if (empty($payment['midtrans_order_id'])) {
            return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)->with('warning', 'Pembayaran ini bukan dari Midtrans');
        }

        try {
            $statusData        = $this->midtrans->getTransactionStatus($payment['midtrans_order_id']);
            $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
            $fraudStatus       = strtolower($statusData['fraud_status'] ?? '');
            $newStatus         = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

            if ($newStatus !== $payment['status']) {
                $updateData = [
                    'status'               => $newStatus,
                    'midtrans_raw_response'=> json_encode($statusData),
                    'keterangan'           => "Manual check: {$transactionStatus}",
                    'updated_at'           => date('Y-m-d H:i:s'),
                ];
                if ($newStatus === 'verified') {
                    $updateData['verified_at']          = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    $updateData['auto_verified']        = true;
                    $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    if (!empty($payment['id_voucher'])) {
                        $this->voucherModel->reduceQuota($payment['id_voucher']);
                    }
                    $message   = 'Status berhasil diperbarui! Pembayaran telah terverifikasi.';
                    $alertType = 'success';
                } elseif ($newStatus === 'pending') {
                    $message   = 'Status masih pending. Pembayaran sedang diproses.';
                    $alertType = 'info';
                } else {
                    $message   = "Status diperbarui menjadi: " . ucfirst($newStatus);
                    $alertType = 'warning';
                }
                $this->payModel->update($paymentId, $updateData);
            } else {
                $message   = 'Status pembayaran sudah up-to-date: ' . ucfirst($payment['status']);
                $alertType = 'info';
            }
        } catch (\Exception $e) {
            log_message('error', '[Presenter] Manual status check error: ' . $e->getMessage());
            $message   = 'Gagal memeriksa status. Coba lagi nanti.';
            $alertType = 'error';
        }

        return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)->with($alertType, $message);
    }

    /* ========================================================================
     *  CANCEL
     * ===================================================================== */
    public function cancel(int $paymentId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $pay = $this->payModel->find($paymentId);
        if (!$pay || (int)$pay['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data tidak ditemukan.');
        }

        if ($pay['status'] !== 'pending') {
            return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)
                ->with('warning', 'Pembayaran tidak dapat dibatalkan.');
        }

        $this->payModel->update($paymentId, [
            'status'      => 'canceled',
            'keterangan'  => 'Dibatalkan oleh user',
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/presenter/pembayaran')
            ->with('message', 'Pembayaran dibatalkan.');
    }
}
