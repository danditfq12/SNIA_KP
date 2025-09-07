<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ---------------------------------------------------
// Landing Page (public)
// ---------------------------------------------------
$routes->get('/', 'Home::index');

// ---------------------------------------------------
// Auth (public, no login required)
// ---------------------------------------------------
$routes->group('auth', ['namespace' => 'App\Controllers\Auth'], function ($routes) {
    $routes->get('login',    'Login::index');
    $routes->post('login',   'Login::login');
    $routes->get('logout',   'Logout::index');

    $routes->get('register', 'Register::index');
    $routes->post('register','Register::store');

    $routes->get('verify',   'Verify::index');
    $routes->post('verify',  'Verify::check');
    $routes->get('resend',   'Verify::resend');
});

// ---------------------------------------------------
// QR Attendance (public – dipakai semua role)
// ---------------------------------------------------
$routes->get ('qr',          'QRAttendance::showScannerInterface'); // /qr
$routes->get ('qr/(:any)',   'QRAttendance::scan/$1');              // /qr/{token}
$routes->post('qr/process',  'QRAttendance::process');              // POST /qr/process

// ---------------------------------------------------
// Notifikasi (butuh login)
// ---------------------------------------------------
$routes->group('notif', ['filter' => 'auth'], static function($routes) {
    $routes->get('recent',      'Notif::recent');
    $routes->get('list',        'Notif::list');
    $routes->get('count',       'Notif::count');
    $routes->post('read/(:num)','Notif::markRead/$1');
    $routes->get('read-all',    'Notif::readAll');
});

// ---------------------------------------------------
// Default dashboard redirect (must login)
// ---------------------------------------------------
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

