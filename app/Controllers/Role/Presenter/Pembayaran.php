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
        $this->eventModel = new EventModel();
        $this->payModel = new PembayaranModel();
        $this->absModel = new AbstrakModel();
        $this->voucherModel = new VoucherModel();
        $this->userModel = new UserModel();
        $this->regModel = new EventRegistrationModel();
        $this->fpModel  = new FullPaperModel();
        $this->midtrans = new MidtransService();
        $this->notificationService = new NotificationService();
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? session('id') ?? 0);
    }

    /**
     * Get price berdasarkan mode kehadiran dari registrasi
     */
    private function getBasePrice(array $event, string $mode = 'offline'): int
    {
        $eventId = (int)($event['id'] ?? 0);
        
        // Gunakan method getEventPrice dari EventModel yang sudah support wave-based pricing
        $price = $this->eventModel->getEventPrice($eventId, 'presenter', $mode);
        
        return max(0, (int)$price);
    }

    /**
     * Get mode kehadiran dari registrasi user
     */
    private function getUserMode(int $eventId, int $userId): string
    {
        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) return 'offline'; // fallback default
        
        $mode = strtolower(trim((string)($reg['mode_kehadiran'] ?? 'offline')));
        return in_array($mode, ['online','offline'], true) ? $mode : 'offline';
    }

    private function formatRupiah(int $n): string
    {
        return 'Rp ' . number_format($n, 0, ',', '.');
    }

    /**
     * Get payment type label untuk display
     */
    private function getPaymentTypeLabel(string $paymentType): string
    {
        if (empty($paymentType) || $paymentType === 'midtrans') {
            return 'MIDTRANS';
        }

        // Bank Transfer dengan bank specific
        if (str_starts_with($paymentType, 'bank_transfer_')) {
            $bank = strtoupper(str_replace('bank_transfer_', '', $paymentType));
            return "VA {$bank}";
        }

        // Payment types lainnya
        return match($paymentType) {
            'gopay' => 'GOPAY',
            'qris' => 'QRIS',
            'shopeepay' => 'SHOPEEPAY',
            'credit_card' => 'CREDIT CARD',
            'cstore' => 'ALFAMART/INDOMARET',
            'alfamart' => 'ALFAMART',
            'indomaret' => 'INDOMARET',
            'akulaku' => 'AKULAKU',
            'mandiri_bill', 'echannel' => 'MANDIRI BILL',
            'bca_klikpay' => 'BCA KLIKPAY',
            'bca_klikbca' => 'BCA KLIKBCA',
            'cimb_clicks' => 'CIMB CLICKS',
            'danamon_online' => 'DANAMON ONLINE',
            default => strtoupper(str_replace('_', ' ', $paymentType))
        };
    }

    /**
     * Get payment type icon
     */
    private function getPaymentTypeIcon(string $paymentType): string
    {
        if (empty($paymentType)) {
            return 'bi bi-credit-card-2-front';
        }

        // GoPay
        if (str_contains($paymentType, 'gopay')) {
            return 'bi bi-wallet2';
        }

        // QRIS
        if (str_contains($paymentType, 'qris')) {
            return 'bi bi-qr-code';
        }

        // Bank Transfer / VA
        if (str_contains($paymentType, 'bank_transfer') || 
            str_contains($paymentType, 'bca') || 
            str_contains($paymentType, 'bni') || 
            str_contains($paymentType, 'bri') ||
            str_contains($paymentType, 'mandiri') ||
            str_contains($paymentType, 'permata') ||
            str_contains($paymentType, 'cimb')) {
            return 'bi bi-bank';
        }

        // Credit Card
        if (str_contains($paymentType, 'credit_card') || str_contains($paymentType, 'card')) {
            return 'bi bi-credit-card-2-front';
        }

        // E-wallet lainnya
        if (str_contains($paymentType, 'shopee') || str_contains($paymentType, 'dana') || 
            str_contains($paymentType, 'ovo') || str_contains($paymentType, 'linkaja')) {
            return 'bi bi-wallet2';
        }

        // Convenience Store
        if (str_contains($paymentType, 'cstore') || str_contains($paymentType, 'alfamart') || 
            str_contains($paymentType, 'indomaret')) {
            return 'bi bi-shop';
        }

        return 'bi bi-credit-card-2-front';
    }

    /**
     * Get payment type badge color
     */
    private function getPaymentTypeBadge(string $paymentType): string
    {
        if (empty($paymentType)) {
            return 'bg-primary';
        }

        // GoPay / E-wallet -> info (biru muda)
        if (str_contains($paymentType, 'gopay') || str_contains($paymentType, 'shopee') || 
            str_contains($paymentType, 'dana') || str_contains($paymentType, 'ovo')) {
            return 'bg-info';
        }

        // QRIS -> primary (biru)
        if (str_contains($paymentType, 'qris')) {
            return 'bg-primary';
        }

        // Bank Transfer -> success (hijau)
        if (str_contains($paymentType, 'bank_transfer') || 
            str_contains($paymentType, 'bca') || 
            str_contains($paymentType, 'bni') || 
            str_contains($paymentType, 'bri') ||
            str_contains($paymentType, 'mandiri')) {
            return 'bg-success';
        }

        // Credit Card -> warning (kuning)
        if (str_contains($paymentType, 'credit_card')) {
            return 'bg-warning';
        }

        // Convenience Store -> secondary (abu)
        if (str_contains($paymentType, 'cstore') || str_contains($paymentType, 'alfamart')) {
            return 'bg-secondary';
        }

        return 'bg-primary';
    }

    /**
     * Format payment type untuk disimpan ke database
     * Dipanggil dari notification/callback
     */
    private function formatPaymentType(string $paymentType, ?string $bank = null): string
    {
        $paymentType = strtolower(trim($paymentType));
        
        // Format: "bank_transfer" + bank => "bank_transfer_bca"
        if ($paymentType === 'bank_transfer' && $bank) {
            return 'bank_transfer_' . strtolower($bank);
        }

        // Format: "echannel" (Mandiri Bill) => "mandiri_bill"
        if ($paymentType === 'echannel') {
            return 'mandiri_bill';
        }

        // Format lainnya tetap gunakan payment_type asli
        // gopay, qris, credit_card, cstore, akulaku, dll
        return $paymentType;
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
     * Guard KERAS: pembayaran hanya diizinkan jika:
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

    public function index()
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        // semua pembayaran user
        $payments = $this->payModel
            ->select("pembayaran.*, e.title AS event_title, e.event_date, e.event_time, e.format")
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.id_pembayaran', 'DESC')
            ->findAll();

        // event map & flow
        $eventIds = array_unique(array_map('intval', array_filter(array_column($payments, 'event_id'))));
        $events = [];
        $flowStatuses = [];
        if (!empty($eventIds)) {
            $eventRows = $this->eventModel->whereIn('id', $eventIds)->findAll();
            foreach ($eventRows as $ev) {
                $events[$ev['id']] = $ev;
                $flowStatuses[$ev['id']] = $this->computeFlowStatus($ev['id'], $userId);
            }
        }

        // normalisasi untuk view
        $enhancedPayments = [];
        foreach ($payments as $p) {
            $eventId = (int)($p['event_id'] ?? 0);

            $p['event'] = $events[$eventId] ?? null;
            $p['flow']  = $flowStatuses[$eventId] ?? null;

            // Gunakan midtrans_payment_type sebagai metode
            $paymentType = strtolower(trim((string)($p['midtrans_payment_type'] ?? '')));
            
            // Jika payment_type kosong, fallback ke metode
            if (empty($paymentType)) {
                $paymentType = strtolower((string)($p['metode'] ?? ''));
            }
            
            // Format label dan icon berdasarkan payment type
            $p['method_label'] = $this->getPaymentTypeLabel($paymentType);
            $p['method_icon'] = $this->getPaymentTypeIcon($paymentType);
            $p['method_badge'] = $this->getPaymentTypeBadge($paymentType);

            $amount = (int)($p['jumlah'] ?? 0);
            $p['jumlah_formatted'] = 'Rp ' . number_format($amount, 0, ',', '.');

            $tanggal = $p['tanggal_bayar'] ?? $p['created_at'] ?? $p['updated_at'] ?? null;
            $p['tanggal_date'] = $tanggal ? date('d M Y', strtotime($tanggal)) : '-';
            $p['tanggal_time'] = $tanggal ? date('H:i', strtotime($tanggal)) : '';

            $status = strtolower((string)($p['status'] ?? ''));
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

            $enhancedPayments[] = $p;
        }

        // Tagihan yang harus dibayar (HANYA FP ACC)
        $eventsNeedingPayment = $this->getEventsNeedingPayment($userId);

        $dueTotal = 0;
        foreach ($eventsNeedingPayment as $bill) {
            $dueTotal += (int)($bill['amount'] ?? 0);
        }
        $dueStats = [
            'count' => count($eventsNeedingPayment),
            'total' => $dueTotal,
            'total_formatted' => 'Rp ' . number_format($dueTotal, 0, ',', '.'),
        ];

        return view('role/presenter/pembayaran/index', [
            'title' => 'Pembayaran',
            'eventsNeedingPayment' => $eventsNeedingPayment,
            'allPayments' => $enhancedPayments,
            'dueStats' => $dueStats,
        ]);
    }

    /**
     * Ambil event yang HARUS bayar:
     * - User terdaftar
     * - FP terbaru path != '' dan status === 'ACCEPTED'
     * - Belum ada payment pending/verified
     * - Harga berdasarkan mode kehadiran yang dipilih saat registrasi
     */
    private function getEventsNeedingPayment(int $userId): array
    {
        $regs = $this->regModel->listByUser($userId) ?? [];
        if (empty($regs)) return [];

        $rows = [];
        foreach ($regs as $r) {
            $eventId = (int)($r['id_event'] ?? 0);
            if ($eventId <= 0) continue;

            $fp = $this->fpModel->getLatestRowByUserEvent($userId, $eventId);
            if (!$fp) continue;

            $path   = trim((string)($fp['full_paper_path'] ?? ''));
            $status = strtoupper((string)($fp['full_paper_status'] ?? 'NONE'));

            // HANYA jika ACC
            if ($path === '' || $status !== FullPaperModel::STATUS_ACCEPTED) continue;

            // Skip jika sudah ada payment pending/verified
            $existing = $this->payModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->whereIn('status', ['pending', 'verified'])
                ->first();
            if ($existing) continue;

            $ev = $this->eventModel->find($eventId);
            if (!$ev) continue;

            // Get mode dari registrasi user
            $userMode = $this->getUserMode($eventId, $userId);
            
            // Get price berdasarkan mode user
            $amount = $this->getBasePrice($ev, $userMode);
            
            $eventDate = $ev['event_date'] ?? null;

            $rows[] = [
                'event_id'         => $eventId,
                'title'            => (string)($ev['title'] ?? '-'),
                'event_date'       => $eventDate,
                'event_date_fmt'   => $eventDate ? date('d M Y', strtotime($eventDate)) : '-',
                'amount'           => $amount,
                'amount_formatted' => $this->formatRupiah($amount),
                'pay_url'          => site_url('presenter/pembayaran/instruction/'.$eventId),
                'mode'             => $userMode, // tambahkan info mode
            ];
        }

        usort($rows, fn($a,$b) => strtotime($a['event_date'] ?? '2100-01-01') <=> strtotime($b['event_date'] ?? '2100-01-01'));
        return $rows;
    }

    /**
     * Flow ringkas — state "bayar" hanya muncul ketika FP == ACCEPTED.
     */
    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $reg  = $this->regModel->findUserReg($eventId, $userId);
        $ab   = $this->absModel->where('id_user', $userId)->where('event_id', $eventId)->orderBy('id_abstrak','DESC')->first();
        $pay  = $this->payModel->where('id_user',$userId)->where('event_id',$eventId)->orderBy('id_pembayaran','DESC')->first();

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
        $status = strtolower($payment['status'] ?? 'pending');
        $flowState = $flow['state'] ?? 'unknown';

        switch ($status) {
            case 'pending':
                return ['label'=>'Cek Status','url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),'class'=>'btn-warning','icon'=>'bi-clock'];
            case 'verified':
                if ($flowState === 'siap_absen') {
                    return ['label'=>'Lihat Event','url'=>site_url('presenter/events/detail/' . $payment['event_id']),'class'=>'btn-success','icon'=>'bi-calendar-check'];
                }
                return ['label'=>'Detail','url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),'class'=>'btn-outline-success','icon'=>'bi-check-circle'];
            case 'canceled':
            case 'expired':
                if ($flowState === 'bayar') {
                    return ['label'=>'Bayar Ulang','url'=>site_url('presenter/pembayaran/instruction/' . $payment['event_id']),'class'=>'btn-primary','icon'=>'bi-arrow-repeat'];
                }
                return ['label'=>'Detail','url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),'class'=>'btn-outline-danger','icon'=>'bi-x-circle'];
            default:
                return ['label'=>'Detail','url'=>site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),'class'=>'btn-outline-secondary','icon'=>'bi-eye'];
        }
    }

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
                    $statusData = $this->midtrans->getTransactionStatus($orderId);
                    $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
                    $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
                    $paymentType = strtolower($statusData['payment_type'] ?? '');
                    $bank = $statusData['bank'] ?? null;
                    
                    $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

                    if ($newStatus !== 'pending') {
                        $updateData = [
                            'status' => $newStatus,
                            'midtrans_raw_response' => json_encode($statusData),
                            'keterangan' => "Auto sync: {$transactionStatus}"
                        ];
                        
                        // SIMPAN PAYMENT TYPE
                        if ($paymentType) {
                            $updateData['midtrans_payment_type'] = $this->formatPaymentType($paymentType, $bank);
                        }
                        
                        if ($newStatus === 'verified') {
                            $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            $updateData['auto_verified'] = true;
                            $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            if (!empty($payment['id_voucher'])) $this->voucherModel->reduceQuota($payment['id_voucher']);
                        }
                        $this->payModel->update($payment['id_pembayaran'], $updateData);
                    }
                } catch (\Exception $e) {
                    log_message('error', "Failed to sync payment {$orderId}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Sync pending payments error: ' . $e->getMessage());
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

        $existingPayment = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existingPayment) {
            return redirect()->to('/presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('message', 'Anda sudah memiliki pembayaran untuk event ini.');
        }

        // Get mode dari registrasi user
        $userMode = $this->getUserMode($eventId, $userId);
        $basePrice = $this->getBasePrice($event, $userMode);

        return view('role/presenter/pembayaran/instruction', [
            'title' => 'Instruksi Pembayaran',
            'event' => $event,
            'basePrice' => $basePrice,
            'userMode' => $userMode, // kirim mode ke view
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production' => $this->midtrans->isProduction(),
        ]);
    }

    public function validateVoucher()
    {
        $userId  = $this->uid();
        $eventId = (int) ($this->request->getPost('event_id') ?? $this->request->getGet('event_id'));
        $code    = (string) ($this->request->getPost('kode_voucher') ?? $this->request->getGet('kode_voucher'));

        if (!$eventId) {
            return $this->response->setJSON(['ok'=>false,'message'=>'Event tidak valid.','token'=>csrf_hash()]);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['ok'=>false,'message'=>'Event tidak ditemukan.','token'=>csrf_hash()]);
        }

        // Get mode dari registrasi
        $userMode = $this->getUserMode($eventId, $userId);
        $basePrice = $this->getBasePrice($event, $userMode);
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

        $existingPayment = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existingPayment) {
            return redirect()->to('/presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('message', 'Anda sudah memiliki pembayaran untuk event ini.');
        }

        // Get mode dari registrasi
        $userMode = $this->getUserMode($eventId, $userId);
        $basePrice = $this->getBasePrice($event, $userMode);
        $user = $this->userModel->find($userId);

        return view('role/presenter/pembayaran/create', [
            'title'   => 'Pilihan Pembayaran',
            'event'   => $event,
            'amount'  => $basePrice,
            'userMode' => $userMode,
            'user'    => $user,
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production'       => $this->midtrans->isProduction(),
        ]);
    }

    public function processPayment()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success'=>false,'message'=>'Invalid request method']);
        }

        $userId = $this->uid();
        if (!$userId) return $this->response->setJSON(['success'=>false,'message'=>'Please login']);

        $eventId     = (int) $this->request->getPost('event_id');
        $voucherCode = trim((string) $this->request->getPost('voucher_code'));

        $event = $this->eventModel->find($eventId);
        if (!$event) return $this->response->setJSON(['success'=>false,'message'=>'Event not found']);

        if (!$this->ensureAccepted($userId, $eventId)) {
            return $this->response->setJSON(['success'=>false,'message'=>'Full Paper belum diterima (ACC)']);
        }

        $user       = $this->userModel->find($userId);
        
        // Get mode dari registrasi user
        $userMode = $this->getUserMode($eventId, $userId);
        $basePrice  = $this->getBasePrice($event, $userMode);
        $finalPrice = $basePrice;
        $voucherId  = null;

        if ($voucherCode !== '') {
            $voucher = $this->findValidVoucher($voucherCode, $userId, $eventId);
            if ($voucher) {
                $finalPrice = $this->applyVoucherDiscount($basePrice, $voucher);
                $voucherId  = (int)$voucher['id_voucher'];
            }
        }

        try {
            $userDetails = [
                'nama_lengkap' => $user['nama_lengkap'] ?? 'User',
                'email'        => $user['email'] ?? '',
                'no_hp'        => $user['no_hp'] ?? '',
            ];
            $eventDetails = ['title' => $event['title'] ?? 'Event Registration'];

            // Gunakan mode dari registrasi user
            $midtransResponse = $this->midtrans->createEventPayment(
                $eventId,
                $userId,
                'presenter',
                $userMode, // gunakan mode user
                $finalPrice,
                $userDetails,
                $eventDetails
            );

            $paymentData = [
                'id_user'             => $userId,
                'event_id'            => $eventId,
                'jumlah'              => $finalPrice,
                'participation_type'  => $userMode, // simpan mode user
                'midtrans_order_id'   => $midtransResponse['order_id'],
                'midtrans_snap_token' => $midtransResponse['snap_token'],
                'id_voucher'          => $voucherId,
                'original_amount'     => $basePrice,
                'discount_amount'     => $basePrice - $finalPrice,
            ];

            $this->payModel->createMidtransPayment($paymentData);
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
            log_message('error', 'Payment creation error: ' . $e->getMessage());
            return $this->response->setJSON(['success'=>false,'message'=>'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function finish()
    {
        $orderId = $this->request->getGet('order_id');
        if (!$orderId) return redirect()->to('/presenter/pembayaran')->with('error', 'Parameter pembayaran tidak valid');

        $payment = $this->payModel->getByMidtransOrderId($orderId);
        if (!$payment) return redirect()->to('/presenter/pembayaran')->with('error', 'Data pembayaran tidak ditemukan');
        if ((int)$payment['id_user'] !== $this->uid()) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Akses tidak diizinkan');
        }

        try {
            $statusData = $this->midtrans->getTransactionStatus($orderId);
            if (isset($statusData['transaction_status'])) {
                $transactionStatus = strtolower($statusData['transaction_status']);
                $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
                $paymentType = strtolower($statusData['payment_type'] ?? '');
                $bank = $statusData['bank'] ?? null;
                
                $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

                if ($newStatus !== $payment['status']) {
                    $updateData = [
                        'status' => $newStatus,
                        'midtrans_raw_response' => json_encode($statusData),
                        'keterangan' => "Finish callback: {$transactionStatus}"
                    ];
                    
                    // SIMPAN PAYMENT TYPE
                    if ($paymentType) {
                        $updateData['midtrans_payment_type'] = $this->formatPaymentType($paymentType, $bank);
                    }
                    
                    if ($newStatus === 'verified') {
                        $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                        $updateData['auto_verified'] = true;
                        $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                        if (!empty($payment['id_voucher'])) $this->voucherModel->reduceQuota($payment['id_voucher']);
                    }
                    $this->payModel->update($payment['id_pembayaran'], $updateData);
                }

                $message = match($newStatus ?? $payment['status']) {
                    'verified' => 'Pembayaran Anda telah berhasil dan diverifikasi!',
                    'pending'  => 'Pembayaran sedang diproses. Status akan diperbarui otomatis.',
                    default    => 'Pembayaran dibatalkan/gagal. Anda dapat mencoba lagi.'
                };
                $alertType = match($newStatus ?? $payment['status']) {
                    'verified' => 'success',
                    'pending'  => 'info',
                    default    => 'warning'
                };
            } else {
                $message = 'Pembayaran sedang diproses. Status akan diperbarui secara otomatis.';
                $alertType = 'info';
            }
        } catch (\Exception $e) {
            log_message('error', 'Payment finish callback error: ' . $e->getMessage());
            $message = 'Pembayaran telah dibuat. Mohon periksa status pembayaran Anda.';
            $alertType = 'info';
        }

        return redirect()->to('/presenter/pembayaran/detail/' . $payment['id_pembayaran'])->with($alertType, $message);
    }

    public function detail(int $id)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $row = $this->payModel->find($id);
        if (!$row || (int)$row['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data pembayaran tidak ditemukan.');
        }

        // Sync status jika pending Midtrans
        if ($row['status'] === 'pending' && $row['metode'] === 'midtrans' && !empty($row['midtrans_order_id'])) {
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

            $statusData = $this->midtrans->getTransactionStatus($payment['midtrans_order_id']);
            $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
            $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
            $paymentType = strtolower($statusData['payment_type'] ?? '');
            $bank = $statusData['bank'] ?? null;
            
            $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

            if ($newStatus !== $payment['status']) {
                $updateData = [
                    'status' => $newStatus,
                    'midtrans_raw_response' => json_encode($statusData),
                    'keterangan' => "Auto sync: {$transactionStatus}"
                ];
                
                // SIMPAN PAYMENT TYPE
                if ($paymentType) {
                    $updateData['midtrans_payment_type'] = $this->formatPaymentType($paymentType, $bank);
                }
                
                if ($newStatus === 'verified') {
                    $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    $updateData['auto_verified'] = true;
                    $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    if (!empty($payment['id_voucher'])) $this->voucherModel->reduceQuota($payment['id_voucher']);
                }
                $this->payModel->update($payment['id_pembayaran'], $updateData);
            }
        } catch (\Exception $e) {
            log_message('error', 'Single payment sync error: ' . $e->getMessage());
        }
    }

    /**
     * FIXED: Check Status - dengan error handling yang lebih baik
     */
    public function checkStatus($paymentId)
    {
        $userId = $this->uid();
        if (!$userId) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Validasi payment ID
        if (!is_numeric($paymentId) || $paymentId <= 0) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'ID pembayaran tidak valid');
        }

        $payment = $this->payModel->find($paymentId);
        
        // Validasi payment exists
        if (!$payment) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data pembayaran tidak ditemukan');
        }

        // Validasi ownership
        if ((int)$payment['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Anda tidak memiliki akses ke pembayaran ini');
        }

        // Validasi metode pembayaran
        if (($payment['metode'] ?? '') !== 'midtrans') {
            return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)
                ->with('warning', 'Cek status hanya tersedia untuk pembayaran melalui Midtrans');
        }

        // Validasi order ID
        if (empty($payment['midtrans_order_id'])) {
            return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)
                ->with('warning', 'Order ID Midtrans tidak ditemukan');
        }

        try {
            $statusData = $this->midtrans->getTransactionStatus($payment['midtrans_order_id']);
            
            if (!$statusData || !isset($statusData['transaction_status'])) {
                throw new \Exception('Response dari Midtrans tidak valid');
            }

            $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
            $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
            $paymentType = strtolower($statusData['payment_type'] ?? '');
            $bank = $statusData['bank'] ?? null;
            
            $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

            if ($newStatus !== $payment['status']) {
                $updateData = [
                    'status' => $newStatus,
                    'midtrans_raw_response' => json_encode($statusData),
                    'keterangan' => "Manual check: {$transactionStatus}",
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // SIMPAN PAYMENT TYPE
                if ($paymentType) {
                    $updateData['midtrans_payment_type'] = $this->formatPaymentType($paymentType, $bank);
                }
                
                if ($newStatus === 'verified') {
                    $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    $updateData['auto_verified'] = true;
                    $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    
                    // Reduce voucher quota jika ada
                    if (!empty($payment['id_voucher'])) {
                        try {
                            $this->voucherModel->reduceQuota($payment['id_voucher']);
                        } catch (\Exception $e) {
                            log_message('warning', 'Failed to reduce voucher quota: ' . $e->getMessage());
                        }
                    }
                    
                    $message = '✅ Status berhasil diperbarui! Pembayaran telah terverifikasi.';
                    $alertType = 'success';
                } elseif ($newStatus === 'pending') {
                    $message = '⏳ Status masih pending. Pembayaran sedang diproses.';
                    $alertType = 'info';
                } elseif ($newStatus === 'canceled') {
                    $message = '❌ Pembayaran dibatalkan. Silakan lakukan pembayaran ulang jika diperlukan.';
                    $alertType = 'warning';
                } elseif ($newStatus === 'expired') {
                    $message = '⌛ Pembayaran telah kedaluwarsa. Silakan lakukan pembayaran ulang.';
                    $alertType = 'warning';
                } else {
                    $message = "📊 Status diperbarui menjadi: " . ucfirst($newStatus);
                    $alertType = 'info';
                }
                
                $this->payModel->update($paymentId, $updateData);
                
                // Log activity
                log_message('info', "Manual status check - Payment ID: {$paymentId}, Old Status: {$payment['status']}, New Status: {$newStatus}");
            } else {
                $message = '✔️ Status pembayaran sudah up-to-date: ' . ucfirst($payment['status']);
                $alertType = 'info';
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Manual status check error - Payment ID: ' . $paymentId . ', Error: ' . $e->getMessage());
            
            $message = '⚠️ Gagal memeriksa status pembayaran. Silakan coba lagi nanti atau hubungi admin jika masalah berlanjut.';
            $alertType = 'error';
        }

        return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)->with($alertType, $message);
    }

    public function cancel(int $paymentId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $pay = $this->payModel->find($paymentId);
        if (!$pay || (int)$pay['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data tidak ditemukan.');
        }
        if ($pay['status'] !== 'pending') {
            return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)->with('warning', 'Pembayaran tidak dapat dibatalkan.');
        }

        $this->payModel->update($paymentId, ['status' => 'canceled', 'keterangan' => 'Dibatalkan oleh user']);
        return redirect()->to('/presenter/pembayaran')->with('message', 'Pembayaran dibatalkan.');
    }
}