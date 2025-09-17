<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PembayaranModel;
use App\Services\MidtransService;

class SyncPayments extends BaseCommand
{
    protected $group       = 'app';
    protected $name        = 'sync:payments';
    protected $description = 'Sync pending payments with Midtrans status';

    public function run(array $params)
    {
        CLI::write('Starting payment synchronization...', 'green');
        
        $paymentModel = new PembayaranModel();
        $midtrans = new MidtransService();
        
        // Get all pending Midtrans payments
        $pendingPayments = $paymentModel->getPendingMidtransPayments(100);
        
        if (empty($pendingPayments)) {
            CLI::write('No pending payments found.', 'yellow');
            return;
        }
        
        CLI::write('Found ' . count($pendingPayments) . ' pending payments to sync.', 'blue');
        
        $updated = 0;
        $errors = 0;
        
        foreach ($pendingPayments as $payment) {
            $orderId = $payment['midtrans_order_id'] ?? $payment['payment_reference'];
            
            if (!$orderId) {
                CLI::write('Payment ID ' . $payment['id_pembayaran'] . ': No order ID found', 'red');
                $errors++;
                continue;
            }
            
            try {
                // Get status from Midtrans
                $status = $midtrans->getTransactionStatus($orderId);
                
                if (!$status) {
                    CLI::write('Payment ID ' . $payment['id_pembayaran'] . ': Failed to get Midtrans status', 'red');
                    $errors++;
                    continue;
                }
                
                $transactionStatus = $status['transaction_status'] ?? '';
                $fraudStatus = $status['fraud_status'] ?? null;
                
                // Determine new status
                $newStatus = $this->determinePaymentStatus($transactionStatus, $fraudStatus);
                
                if ($newStatus !== 'pending') {
                    $updateData = [
                        'status' => $newStatus,
                        'midtrans_transaction_id' => $status['transaction_id'] ?? $orderId,
                        'midtrans_payment_type' => $status['payment_type'] ?? null,
                        'midtrans_raw_response' => json_encode($status),
                        'keterangan' => 'Auto-synced from Midtrans: ' . $transactionStatus
                    ];
                    
                    if ($newStatus === 'verified') {
                        $updateData['verified_at'] = $status['settlement_time'] ?? date('Y-m-d H:i:s');
                        $updateData['auto_verified'] = true;
                        $updateData['midtrans_settlement_time'] = $status['settlement_time'] ?? null;
                    }
                    
                    $paymentModel->update($payment['id_pembayaran'], $updateData);
                    
                    CLI::write('Payment ID ' . $payment['id_pembayaran'] . ': Updated to ' . $newStatus, 'green');
                    $updated++;
                    
                    // Update registration if verified
                    if ($newStatus === 'verified') {
                        $this->updateRegistrationStatus($payment);
                    }
                } else {
                    CLI::write('Payment ID ' . $payment['id_pembayaran'] . ': Still pending', 'yellow');
                }
                
            } catch (\Exception $e) {
                CLI::write('Payment ID ' . $payment['id_pembayaran'] . ': Error - ' . $e->getMessage(), 'red');
                $errors++;
            }
            
            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }
        
        CLI::write('Synchronization completed!', 'green');
        CLI::write("Updated: $updated payments", 'blue');
        CLI::write("Errors: $errors payments", 'red');
    }
    
    private function determinePaymentStatus(string $transactionStatus, ?string $fraudStatus): string
    {
        if ($transactionStatus === 'settlement') {
            return 'verified';
        }
        
        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'accept') {
                return 'verified';
            } elseif ($fraudStatus === 'challenge') {
                return 'pending';
            }
        }
        
        if ($transactionStatus === 'pending') {
            return 'pending';
        }
        
        if (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
            return 'canceled';
        }

        return 'pending';
    }
    
    private function updateRegistrationStatus(array $payment): void
    {
        try {
            $regModel = new \App\Models\EventRegistrationModel();
            $registration = $regModel
                ->where('id_user', $payment['id_user'])
                ->where('id_event', $payment['event_id'])
                ->first();

            if ($registration) {
                $regModel->update($registration['id'], ['status' => 'lunas']);
                CLI::write('Registration updated for user ' . $payment['id_user'], 'blue');
            }
        } catch (\Exception $e) {
            CLI::write('Failed to update registration: ' . $e->getMessage(), 'red');
        }
    }
}