// ---------------------------------------------------
// Admin Routes
// ---------------------------------------------------
$routes->group('admin', [
    'filter'    => 'role:admin',
    'namespace' => 'App\Controllers\Role\Admin',
], function ($routes) {

    $routes->get('dashboard', 'Dashboard::index');

    // Users
    $routes->get ('users',                 'User::index');
    $routes->post('users/store',           'User::store');
    $routes->get ('users/edit/(:num)',     'User::edit/$1');
    $routes->post('users/update/(:num)',   'User::update/$1');
    $routes->get ('users/delete/(:num)',   'User::delete/$1');
    $routes->get ('users/detail/(:num)',   'User::detail/$1');

    // Abstrak
    $routes->get ('abstrak',                         'Abstrak::index');
    $routes->get ('abstrak/detail/(:num)',           'Abstrak::detail/$1');
    $routes->post('abstrak/assign/(:num)',           'Abstrak::assign/$1');
    $routes->post('abstrak/update-status',           'Abstrak::updateStatus');
    $routes->post('abstrak/bulk-update-status',      'Abstrak::bulkUpdateStatus');
    $routes->get ('abstrak/delete/(:num)',           'Abstrak::delete/$1');
    $routes->get ('abstrak/download/(:num)',         'Abstrak::downloadFile/$1');
    $routes->get ('abstrak/export',                  'Abstrak::export');
    $routes->get ('abstrak/statistics',              'Abstrak::statistics');
    $routes->get ('abstrak/reviewers-by-category/(:num)', 'Abstrak::getReviewersByCategory/$1');

    // Reviewer
    $routes->get ('reviewer',                    'Reviewer::index');
    $routes->post('reviewer/store',              'Reviewer::store');
    $routes->post('reviewer/assignCategory',     'Reviewer::assignCategory');
    $routes->get ('reviewer/removeCategory/(:num)','Reviewer::removeCategory/$1');
    $routes->get ('reviewer/toggleStatus/(:num)','Reviewer::toggleStatus/$1');
    $routes->get ('reviewer/detail/(:num)',      'Reviewer::detail/$1');
    $routes->get ('reviewer/delete/(:num)',      'Reviewer::delete/$1');
    $routes->get ('reviewer/performance',        'Reviewer::performance');

    // Event (role-based pricing)
    $routes->get ('event',                        'Event::index');
    $routes->post('event/store',                  'Event::store');
    $routes->get ('event/edit/(:num)',            'Event::edit/$1');
    $routes->post('event/update/(:num)',          'Event::update/$1');
    $routes->get ('event/delete/(:num)',          'Event::delete/$1');
    $routes->get ('event/detail/(:num)',          'Event::detail/$1');
    $routes->get ('event/toggle-registration/(:num)', 'Event::toggleRegistration/$1');
    $routes->get ('event/toggle-abstract/(:num)',     'Event::toggleAbstractSubmission/$1');
    $routes->get ('event/toggle-status/(:num)',       'Event::toggleStatus/$1');
    $routes->get ('event/export',                 'Event::export');
    $routes->get ('event/statistics',             'Event::statistics');

    // Pembayaran (admin)
    $routes->get ('pembayaran',                       'Pembayaran::index');
    $routes->post('pembayaran/verifikasi/(:num)',     'Pembayaran::verifikasi/$1');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->post('pembayaran/bulk-verifikasi',       'Pembayaran::bulkVerifikasi');
    $routes->get ('pembayaran/export',                'Pembayaran::export');
    $routes->get ('pembayaran/statistik',             'Pembayaran::statistik');

    // Absensi (admin)
    $routes->get ('absensi',                          'Absensi::index');
    $routes->get ('absensi/export',                   'Absensi::export');
    $routes->post('absensi/generateMultipleQRCodes',  'Absensi::generateMultipleQRCodes');
    $routes->get ('absensi/getEventStatus',           'Absensi::getEventStatus');
    $routes->post('absensi/markAttendance',           'Absensi::markAttendance');
    $routes->post('absensi/removeAttendance',         'Absensi::removeAttendance');
    $routes->post('absensi/bulkMarkAttendance',       'Absensi::bulkMarkAttendance');
    $routes->get ('absensi/getEligibleUsers',         'Absensi::getEligibleUsers');
    $routes->get ('absensi/liveStats',                'Absensi::liveStats');

    // (Tidak perlu menaruh route /qr lagi di sini karena sudah global di atas)

    // Dokumen
    $routes->get ('dokumen',                        'Dokumen::index');
    $routes->post('dokumen/uploadLoa/(:num)',       'Dokumen::uploadLoa/$1');
    $routes->post('dokumen/uploadSertifikat/(:num)','Dokumen::uploadSertifikat/$1');
    $routes->get ('dokumen/download/(:num)',        'Dokumen::download/$1');
    $routes->get ('dokumen/delete/(:num)',          'Dokumen::delete/$1');
    $routes->get ('dokumen/generateBulkLOA',        'Dokumen::generateBulkLOA');
    $routes->get ('dokumen/generateBulkSertifikat', 'Dokumen::generateBulkSertifikat');

    // Voucher
    $routes->get ('voucher',                    'Voucher::index');
    $routes->post('voucher/store',              'Voucher::store');
    $routes->get ('voucher/edit/(:num)',        'Voucher::edit/$1');
    $routes->post('voucher/update/(:num)',      'Voucher::update/$1');
    $routes->get ('voucher/delete/(:num)',      'Voucher::delete/$1');
    $routes->get ('voucher/toggle-status/(:num)','Voucher::toggleStatus/$1');
    $routes->get ('voucher/detail/(:num)',      'Voucher::detail/$1');
    $routes->get ('voucher/generate-code',      'Voucher::generateCode');
    $routes->post('voucher/validate',           'Voucher::validateVoucher');
    $routes->get ('voucher/export',             'Voucher::export');

    // Laporan
    $routes->get ('laporan',            'Laporan::index');
    $routes->get ('laporan/export',     'Laporan::export');
    $routes->get ('laporan/chart-data', 'Laporan::getChartData');
});

// ---------------------------------------------------
// Presenter Routes
// ---------------------------------------------------
$routes->group('presenter', [
    'filter'    => 'role:presenter',
    'namespace' => 'App\Controllers\Role\Presenter',
], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Event
    $routes->get ('events',                      'Event::index');
    $routes->get ('events/detail/(:num)',        'Event::detail/$1');
    $routes->get ('events/register/(:num)',      'Event::showRegistrationForm/$1');
    $routes->post('events/register/(:num)',      'Event::register/$1');

    // Abstrak
    $routes->get ('abstrak',                   'Abstrak::index');
    $routes->post('abstrak/upload',            'Abstrak::upload');
    $routes->get ('abstrak/status',            'Abstrak::status');
    $routes->get ('abstrak/detail/(:num)',     'Abstrak::detail/$1');
    $routes->get ('abstrak/download/(:segment)','Abstrak::download/$1');

    // Debug
    $routes->get ('abstrak/debug/test',  'AbstractDebug::testUpload');
    $routes->post('abstrak/debug/simple','AbstractDebug::simpleUpload');

    // Pembayaran
    $routes->get ('pembayaran',                       'Pembayaran::index');
    $routes->get ('pembayaran/create/(:num)',         'Pembayaran::create/$1');
    $routes->post('pembayaran/store',                 'Pembayaran::store');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->get ('pembayaran/cancel/(:num)',         'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher',      'Pembayaran::validateVoucher');

    // Absensi
    $routes->get ('absensi',     'Absensi::index');
    $routes->post('absensi/scan','Absensi::scan');

    // Dokumen
    $routes->get ('dokumen/loa',                         'Dokumen::loa');
    $routes->get ('dokumen/loa/download/(:segment)',     'Dokumen::downloadLoa/$1');
    $routes->get ('dokumen/sertifikat',                  'Dokumen::sertifikat');
    $routes->get ('dokumen/sertifikat/download/(:segment)','Dokumen::downloadSertifikat/$1');
});

