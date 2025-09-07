<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\EmailQueueModel;
use CodeIgniter\Email\Email;

class SendEmails extends BaseCommand
{
    protected $group       = 'Notifications';
    protected $name        = 'send:emails';
    protected $description = 'Proses email queue dan kirim email pending';

    public function run(array $params)
    {
        $queue = new EmailQueueModel();

        $emails = $queue->getPending(20); // ambil max 20 pending
        if (empty($emails)) {
            CLI::write('Tidak ada email pending.', 'yellow');
            return;
        }

        $emailService = new Email();

        foreach ($emails as $row) {
            CLI::write("Kirim ke: {$row['to_email']} | Subjek: {$row['subject']}");

            try {
                $emailService->setTo($row['to_email']);
                $emailService->setSubject($row['subject']);
                if (!empty($row['body_html'])) {
                    $emailService->setMessage($row['body_html']);
                    $emailService->setAltMessage($row['body_text'] ?? strip_tags($row['body_html']));
                } else {
                    $emailService->setMessage($row['body_text'] ?? '(kosong)');
                }

                if ($emailService->send()) {
                    $queue->update($row['id'], [
                        'status'    => 'sent',
                        'sent_at'   => date('Y-m-d H:i:s'),
                        'attempts'  => $row['attempts'] + 1,
                        'updated_at'=> date('Y-m-d H:i:s'),
                    ]);
                    CLI::write(' -> sukses', 'green');
                } else {
                    $queue->update($row['id'], [
                        'status'    => 'failed',
                        'last_error'=> $emailService->printDebugger(['headers']),
                        'attempts'  => $row['attempts'] + 1,
                        'updated_at'=> date('Y-m-d H:i:s'),
                    ]);
                    CLI::error(' -> gagal');
                }
            } catch (\Throwable $e) {
                $queue->update($row['id'], [
                    'status'    => 'failed',
                    'last_error'=> $e->getMessage(),
                    'attempts'  => $row['attempts'] + 1,
                    'updated_at'=> date('Y-m-d H:i:s'),
                ]);
                CLI::error(' -> error: ' . $e->getMessage());
            }
        }
    }
}
