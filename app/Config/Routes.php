<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ---------------------------------------------------
// Landing Page
// ---------------------------------------------------
$routes->get('/', 'Home::index');

// ---------------------------------------------------
// Auth (halaman publik, tanpa filter login)
// ---------------------------------------------------
$routes->group('auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->get('login', 'Login::index');
    $routes->post('login', 'Login::login');
    $routes->get('logout', 'Logout::index');

    $routes->get('register', 'Register::index');
    $routes->post('register', 'Register::store');

    $routes->get('verify', 'Verify::index');       // tampilkan form
    $routes->post('verify', 'Verify::check');      // proses cek OTP
    $routes->get('resend', 'Verify::resend');      // kirim ulang kode
});


// ---------------------------------------------------
// Dashboard redirect (wajib login)
// ---------------------------------------------------
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

// ---------------------------------------------------
// Admin Routes
// ---------------------------------------------------
$routes->group('admin', [
    'filter' => 'role:admin',
    'namespace' => 'App\Controllers\Role\Admin'
], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // User Management
    $routes->get('users', 'User::index');
    $routes->post('users/store', 'User::store');
    $routes->get('users/edit/(:num)', 'User::edit/$1');
    $routes->post('users/update/(:num)', 'User::update/$1');
    $routes->get('users/delete/(:num)', 'User::delete/$1');

    // Abstrak
    $routes->get('abstrak', 'Abstrak::index');
    $routes->post('abstrak/assign/(:num)', 'Abstrak::assign/$1');

    // Reviewer
    $routes->get('reviewer', 'Reviewer::index');
    $routes->post('reviewer/store', 'Reviewer::store');

    // Pembayaran
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->post('pembayaran/verifikasi/(:num)', 'Pembayaran::verifikasi/$1');

    // Absensi
    $routes->get('absensi', 'Absensi::index');
    $routes->get('absensi/export', 'Absensi::export');

    // Dokumen
    $routes->get('dokumen', 'Dokumen::index');
    $routes->post('dokumen/loa/(:num)', 'Dokumen::uploadLoa/$1');
    $routes->post('dokumen/sertifikat/(:num)', 'Dokumen::uploadSertifikat/$1');

    // Voucher
    $routes->get('voucher', 'Voucher::index');
    $routes->post('voucher/store', 'Voucher::store');

    // Laporan
    $routes->get('laporan', 'Laporan::index');
    $routes->get('laporan/export', 'Laporan::export');
});

// ---------------------------------------------------
// Presenter Routes
// ---------------------------------------------------
$routes->group('presenter', [
    'filter' => 'role:presenter',
    'namespace' => 'App\Controllers\Role\Presenter'
], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('abstrak', 'Abstrak::index');
    $routes->post('abstrak/upload', 'Abstrak::upload');
    $routes->get('abstrak/status', 'Abstrak::status');
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->post('pembayaran/store', 'Pembayaran::store');
    $routes->get('absensi', 'Absensi::index');
    $routes->get('dokumen/loa', 'Dokumen::loa');
});

// ---------------------------------------------------
// Audience Routes
// ---------------------------------------------------
$routes->group('audience', [
    'filter' => 'role:audience',
    'namespace' => 'App\Controllers\Role\Audience'
], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->post('pembayaran/store', 'Pembayaran::store');
    $routes->get('absensi', 'Absensi::index');
    $routes->get('dokumen/sertifikat', 'Dokumen::sertifikat');
});

// ---------------------------------------------------
// Reviewer Routes
// ---------------------------------------------------
$routes->group('reviewer', [
    'filter' => 'role:reviewer',
    'namespace' => 'App\Controllers\Role\Reviewer'
], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('abstrak', 'Abstrak::index');
    $routes->get('abstrak/(:num)', 'Abstrak::detail/$1');
    $routes->post('review/(:num)', 'Review::store/$1');
    $routes->get('riwayat', 'Review::riwayat');
});
