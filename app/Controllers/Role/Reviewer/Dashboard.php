<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        return view('role/reviewer/dashboard');
    }
}
