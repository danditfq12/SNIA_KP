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
// Enhanced QR System Routes (URUTAN PENTING)
// ---------------------------------------------------
$routes->group('qr', static function ($routes) {
    // /qr → halaman scanner
    $routes->get('/', 'QRAttendance::showScannerInterface');

    // scanner UI
    $routes->get('scanner', 'QRAttendance::showScannerInterface');
    $routes->get('mobile',  'QRAttendance::showScannerInterface');

    // submit hasil scan / token
    $routes->post('process', 'QRAttendance::process');

    // generate QR untuk admin
    $routes->post('generate/(:num)', 'QRAttendance::generateEventQRCodes/$1', ['filter' => 'role:admin']);

    // debug only
    if (ENVIRONMENT === 'development') {
        $routes->get('debug/(:segment)', 'QRAttendance::debugQR/$1');
        $routes->get('test/(:num)',      'QRAttendance::generateTestQR/$1');
    }

    // GENERIC token scan (HARUS PALING AKHIR)
    $routes->get('(:segment)', 'QRAttendance::scan/$1');
});

// ---------------------------------------------------
// Auth (tanpa filter login)
// ---------------------------------------------------
$routes->group('auth', ['namespace' => 'App\Controllers\Auth'], static function ($routes) {
    $routes->get('login',  'Login::index');
    $routes->post('login', 'Login::login');
    $routes->get('logout', 'Logout::index');

    $routes->get('register', 'Register::index');
    $routes->post('register','Register::store');

    $routes->get('verify',  'Verify::index');
    $routes->post('verify', 'Verify::check');
    $routes->get('resend',  'Verify::resend');
});

// ---------------------------------------------------
// Notifikasi (butuh login)
// ---------------------------------------------------
$routes->group('notif', ['filter' => 'auth'], static function ($routes) {
    $routes->get('recent',       'Notif::recent');
    $routes->get('count',        'Notif::count');
    // (opsional, kalau ada): $routes->get('list', 'Notif::list');
    $routes->post('read/(:num)', 'Notif::markRead/$1');
    $routes->match(['get','post'], 'read-all', 'Notif::readAll');
});

// ---------------------------------------------------
// Dashboard redirect (wajib login)
// ---------------------------------------------------
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

// ---------------------------------------------------
// Debug Routes (Development Only)
// ---------------------------------------------------
if (ENVIRONMENT === 'development') {
    $routes->group('debug', static function ($routes) {
        $routes->get('system',          'DebugHelper::systemCheck');
        $routes->get('qr/(:segment)',   'DebugHelper::qrDebug/$1');
        $routes->get('qr',              'DebugHelper::qrDebug');
        $routes->get('generate/(:num)', 'DebugHelper::generateTestQR/$1');
        $routes->get('errors',          'DebugHelper::errorLog');
        $routes->get('db',              'DebugHelper::dbTest');

        // Event debugging
        $routes->get('event/status/(:num)', 'Role\Admin\Event::refreshEventStatus/$1', ['filter' => 'role:admin']);
    });
}

