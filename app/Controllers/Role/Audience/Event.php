<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\UserModel;
use App\Models\PembayaranModel;
use App\Models\FasilitasBenefitModel;
use App\Services\NotificationService;

class Event extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * LIST EVENT - Dengan status registrasi & pembayaran
     */
    public function index()
    {
        $qRaw   = (string)$this->request->getGet('q');
        $format = (string)$this->request->getGet('format');

        $q = trim($qRaw);
        $fmt = trim($format);
        $isSearching = ($q !== '') || ($fmt !== '');

        $eventM  = new EventModel();
        $builder = $eventM->where('is_active', true);

        if (in_array($fmt, ['online','offline','both'], true)) {
            $builder->where('format', $fmt);
        }
        if ($q !== '') {
            $builder->groupStart()
                        ->like('title', $q)
                        ->orLike('location', $q)
                    ->groupEnd();
        }

        $events = $builder
            ->orderBy('event_date', 'ASC')
            ->orderBy('event_time', 'ASC')
            ->findAll();

        // Cek gelombang aktif per event
        $now = time();
        foreach ($events as &$e) {
            $e['reg_open'] = $eventM->isRegistrationOpen($e['id']);
            
            $currentWave = $eventM->getCurrentWave($e['id']);
            if ($currentWave) {
                $e['active_wave'] = $currentWave;
                $e['audience_fee_online'] = (float)($currentWave['audience_fee_online'] ?? 0);
                $e['audience_fee_offline'] = (float)($currentWave['audience_fee_offline'] ?? 0);
            } else {
                $e['active_wave'] = null;
                $e['audience_fee_online'] = 0;
                $e['audience_fee_offline'] = 0;
            }
            
            $e['has_upcoming_wave'] = false;
            $waves = $eventM->getWaves($e['id']);
            if (!empty($waves) && is_array($waves)) {
                foreach ($waves as $wave) {
                    $start = strtotime($wave['registration_start'] ?? '');
                    if ($start && $now < $start) {
                        $e['has_upcoming_wave'] = true;
                        $e['next_wave_start'] = $wave['registration_start'];
                        break;
                    }
                }
            }
        }
        unset($e);

        // Status registrasi & pembayaran user
        $userId = (int)(session()->get('id_user') ?? 0);
        $myRegs = [];
        if ($userId) {
            $ids = array_column($events, 'id');
            if (!empty($ids)) {
                $regM = new EventRegistrationModel();
                $regs = $regM->select('id, id_event, status')
                             ->where('id_user', $userId)
                             ->whereIn('id_event', $ids)
                             ->findAll();

                $payRows = $this->db->table('pembayaran')
                    ->select('id_pembayaran, event_id, status, tanggal_bayar')
                    ->where('id_user', $userId)
                    ->whereIn('event_id', $ids)
                    ->orderBy('tanggal_bayar', 'DESC')
                    ->get()->getResultArray();

                $latestPay = [];
                foreach ($payRows as $p) {
                    $eid = (int)$p['event_id'];
                    if (!isset($latestPay[$eid])) {
                        $latestPay[$eid] = [
                            'payment_id'     => (int)$p['id_pembayaran'],
                            'payment_status' => $p['status'],
                        ];
                    }
                }

                foreach ($regs as $r) {
                    $eid = (int)$r['id_event'];
                    $myRegs[$eid] = [
                        'reg_id'         => (int)$r['id'],
                        'status'         => $r['status'],
                        'payment_id'     => $latestPay[$eid]['payment_id']     ?? null,
                        'payment_status' => $latestPay[$eid]['payment_status'] ?? null,
                    ];
                }
            }
        }

        // ========== LOGIKA BARU: Pisah kategori event ==========
        $openEvents     = [];
        $pendingEvents  = [];
        $closedEvents   = [];
        $finishedEvents = [];
        
        foreach ($events as $ev) {
            $eventEndDate = $ev['event_end_date'] ?? $ev['event_date'];
            $eventEndTime = $ev['event_end_time'] ?? '23:59:59';
            $eventEnd = strtotime($eventEndDate . ' ' . $eventEndTime);
            
            $regInfo = $myRegs[$ev['id']] ?? null;
            $regStatus = null;
            $paymentStatus = null;
            $isRegistered = false;
            $isPaidOrVerified = false;
            
            if ($regInfo && is_array($regInfo)) {
                $regStatus = $regInfo['status'] ?? null;
                $paymentStatus = $regInfo['payment_status'] ?? null;
                
                $isRegistered = !in_array($regStatus, ['batal', 'ditolak'], true);
                
                $isPaidOrVerified = in_array($paymentStatus, ['verified', 'confirmed'], true) || 
                                   $regStatus === 'lunas';
            }
            
            if ($eventEnd && $now > $eventEnd) {
                $finishedEvents[] = $ev;
            }
            elseif ($isRegistered && $isPaidOrVerified) {
                $closedEvents[] = $ev;
            }
            elseif (!empty($ev['reg_open'])) {
                $openEvents[] = $ev;
            }
            elseif (!empty($ev['has_upcoming_wave'])) {
                $pendingEvents[] = $ev;
            }
            else {
                $closedEvents[] = $ev;
            }
        }

        return view('role/audience/events/index', [
            'title'           => 'Event Tersedia',
            'q'               => $qRaw,
            'format'          => $fmt,
            'isSearching'     => $isSearching,
            'openEvents'      => $openEvents,
            'pendingEvents'   => $pendingEvents,
            'closedEvents'    => $closedEvents,
            'finishedEvents'  => $finishedEvents,
            'myRegs'          => $myRegs,
            'total_all'       => count($events),
            'total_open'      => count($openEvents),
            'total_pending'   => count($pendingEvents),
            'total_closed'    => count($closedEvents),
            'total_finished'  => count($finishedEvents),
        ]);
    }

    /** 
     * DETAIL EVENT
     */
    public function detail(int $id)
    {
        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }

        $idUser  = (int)(session()->get('id_user') ?? 0);
        $regM    = new EventRegistrationModel();
        
        $allRegs = $regM->where('id_event', $id)
                        ->where('id_user', $idUser)
                        ->findAll();
        
        $myReg = null;
        foreach ($allRegs as $reg) {
            if (!in_array($reg['status'] ?? '', ['batal', 'ditolak'], true)) {
                $myReg = $reg;
                break;
            }
        }
        
        $options = $eventM->getParticipationOptions($id, 'audience');
        $pricing = $eventM->getPricingMatrix($id);
        $isOpen  = $eventM->isRegistrationOpen($id);

        $currentWave = $eventM->getCurrentWave($id);
        $waveInfo = null;
        if ($currentWave) {
            $waveInfo = [
                'wave_number' => $currentWave['wave_number'] ?? null,
                'start' => $currentWave['registration_start'] ?? null,
                'deadline' => $currentWave['registration_deadline'] ?? null,
            ];
        }
        
        $allWaves = $eventM->getWaves($id);

        // ========== AMBIL FASILITAS DARI DATABASE ==========
        $fasilitasModel = new FasilitasBenefitModel();
        
        $fasilitasOnline = [];
        $fasilitasOffline = [];
        
        // Ambil fasilitas audience online
        $facilityOnline = $fasilitasModel->getFasilitasByType($id, 'audience_online');
        if ($facilityOnline && !empty($facilityOnline['fasilitas'])) {
            $fasilitasOnline = is_array($facilityOnline['fasilitas']) 
                ? $facilityOnline['fasilitas'] 
                : json_decode($facilityOnline['fasilitas'], true);
        }
        
        // Ambil fasilitas audience offline
        $facilityOffline = $fasilitasModel->getFasilitasByType($id, 'audience_offline');
        if ($facilityOffline && !empty($facilityOffline['fasilitas'])) {
            $fasilitasOffline = is_array($facilityOffline['fasilitas']) 
                ? $facilityOffline['fasilitas'] 
                : json_decode($facilityOffline['fasilitas'], true);
        }

        return view('role/audience/events/detail', [
            'event'            => $ev,
            'options'          => $options,
            'pricing'          => $pricing,
            'isOpen'           => $isOpen,
            'myReg'            => $myReg,
            'waveInfo'         => $waveInfo,
            'allWaves'         => $allWaves,
            'fasilitasOnline'  => $fasilitasOnline,
            'fasilitasOffline' => $fasilitasOffline,
        ]);
    }

    /** 
     * HALAMAN PILIH MODE - WITH FACILITIES FROM DATABASE
     */
    public function showRegistrationForm(int $id)
    {
        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }
        if (!$eventM->isRegistrationOpen($id)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Pendaftaran event telah ditutup.');
        }

        $idUser   = (int)(session()->get('id_user') ?? 0);
        $regM     = new EventRegistrationModel();
        
        $allRegs = $regM->where('id_event', $id)
                        ->where('id_user', $idUser)
                        ->findAll();
        
        $existing = null;
        foreach ($allRegs as $reg) {
            if (!in_array($reg['status'] ?? '', ['batal', 'ditolak'], true)) {
                $existing = $reg;
                break;
            }
        }
        
        if ($existing) {
            if (($existing['status'] ?? '') === 'menunggu_pembayaran') {
                return redirect()->to('/audience/pembayaran/instruction/'.$existing['id'])
                                 ->with('message','Kamu sudah terdaftar. Lanjutkan pembayaran.');
            }
            return redirect()->to('/audience/events/detail/'.$id)
                             ->with('warning','Kamu sudah terdaftar pada event ini.');
        }

        $options = $eventM->getParticipationOptions($id, 'audience');
        $pricing = $eventM->getPricingMatrix($id);

        $currentWave = $eventM->getCurrentWave($id);
        $waveInfo = null;
        if ($currentWave) {
            $waveInfo = [
                'wave_number' => $currentWave['wave'] ?? null,
                'start' => $currentWave['registration_start'] ?? null,
                'deadline' => $currentWave['registration_deadline'] ?? null,
            ];
        }

        // ========== AMBIL FASILITAS DARI DATABASE ==========
        $fasilitasModel = new FasilitasBenefitModel();
        
        $fasilitasOnline = [];
        $fasilitasOffline = [];
        
        // Ambil fasilitas audience online
        $facilityOnline = $fasilitasModel->getFasilitasByType($id, 'audience_online');
        if ($facilityOnline && !empty($facilityOnline['fasilitas'])) {
            $fasilitasOnline = is_array($facilityOnline['fasilitas']) 
                ? $facilityOnline['fasilitas'] 
                : json_decode($facilityOnline['fasilitas'], true);
        }
        
        // Ambil fasilitas audience offline
        $facilityOffline = $fasilitasModel->getFasilitasByType($id, 'audience_offline');
        if ($facilityOffline && !empty($facilityOffline['fasilitas'])) {
            $fasilitasOffline = is_array($facilityOffline['fasilitas']) 
                ? $facilityOffline['fasilitas'] 
                : json_decode($facilityOffline['fasilitas'], true);
        }

        return view('role/audience/events/register', [
            'event'            => $ev,
            'options'          => $options,
            'pricing'          => $pricing,
            'waveInfo'         => $waveInfo,
            'fasilitasOnline'  => $fasilitasOnline,
            'fasilitasOffline' => $fasilitasOffline,
        ]);
    }

    public function register(int $id)
    {
        $idUser = (int)(session()->get('id_user') ?? 0);
        if ($idUser <= 0) return redirect()->to('/auth/login')->with('error','Silakan login.');

        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }
        if (!$eventM->isRegistrationOpen($id)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Pendaftaran event telah ditutup.');
        }

        $regM = new EventRegistrationModel();
        
        $allRegs = $regM->where('id_event', $id)
                        ->where('id_user', $idUser)
                        ->findAll();
        
        $existing = null;
        foreach ($allRegs as $reg) {
            if (!in_array($reg['status'] ?? '', ['batal', 'ditolak'], true)) {
                $existing = $reg;
                break;
            }
        }
        
        if ($existing) {
            if (($existing['status'] ?? '') === 'menunggu_pembayaran') {
                return redirect()->to('/audience/pembayaran/instruction/'.$existing['id'])
                                 ->with('message','Kamu sudah terdaftar. Lanjutkan pembayaran.');
            }
            return redirect()->to('/audience/events/detail/'.$id)
                             ->with('warning','Kamu sudah terdaftar pada event ini.');
        }

        $mode  = (string)$this->request->getPost('mode_kehadiran');
        $valid = $eventM->getParticipationOptions($id, 'audience');
        if (!in_array($mode, $valid, true)) {
            return redirect()->back()->withInput()->with('error','Mode kehadiran tidak valid.');
        }
        if ($eventM->hasReachedMaxParticipants($id, $mode)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Kuota peserta telah penuh.');
        }

        $userRole = 'audience';
        $price = $eventM->getEventPrice($id, $userRole, $mode);

        if ($price <= 0) {
            return redirect()->back()->withInput()
                             ->with('error','Harga tidak tersedia untuk mode kehadiran ini. Silakan hubungi admin.');
        }

        $oldCanceledReg = $regM->where('id_event', $id)
                               ->where('id_user', $idUser)
                               ->whereIn('status', ['batal', 'ditolak'])
                               ->orderBy('id', 'DESC')
                               ->first();
        
        if ($oldCanceledReg) {
            $regM->update($oldCanceledReg['id'], [
                'status' => 'menunggu_pembayaran',
                'mode_kehadiran' => $mode,
                'jumlah_bayar' => $price,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $idReg = $oldCanceledReg['id'];
            
            $this->db->table('pembayaran')
                     ->where('event_id', $id)
                     ->where('id_user', $idUser)
                     ->where('id_registrasi', $idReg)
                     ->whereIn('status', ['canceled', 'expired', 'rejected'])
                     ->delete();
            
            log_message('info', "Reactivated registration {$idReg} for user {$idUser}");
        } else {
            $idReg = $regM->insert([
                'id_event' => $id,
                'id_user' => $idUser,
                'mode_kehadiran' => $mode,
                'status' => 'menunggu_pembayaran',
                'jumlah_bayar' => $price,
                'tanggal_daftar' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            log_message('info', "Created new registration {$idReg} for user {$idUser}");
        }

        try {
            $notif = new NotificationService();
            $notif->notify(
                $idUser, 'registration', 'Pendaftaran berhasil',
                "Kamu berhasil mendaftar event \"{$ev['title']}\". Lanjutkan pembayaran.",
                site_url('audience/pembayaran/instruction/' . $idReg)
            );
            
            $admins = (new UserModel())->select('id_user')->where('role','admin')->where('status','aktif')->findAll();
            foreach ($admins as $a) {
                $notif->notify(
                    (int)$a['id_user'], 'registration', 'Pendaftaran audience baru',
                    "Peserta baru mendaftar: {$ev['title']}.",
                    site_url('admin/event/detail/' . $id)
                );
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Notification failed: ' . $e->getMessage());
        }

        return redirect()->to('/audience/pembayaran/instruction/'.$idReg)
                         ->with('message','Pendaftaran berhasil. Silakan lakukan pembayaran.');
    }
}