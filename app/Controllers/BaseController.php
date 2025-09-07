<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// Library NotificationService
use App\Services\NotificationService;

abstract class BaseController extends Controller
{
    /** @var CLIRequest|IncomingRequest */
    protected $request;

    /** @var list<string> */
    protected $helpers = [];

    /** Data global untuk view */
    protected $data = [];

    /** @var \CodeIgniter\Session\Session */
    protected $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Session
        $this->session = service('session');

        // Default
        $this->data['notifs'] = [];

        // Load notifikasi global (hanya kalau user login)
        if ($this->session->get('id_user')) {
            try {
                $notifService = new NotificationService();
                $this->data['notifs'] = $notifService->getForCurrentUser();
            } catch (\Throwable $e) {
                log_message('error', 'Load notif error: ' . $e->getMessage());
                $this->data['notifs'] = [];
            }
        }

        // 👉 Inject supaya variabel "notifs" tersedia di SEMUA view (termasuk header)
        service('renderer')->setVar('notifs', $this->data['notifs']);
    }
}
