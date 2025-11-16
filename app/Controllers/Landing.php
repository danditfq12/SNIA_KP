<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\VoucherModel;

class Landing extends BaseController
{
    protected $eventModel;
    protected $voucherModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel   = new EventModel();
        $this->voucherModel = new VoucherModel();
        $this->db           = \Config\Database::connect();
    }

    public function index()
    {
        try {
            // Ambil event aktif terdekat
            $activeEvent = $this->eventModel
                ->where('is_active', true)
                ->where('registration_active', true)
                ->where('event_date >=', date('Y-m-d'))
                ->orderBy('event_date', 'ASC')
                ->first();

            // Ambil voucher aktif jika ada event
            $activeVouchers = [];
            if ($activeEvent) {
                $activeVouchers = $this->voucherModel
                    ->where('status', 'aktif')
                    ->where('masa_berlaku >=', date('Y-m-d'))
                    ->where('kuota >', 0)
                    ->orderBy('masa_berlaku', 'ASC')
                    ->findAll();

                // Hitung sisa kuota
                foreach ($activeVouchers as &$voucher) {
                    $usedCount = $this->db->table('pembayaran')
                        ->where('id_voucher', $voucher['id_voucher'])
                        ->where('status', 'verified')
                        ->countAllResults();

                    $voucher['used_count'] = $usedCount;
                    $voucher['remaining']  = max(0, (int)$voucher['kuota'] - $usedCount);
                }
            }

            // Parse boolean + format tanggal
            if ($activeEvent) {
                $activeEvent['is_active']                    = $this->parseBoolean($activeEvent['is_active']);
                $activeEvent['registration_active']          = $this->parseBoolean($activeEvent['registration_active']);
                $activeEvent['abstract_submission_active']   = $this->parseBoolean($activeEvent['abstract_submission_active']);
                $activeEvent['full_paper_submission_active'] = $this->parseBoolean($activeEvent['full_paper_submission_active'] ?? false);

                $activeEvent['event_date_formatted'] = $this->formatDateIndonesian($activeEvent['event_date']);
                $activeEvent['registration_deadline_formatted'] = !empty($activeEvent['registration_deadline'])
                    ? $this->formatDateIndonesian($activeEvent['registration_deadline'])
                    : null;
            }

            return view('landing/index', [
                'activeEvent'    => $activeEvent,
                'activeVouchers' => $activeVouchers,
                'title'          => 'SNIA - Seminar Nasional Informatika',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Landing page error: ' . $e->getMessage());
            return view('landing/index', [
                'activeEvent'    => null,
                'activeVouchers' => [],
                'title'          => 'SNIA - Seminar Nasional Informatika',
            ]);
        }
    }

    private function parseBoolean($value)
    {
        if ($value === null || $value === '') return false;
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (bool) intval($value);
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'yes', 'on', 'y', 't'], true);
        }
        return false;
    }

    private function formatDateIndonesian($date)
    {
        if (!$date) return '';
        $months = [1 => 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $ts = strtotime($date);
        return date('d', $ts) . ' ' . $months[(int)date('m', $ts)] . ' ' . date('Y', $ts);
    }
}
