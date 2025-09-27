<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $isLoggedIn = session()->get('isLoggedIn');
        $userRole   = session()->get('role');

        if (! $isLoggedIn) {
            return redirect()->to(site_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $allowed = (array) ($arguments ?? []);
        if (! $userRole || ! in_array($userRole, $allowed, true)) {
            return redirect()->to(site_url('dashboard'))->with('error', 'Akses ditolak.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
