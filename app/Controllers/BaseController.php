<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// tambahkan Library NotificationService
use App\Libraries\NotificationService;

abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Data global yang bisa dikirim ke semua view
     */
    protected $data = [];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Session
        $this->session = service('session');

        // Load notifikasi global (hanya kalau user login)
        if ($this->session->get('id_user')) {
            $notifService = new NotificationService();
            $this->data['notifs'] = $notifService->getForCurrentUser();
        } else {
            $this->data['notifs'] = [];
        }
    }
}
