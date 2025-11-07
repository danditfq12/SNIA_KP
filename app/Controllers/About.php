<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class About extends BaseController
{
    public function index()
    {
        $title = 'Tentang SNIA';
        $team = [
            ['name' => 'Dr. Ridwan Ilyas',   'role' => 'Ketua Pelaksana',       'photo' => base_url('assets/img/team/rina.jpg')],
            ['name' => 'Adi Nugroho, M.Kom', 'role' => 'Koordinator Program',   'photo' => base_url('assets/img/team/adi.jpg')],
            ['name' => 'Salsa Fitri',        'role' => 'Publikasi & Kemitraan', 'photo' => base_url('assets/img/team/salsa.jpg')],
        ];

        return view('landing/about', compact('title', 'team'));
    }
}
