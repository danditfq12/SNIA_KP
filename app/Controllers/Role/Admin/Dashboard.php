<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        return view('role/admin/dashboard');
    }
}
