<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        return view('role/audience/dashboard');
    }
}