// ---------------------------------------------------
// Admin Routes
// ---------------------------------------------------
$routes->group('admin', [
    'filter'    => 'role:admin',
    'namespace' => 'App\Controllers\Role\Admin',
], static function ($routes) {

    $routes->get('dashboard', 'Dashboard::index');

    // Users
    $routes->get ('users',               'User::index');
    $routes->get ('users/edit/(:num)',   'User::edit/$1');      // return JSON untuk modal edit
    $routes->post('users/update/(:num)', 'User::update/$1');    // submit update dari modal
    $routes->get ('users/delete/(:num)', 'User::delete/$1');    // pakai GET -> sesuai window.location.href

    // Abstrak
    $routes->get ('abstrak',                       'Abstrak::index');
    $routes->get ('abstrak/detail/(:num)',         'Abstrak::detail/$1');
    $routes->post('abstrak/assign/(:num)',         'Abstrak::assign/$1');
    $routes->post('abstrak/update-status',         'Abstrak::updateStatus');
    $routes->post('abstrak/bulk-update-status',    'Abstrak::bulkUpdateStatus');

    // PERUBAHAN: izinkan GET/POST untuk delete karena view memanggil via window.location.href (GET)
    $routes->match(['get','post'], 'abstrak/delete/(:num)', 'Abstrak::delete/$1');

    $routes->get ('abstrak/download/(:num)',       'Abstrak::downloadFile/$1');
    $routes->get ('abstrak/export',                'Abstrak::export');
    $routes->get ('abstrak/statistics',            'Abstrak::statistics');

    // PERUBAHAN: alias sesuai AJAX di view: /admin/reviewer/by-category/{id}
    $routes->get ('reviewer/by-category/(:num)',   'Abstrak::getReviewersByCategory/$1');

    // Tetap sediakan route lama untuk kompatibilitas (kalau ada referensi lama)
    $routes->get ('abstrak/reviewers-by-category/(:num)', 'Abstrak::getReviewersByCategory/$1');

    // Reviewer
    $routes->get ('reviewer',                 'Reviewer::index');
    $routes->post('reviewer/store',           'Reviewer::store');
    $routes->get ('reviewer/detail/(:num)',   'Reviewer::detail/$1');
    $routes->get ('reviewer/toggleStatus/(:num)', 'Reviewer::toggleStatus/$1');
    $routes->post('reviewer/assignCategory',  'Reviewer::assignCategory');
    $routes->get ('reviewer/removeCategory/(:num)', 'Reviewer::removeCategory/$1');
    $routes->get ('reviewer/delete/(:num)',   'Reviewer::delete/$1');
    $routes->get ('reviewer/export',          'Reviewer::export');
    $routes->get ('reviewer/statistics',      'Reviewer::getStatistics');

    // Event (sensitif → POST untuk ubah)
    $routes->get ('event',                  'Event::index');
    $routes->post('event/store',            'Event::store');
    $routes->get ('event/edit/(:num)',      'Event::edit/$1');
    $routes->post('event/update/(:num)',    'Event::update/$1');
    $routes->post('event/delete/(:num)',    'Event::delete/$1');
    $routes->get ('event/detail/(:num)',    'Event::detail/$1');
    $routes->post('event/toggle-registration/(:num)',      'Event::toggleRegistration/$1');
    $routes->post('event/toggle-abstract-submission/(:num)','Event::toggleAbstractSubmission/$1');
    $routes->post('event/toggle-status/(:num)',           'Event::toggleStatus/$1');
    $routes->get ('event/export',           'Event::export');
    $routes->get ('event/statistics',       'Event::statistics');

    // Pembayaran
    $routes->get ('pembayaran',                       'Pembayaran::index');
    $routes->post('pembayaran/verifikasi/(:num)',     'Pembayaran::verifikasi/$1');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->get ('pembayaran/view-bukti/(:num)',     'Pembayaran::viewBukti/$1');
    $routes->post('pembayaran/bulk-verifikasi',       'Pembayaran::bulkVerifikasi');
    $routes->get ('pembayaran/export',                'Pembayaran::export');
    $routes->get ('pembayaran/statistik',             'Pembayaran::statistik');
    $routes->post('pembayaran/delete/(:num)',         'Pembayaran::delete/$1'); // (jangan duplikasi)

    // Absensi (admin)
    $routes->get ('absensi',                    'Absensi::index');
    $routes->post('absensi/generateMultipleQRCodes', 'Absensi::generateMultipleQRCodes');
    $routes->get ('absensi/getEventStatus',     'Absensi::getEventStatus');
    $routes->post('absensi/markAttendance',     'Absensi::markAttendance');
    $routes->post('absensi/removeAttendance',   'Absensi::removeAttendance');
    $routes->post('absensi/bulkMarkAttendance', 'Absensi::bulkMarkAttendance');
    $routes->get ('absensi/getEligibleUsers',   'Absensi::getEligibleUsers');
    $routes->get ('absensi/export',             'Absensi::export');
    $routes->get ('absensi/liveStats',          'Absensi::liveStats');

    // Dokumen
    $routes->get ('dokumen',                            'Dokumen::index');
    $routes->post('dokumen/uploadLoa/(:num)',           'Dokumen::uploadLoa/$1');
    $routes->post('dokumen/uploadSertifikat/(:num)',    'Dokumen::uploadSertifikat/$1');
    $routes->get ('dokumen/download/(:num)',            'Dokumen::download/$1');
    $routes->get ('dokumen/preview/(:num)',             'Dokumen::preview/$1');
    $routes->post('dokumen/delete/(:num)',              'Dokumen::delete/$1');
    $routes->post('dokumen/generateBulkLOA',            'Dokumen::generateBulkLOA');
    $routes->post('dokumen/generateBulkSertifikat',     'Dokumen::generateBulkSertifikat');
    $routes->get ('dokumen/getVerifiedPresenters/(:num)','Dokumen::getVerifiedPresenters/$1');
    $routes->get ('dokumen/getAttendees/(:num)',         'Dokumen::getAttendees/$1');

    // Voucher
    $routes->get ('voucher',                   'Voucher::index');
    $routes->post('voucher/store',             'Voucher::store');
    $routes->get ('voucher/edit/(:num)',       'Voucher::edit/$1');
    $routes->post('voucher/update/(:num)',     'Voucher::update/$1');
    $routes->get ('voucher/delete/(:num)',     'Voucher::delete/$1');
    $routes->get ('voucher/force-delete/(:num)','Voucher::forceDelete/$1');
    $routes->get ('voucher/toggle-status/(:num)','Voucher::toggleStatus/$1');
    $routes->get ('voucher/detail/(:num)',     'Voucher::detail/$1');
    $routes->post('voucher/validate',          'Voucher::validateVoucher');
    $routes->get ('voucher/export',            'Voucher::export');

    // Laporan
    $routes->get('laporan',         'Laporan::index');
    $routes->get('laporan/export',  'Laporan::export');
    $routes->get('laporan/chart-data', 'Laporan::getChartData');
});

