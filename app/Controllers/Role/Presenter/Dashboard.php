<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        return view('role/presenter/dashboard');
    }
}
