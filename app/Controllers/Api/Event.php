<?php
// app/Controllers/Api/Event.php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\EventModel;

class Event extends BaseController
{
    protected $eventModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
    }

    public function getActiveEvents()
    {
        $events = $this->eventModel->getActiveEvents();
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $events
        ]);
    }

    public function getPricing($eventId)
    {
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Event not found'
            ]);
        }

        $pricingMatrix = $this->eventModel->getPricingMatrix($eventId);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $pricingMatrix
        ]);
    }

    public function getEventDetails($eventId)
    {
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Event not found'
            ]);
        }

        $stats = $this->eventModel->getEventStats($eventId);
        $pricingMatrix = $this->eventModel->getPricingMatrix($eventId);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'event' => $event,
                'stats' => $stats,
                'pricing' => $pricingMatrix,
                'registration_open' => $this->eventModel->isRegistrationOpen($eventId),
                'abstract_open' => $this->eventModel->isAbstractSubmissionOpen($eventId)
            ]
        ]);
    }

    public function calculatePrice()
    {
        $eventId = $this->request->getJSON()->event_id ?? null;
        $userRole = $this->request->getJSON()->user_role ?? 'audience';
        $participationType = $this->request->getJSON()->participation_type ?? 'offline';
        $voucherCode = $this->request->getJSON()->voucher_code ?? null;

        if (!$eventId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Event ID is required'
            ]);
        }

        $basePrice = $this->eventModel->getEventPrice($eventId, $userRole, $participationType);
        
        if ($basePrice === 0) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Event not found or invalid participation type'
            ]);
        }

        $finalPrice = $basePrice;
        $discount = 0;
        $voucherInfo = null;

        // Apply voucher if provided
        if ($voucherCode) {
            $voucherModel = new \App\Models\VoucherModel();
            $voucher = $voucherModel->where('kode_voucher', strtoupper($voucherCode))->first();
            
            if ($voucher && $voucher['status'] === 'aktif' && strtotime($voucher['masa_berlaku']) > time()) {
                $pembayaranModel = new \App\Models\PembayaranModel();
                $usedCount = $pembayaranModel->where('id_voucher', $voucher['id_voucher'])->countAllResults();
                
                if ($usedCount < $voucher['kuota']) {
                    if ($voucher['tipe'] === 'percentage') {
                        $discount = ($basePrice * $voucher['nilai'] / 100);
                    } else {
                        $discount = min($voucher['nilai'], $basePrice);
                    }
                    
                    $finalPrice = max(0, $basePrice - $discount);
                    $voucherInfo = $voucher;
                }
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'base_price' => $basePrice,
                'discount' => $discount,
                'final_price' => $finalPrice,
                'voucher_applied' => $voucherInfo !== null,
                'voucher_info' => $voucherInfo,
                'formatted' => [
                    'base_price' => 'Rp ' . number_format($basePrice, 0, ',', '.'),
                    'discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
                    'final_price' => 'Rp ' . number_format($finalPrice, 0, ',', '.')
                ]
            ]
        ]);
    }
}