// ---------------------------------------------------
// Presenter Routes
// ---------------------------------------------------
$routes->group('presenter', [
    'filter'    => 'role:presenter',
    'namespace' => 'App\Controllers\Role\Presenter',
], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Event
    $routes->get ('events',                      'Event::index');
    $routes->get ('events/detail/(:num)',        'Event::detail/$1');
    $routes->get ('events/register/(:num)',      'Event::showRegistrationForm/$1');
    $routes->post('events/register/(:num)',      'Event::register/$1');
    $routes->post('events/calculate-price',      'Event::calculatePrice');

    // Abstrak
    $routes->get ('abstrak',                     'Abstrak::index');
    $routes->post('abstrak/upload',              'Abstrak::upload');
    $routes->get ('abstrak/status',              'Abstrak::status');
    $routes->get ('abstrak/detail/(:num)',       'Abstrak::detail/$1');
    $routes->get ('abstrak/download/(:segment)', 'Abstrak::download/$1');

    // Pembayaran
    $routes->get ('pembayaran',                       'Pembayaran::index');
    $routes->get ('pembayaran/create/(:num)',         'Pembayaran::create/$1');
    $routes->post('pembayaran/store',                 'Pembayaran::store');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->post('pembayaran/reupload/(:num)',       'Pembayaran::reupload/$1'); // tambahan dari HEAD
    $routes->get ('pembayaran/cancel/(:num)',         'Pembayaran::cancel/$1');   // kompatibilitas lama
    $routes->post('pembayaran/cancel/(:num)',         'Pembayaran::cancel/$1');   // disarankan: POST
    $routes->post('pembayaran/validate-voucher',      'Pembayaran::validateVoucher');

    // Absensi (unlock setelah pembayaran terverifikasi)
    $routes->get ('absensi',     'Absensi::index');
    $routes->post('absensi/scan','Absensi::scan');

    // Dokumen
    $routes->get ('dokumen/loa',                         'Dokumen::loa');
    $routes->get ('dokumen/loa/download/(:segment)',     'Dokumen::downloadLoa/$1');
    $routes->get ('dokumen/sertifikat',                  'Dokumen::sertifikat');
    $routes->get ('dokumen/sertifikat/download/(:segment)','Dokumen::downloadSertifikat/$1');
});

// ---------------------------------------------------
// Audience Routes
// ---------------------------------------------------
$routes->group('audience', [
    'filter'    => 'role:audience',
    'namespace' => 'App\Controllers\Role\Audience',
], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Event
    $routes->get ('events',                      'Event::index');
    $routes->get ('events/detail/(:num)',        'Event::detail/$1');
    $routes->get ('events/register/(:num)',      'Event::showRegistrationForm/$1');
    $routes->post('events/register/(:num)',      'Event::register/$1');

    // Pembayaran
    $routes->get ('pembayaran',                       'Pembayaran::index');
    $routes->get ('pembayaran/instruction/(:num)',    'Pembayaran::instruction/$1'); // penting untuk flow baru
    $routes->get ('pembayaran/create/(:num)',         'Pembayaran::create/$1');
    $routes->post('pembayaran/store',                 'Pembayaran::store');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->post('pembayaran/reupload/(:num)',       'Pembayaran::reupload/$1');
    $routes->get ('pembayaran/cancel/(:num)',         'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher',      'Pembayaran::validateVoucher');

    // Absensi
    $routes->get ('absensi',              'Absensi::index');   // list event + riwayat
    $routes->get ('absensi/event/(:num)', 'Absensi::show/$1'); // detail (scan / token)
    $routes->get ('absensi/token',        'Absensi::token');   // optional GET form
    $routes->post('absensi/scan',         'Absensi::scan');    // submit token

    // Dokumen
    $routes->get ('dokumen/sertifikat',                    'Dokumen::sertifikat');
    $routes->get ('dokumen/sertifikat/download/(:segment)','Dokumen::downloadSertifikat/$1');
});