// ---------------------------------------------------
// Reviewer Routes
// ---------------------------------------------------
$routes->group('reviewer', [
    'filter'    => 'role:reviewer',
    'namespace' => 'App\Controllers\Role\Reviewer',
], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    $routes->get('abstrak',        'Abstrak::index');
    $routes->get('abstrak/(:num)', 'Abstrak::detail/$1');
    $routes->post('review/(:num)', 'Review::store/$1');
    $routes->get('riwayat',        'Riwayat::index');
});

// ---------------------------------------------------
// Audience Routes
// ---------------------------------------------------
$routes->group('audience', [
    'filter'    => 'role:audience',
    'namespace' => 'App\Controllers\Role\Audience',
], function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Event
    $routes->get ('events',                      'Event::index');
    $routes->get ('events/detail/(:num)',        'Event::detail/$1');
    $routes->get ('events/register/(:num)',      'Event::showRegistrationForm/$1');
    $routes->post('events/register/(:num)',      'Event::register/$1');

    // Pembayaran
    $routes->get ('pembayaran',                       'Pembayaran::index');
    $routes->get ('pembayaran/instruction/(:num)',    'Pembayaran::instruction/$1');
    $routes->get ('pembayaran/create/(:num)',         'Pembayaran::create/$1');
    $routes->post('pembayaran/store',                 'Pembayaran::store');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->get ('pembayaran/cancel/(:num)',         'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher',      'Pembayaran::validateVoucher');

    // Absensi (audience)
    $routes->get ('absensi',                    'Absensi::index');       // list event yang bisa diabsen + riwayat
    $routes->get ('absensi/event/(:num)',       'Absensi::show/$1');     // detail event + tombol scan/token
    $routes->get ('absensi/token',              'Absensi::token');       // form token (opsional GET)
    $routes->post('absensi/scan',               'Absensi::scan');        // submit token
    // (Scan QR pakai route global /qr)

    // Dokumen
     $routes->get ('dokumen/sertifikat',                       'Dokumen::sertifikat');
    $routes->get ('dokumen/sertifikat/download/(:segment)',   'Dokumen::downloadSertifikat/$1');
});

// ---------------------------------------------------
// Public API
// ---------------------------------------------------
$routes->group('api/v1', function ($routes) {
    $routes->get ('events/active',          'Api\Event::getActiveEvents');
    $routes->get ('events/(:num)/pricing',  'Api\Event::getPricing/$1');
    $routes->get ('events/(:num)/details',  'Api\Event::getEventDetails/$1');
    $routes->post('events/calculate-price', 'Api\Event::calculatePrice');
    $routes->post('vouchers/validate',      'Api\Voucher::validateVoucher');
});

// ---------------------------------------------------
// User Profile (all roles, must login)
// ---------------------------------------------------
$routes->group('profile', ['filter' => 'auth'], function ($routes) {
    $routes->get ('/',               'Profile::index');
    $routes->post('update',          'Profile::update');
    $routes->post('change-password', 'Profile::changePassword');
    $routes->post('upload-photo',    'Profile::uploadPhoto');
});

// ---------------------------------------------------
// Middleware
// ---------------------------------------------------
$routes->group('middleware', ['filter' => 'auth'], function ($routes) {
    $routes->get('check-payment-status', 'Middleware\PaymentCheck::checkStatus');
    $routes->get('unlock-features',      'Middleware\FeatureUnlock::process');
});