// ---------------------------------------------------
// Reviewer Routes
// ---------------------------------------------------
$routes->group('reviewer', [
    'filter'    => 'role:reviewer',
    'namespace' => 'App\Controllers\Role\Reviewer',
], static function ($routes) {
    $routes->get('dashboard',       'Dashboard::index');
    $routes->get('abstrak',         'Abstrak::index');
    $routes->get('abstrak/(:num)',  'Abstrak::detail/$1');
    $routes->post('review/(:num)',  'Review::store/$1');
    $routes->get('riwayat',         'Riwayat::index');
});

// ---------------------------------------------------
// Public API
// ---------------------------------------------------
$routes->group('api/v1', static function ($routes) {
    $routes->get ('events/active',           'Api\Event::getActiveEvents');
    $routes->get ('events/(:num)/pricing',   'Api\Event::getPricing/$1');
    $routes->get ('events/(:num)/details',   'Api\Event::getEventDetails/$1');
    $routes->post('events/calculate-price',  'Api\Event::calculatePrice');
    $routes->post('vouchers/validate',       'Api\Voucher::validateVoucher');

    // QR
    $routes->get ('qr/validate/(:segment)',  'Api\QR::validateQRCode/$1');
    $routes->post('qr/scan',                 'Api\QR::processScan');
    $routes->get ('events/(:num)/qr-codes',  'Api\Event::getQRCodes/$1', ['filter' => 'role:admin']);
});

// ---------------------------------------------------
// User Profile (semua role, login wajib)
// ---------------------------------------------------
$routes->group('profile', ['filter' => 'auth'], static function ($routes) {
    $routes->get ('/',               'Profile::index');
    $routes->post('update',          'Profile::update');
    $routes->post('change-password', 'Profile::changePassword');
    $routes->post('upload-photo',    'Profile::uploadPhoto');
});

// ---------------------------------------------------
// Middleware
// ---------------------------------------------------
$routes->group('middleware', ['filter' => 'auth'], static function ($routes) {
    $routes->get('check-payment-status', 'Middleware\PaymentCheck::checkStatus');
    $routes->get('unlock-features',      'Middleware\FeatureUnlock::process');
});

// ---------------------------------------------------
// Mobile API (opsional)
// ---------------------------------------------------
$routes->group('mobile/api/v1', ['namespace' => 'App\Controllers\Mobile'], static function ($routes) {
    $routes->post('auth/login',  'Auth::login');
    $routes->post('auth/logout', 'Auth::logout', ['filter' => 'auth']);
    $routes->post('auth/refresh','Auth::refreshToken');

    $routes->post('qr/scan',                 'QR::scanQRCode', ['filter' => 'auth']);
    $routes->get ('qr/validate/(:segment)',  'QR::validateQRCode/$1');

    $routes->get ('events',                  'Event::getActiveEvents', ['filter' => 'auth']);
    $routes->get ('events/(:num)',           'Event::getEventDetail/$1', ['filter' => 'auth']);

    $routes->get ('attendance/history',      'Attendance::getHistory', ['filter' => 'auth']);
    $routes->get ('attendance/event/(:num)', 'Attendance::getEventAttendance/$1', ['filter' => 'auth']);
});

// ---------------------------------------------------
// Webhooks
// ---------------------------------------------------
$routes->group('webhook', ['namespace' => 'App\Controllers\Webhook'], static function ($routes) {
    $routes->post('midtrans',    'Midtrans::handle');
    $routes->post('xendit',      'Xendit::handle');
    $routes->post('gopay',       'Gopay::handle');
    $routes->post('qr-analytics','QRAnalytics::track');
});

// ---------------------------------------------------
// Errors
// ---------------------------------------------------
$routes->set404Override(static function () {
    return view('errors/404');
});

// ---------------------------------------------------
// Maintenance (aktifkan bila perlu)
// ---------------------------------------------------
// $routes->add('.*', 'Maintenance::index');
