<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ---------------------------------------------------
// Landing Page
// ---------------------------------------------------
$routes->get('/', 'Landing::index');
$routes->get('/about', 'About::index');

// ---------------------------------------------------
// Enhanced QR System Routes (URUTAN PENTING)
// ---------------------------------------------------
$routes->group('qr', static function ($routes) {
    $routes->get('/', 'QRAttendance::showScannerInterface');
    $routes->get('scanner', 'QRAttendance::showScannerInterface');
    $routes->get('mobile',  'QRAttendance::showScannerInterface');
    $routes->post('process', 'QRAttendance::process');
    $routes->post('generate/(:num)', 'QRAttendance::generateEventQRCodes/$1', ['filter' => 'role:admin']);

    if (ENVIRONMENT === 'development') {
        $routes->get('debug/(:segment)', 'QRAttendance::debugQR/$1');
        $routes->get('test/(:num)',      'QRAttendance::generateTestQR/$1');
    }

    // HARUS PALING AKHIR
    $routes->get('(:segment)', 'QRAttendance::scan/$1');
});

// ---------------------------------------------------
// Auth (tanpa filter login)
// ---------------------------------------------------
$routes->group('auth', ['namespace' => 'App\Controllers\Auth'], static function ($routes) {
    // Login/Logout
    $routes->get('login',  'Login::index');
    $routes->post('login', 'Login::login');
    $routes->post('logout', 'Logout::index');

    // Register + Email Verify
    $routes->get('register', 'Register::index');
    $routes->post('register', 'Register::store');
    $routes->get('verify',   'Verify::index');
    $routes->post('verify',  'Verify::check');
    $routes->get('resend',   'Verify::resend');

    // Reset Password (lupa → otp → password baru)
    $routes->get ('forgot-password', 'PasswordReset::requestForm');
    $routes->post('forgot-password', 'PasswordReset::requestSend');

    $routes->get ('reset/verify', 'PasswordReset::verifyForm');
    $routes->post('reset/verify', 'PasswordReset::verifyCheck');

    $routes->get ('reset/new', 'PasswordReset::newPasswordForm');
    $routes->post('reset/new', 'PasswordReset::updatePassword');
});

// ---------------------------------------------------
// CRITICAL: Webhooks MUST come before authenticated routes
// NO authentication required for webhooks!
// ---------------------------------------------------
$routes->group('webhook', ['namespace' => 'App\Controllers\Webhook'], static function ($routes) {
    // tangkap GET/POST/HEAD/OPTIONS, dengan/ tanpa trailing slash
    $routes->match(['get','post','head','options'], 'midtrans/handle', 'Midtrans::handle');
    $routes->match(['get','post','head','options'], 'midtrans/handle/', 'Midtrans::handle'); // optional slash

    // endpoint alternatif (opsional)
    $routes->match(['get','post','head','options'], 'midtrans', 'Midtrans::handle');

    // variasi path yang kadang dipakai Midtrans
    $routes->match(['post','options'], 'midtrans/handle/(:any)', 'Midtrans::handle');
    $routes->post('midtrans/handle/v1.0/debit/notify', 'Midtrans::handle');

    // testing
    $routes->get('midtrans/test', 'Midtrans::test');
    $routes->post('midtrans/check/(:segment)', 'Midtrans::checkStatus/$1');
    $routes->get('midtrans/check/(:segment)', 'Midtrans::checkStatus/$1');

    // perbaiki penulisan namespace ganda
    $routes->get('manual-check/(:any)', 'Midtrans::checkStatus/$1');
    $routes->match(['get','post','head','options'], 'midtrans/handle/', 'Midtrans::handle');

});

// Development/testing endpoints di root (tetap biarkan)
if (ENVIRONMENT === 'development') {
    $routes->get('midtrans/test',     'Midtrans::test');
    $routes->get('midtrans/logs',     'Midtrans::logs');
    $routes->get('midtrans/sync/(:segment)', 'Midtrans::syncPayment/$1');
}

// ---------------------------------------------------
// Notifikasi (butuh login)
// ---------------------------------------------------
$routes->group('notif', ['filter' => 'auth'], static function ($routes) {
    $routes->get('recent',       'Notif::recent');
    $routes->get('count',        'Notif::count');
    $routes->post('read/(:num)', 'Notif::markRead/$1');
    $routes->match(['get','post'], 'read-all', 'Notif::readAll');

    $routes->get('activities',   'Notif::activities'); // feed "Aktivitas Terbaru"
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

        // Payment debugging
        $routes->get('payment/sync/(:segment)', 'Role\Admin\Pembayaran::syncMidtransStatus/$1', ['filter' => 'role:admin']);
        $routes->get('payment/webhook-test', 'DebugHelper::testWebhook', ['filter' => 'role:admin']);
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
    $routes->get ('users/edit/(:num)',   'User::edit/$1');
    $routes->post('users/update/(:num)', 'User::update/$1');
    $routes->get ('users/delete/(:num)', 'User::delete/$1');

    // ===== ALUR BARU: SEMUA LIST VIA KELOLA PAPER =====
    $routes->get('abstrak',   'KelolaPaper::index'); // daftar → kelola-paper
    $routes->get('fullpaper', 'KelolaPaper::index');

    // ===== KELOLA PAPER (INDEX/DETAIL) =====
    $routes->group('kelola-paper', static function ($routes) {
        $routes->get('',                 'KelolaPaper::index');
        $routes->get('detail/(:num)',    'KelolaPaper::detail/$1');
        $routes->get('abstrak/(:num)',   'Abstrak::detail/$1');
        $routes->get('fullpaper/(:num)', 'FullPaper::detail/$1');
        $routes->get('event/(:num)',     'KelolaPaper::detail/$1');
    });

    // ===== ABSTRAK (DETAIL & AKSI) =====
    // (detail lama tetap ada)
    $routes->get ('abstrak/detail/(:num)',   'Abstrak::detail/$1');
    $routes->post('abstrak/assign/(:num)',   'Abstrak::assign/$1');
    $routes->post('abstrak/update-status',   'Abstrak::updateStatus');
    $routes->match(['get','post'], 'abstrak/delete/(:num)', 'Abstrak::delete/$1');
    

    // File handling (tetap di namespace abstrak)
    $routes->get ('abstrak/download/(:num)', 'Abstrak::downloadFile/$1');
    $routes->get ('abstrak/view/(:num)',     'Abstrak::view/$1');
    $routes->get ('abstrak/blob/(:num)',     'Abstrak::blob/$1');

    // Reviewer helper (AJAX)
    $routes->get ('reviewer/by-category/(:num)',          'Abstrak::getReviewersByCategory/$1');
    $routes->get ('abstrak/reviewers-by-category/(:num)', 'Abstrak::getReviewersByCategory/$1');

    // ===== FULL PAPER (DETAIL & AKSI) =====
    // (detail lama tetap ada)
    $routes->group('fullpaper', static function ($routes) {
        $routes->get ('detail/(:num)',     'FullPaper::detail/$1');
        $routes->post('set-status/(:num)', 'FullPaper::setStatus/$1');
        $routes->post('assign/(:num)',     'FullPaper::assign/$1');
        $routes->get('unassign/(:num)/(:num)','FullPaper::unassign/$1/$2');

        // File handling
        $routes->get ('view/(:num)',       'FullPaper::view/$1');      // preview inline
        $routes->get ('blob/(:num)',       'FullPaper::blob/$1');      // preview via blob
        $routes->get ('download/(:num)',   'FullPaper::download/$1');  // download attachment
        $routes->post('delete/(:num)',     'FullPaper::delete/$1');    // (opsional) hapus file
    });

    // ===== KATEGORI ABSTRAK =====
    $routes->get  ('kategori',                        'Kategori::index');             // semua (default)
    $routes->get  ('kategori/aktif',                  'Kategori::index/aktif');       // hanya aktif
    $routes->get  ('kategori/nonaktif',               'Kategori::index/nonaktif');    // hanya nonaktif

    $routes->post ('kategori/store',                  'Kategori::store');
    $routes->get  ('kategori/show/(:num)',            'Kategori::show/$1');
    $routes->post ('kategori/update/(:num)',          'Kategori::update/$1');
    $routes->match(['post','delete'], 'kategori/delete/(:num)', 'Kategori::delete/$1');

    // === tambahan untuk ON/OFF ===
    $routes->post ('kategori/toggle/(:num)',          'Kategori::toggle/$1');         // toggle satu item
    $routes->post ('kategori/activate/(:num)',        'Kategori::activate/$1');       // pakai jika mau aksi eksplisit
    $routes->post ('kategori/deactivate/(:num)',      'Kategori::deactivate/$1');     // pakai jika mau aksi eksplisit
    $routes->post ('kategori/bulk',                    'Kategori::bulk');

    // ===== REVIEWER =====
    $routes->get ('reviewer',                      'Reviewer::index');
    $routes->post('reviewer/store',                'Reviewer::store');
    $routes->get ('reviewer/detail/(:num)',        'Reviewer::detail/$1');
    $routes->get ('reviewer/toggleStatus/(:num)',  'Reviewer::toggleStatus/$1');
    $routes->post('reviewer/assignCategory',       'Reviewer::assignCategory');
    $routes->get ('reviewer/removeCategory/(:num)','Reviewer::removeCategory/$1');
    $routes->get ('reviewer/delete/(:num)',        'Reviewer::delete/$1');
    $routes->get ('reviewer/export',               'Reviewer::export');
    $routes->get ('reviewer/statistics',           'Reviewer::getStatistics');

    // ===== EVENT =====
    $routes->get('event', 'Event::index');
    $routes->get('event/detail/(:num)', 'Event::detail/$1');
    
    // Event CRUD
    $routes->post('event/create', 'Event::create');
    $routes->get('event/get/(:num)', 'Event::getEvent/$1');
    $routes->post('event/update/(:num)', 'Event::update/$1');
    $routes->post('event/toggle-status/(:num)', 'Event::toggleStatus/$1');
    $routes->post('event/delete/(:num)', 'Event::delete/$1');
    
    // Registration Waves (Early Bird System)
    $routes->get('event/get-waves/(:num)', 'Event::getWaves/$1');
    $routes->post('event/update-waves/(:num)', 'Event::updateWaves/$1');

    // ===== PEMBAYARAN =====
    $routes->get ('pembayaran',                        'Pembayaran::index');
    $routes->post('pembayaran/verifikasi/(:num)',      'Pembayaran::verifikasi/$1');
    $routes->get ('pembayaran/detail/(:num)',          'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)',  'Pembayaran::downloadBukti/$1');
    $routes->get ('pembayaran/view-bukti/(:num)',      'Pembayaran::viewBukti/$1');
    $routes->post('pembayaran/bulk-verifikasi',        'Pembayaran::bulkVerifikasi');
    $routes->get ('pembayaran/export',                 'Pembayaran::export');
    $routes->get ('pembayaran/statistik',              'Pembayaran::statistik');
    $routes->post('pembayaran/delete/(:num)',          'Pembayaran::delete/$1');
    $routes->get ('pembayaran/midtrans',               'Pembayaran::midtransPayments');
    $routes->post('pembayaran/sync-midtrans/(:num)',   'Pembayaran::syncMidtransStatus/$1');
    $routes->get ('pembayaran/midtrans-stats',         'Pembayaran::midtransStatistics');
    $routes->post('pembayaran/force-verify/(:num)',    'Pembayaran::forceVerifyPayment/$1');
    if (ENVIRONMENT === 'development') {
        $routes->get('pembayaran/debug-bukti/(:num)',  'Pembayaran::debugBukti/$1');
        $routes->get('pembayaran/check-bukti/(:num)',  'Pembayaran::checkBukti/$1');
    }

    // ===== ABSENSI =====
    $routes->get ('absensi',                           'Absensi::index');
    $routes->post('absensi/generateMultipleQRCodes',   'Absensi::generateMultipleQRCodes');
    $routes->get ('absensi/getEventStatus',            'Absensi::getEventStatus');
    $routes->post('absensi/markAttendance',            'Absensi::markAttendance');
    $routes->post('absensi/removeAttendance',          'Absensi::removeAttendance');
    $routes->post('absensi/bulkMarkAttendance',        'Absensi::bulkMarkAttendance');
    $routes->get ('absensi/export',                    'Absensi::export');
    $routes->get ('absensi/liveStats',                 'Absensi::liveStats');
    $routes->get ('absensi/getEligibleUsers',          'Absensi::getEligibleUsers');

    // ===== DOKUMEN =====
    $routes->get ('dokumen',                           'Dokumen::index');
    $routes->post('dokumen/uploadLoa',                 'Dokumen::uploadLoa');
    $routes->post('dokumen/uploadSertifikat',          'Dokumen::uploadSertifikat');
    $routes->get ('dokumen/download/(:num)',           'Dokumen::download/$1');
    $routes->post('dokumen/delete/(:num)',             'Dokumen::delete/$1');
    $routes->get ('dokumen/getVerifiedPresenters/(:num)','Dokumen::getVerifiedPresenters/$1');
    $routes->get ('dokumen/getAttendees/(:num)',       'Dokumen::getAttendees/$1');
    $routes->get ('dokumen/search-eligible-loa',       'Dokumen::searchEligibleLoa');
    $routes->get ('dokumen/users-for-loa/(:num)',            'Dokumen::getUsersForLoa/$1');
    $routes->get ('dokumen/users-for-certificate/(:num)',    'Dokumen::getUsersForCertificate/$1');
    $routes->get ('dokumen/users-for-dokumen-lain/(:num)','Dokumen::getUsersForDokumenLain/$1');
    $routes->post('dokumen/uploadDokumenLainnya', 'Dokumen::uploadDokumenLainnya');

    // ===== VOUCHER =====
    $routes->get ('voucher',                           'Voucher::index');
    $routes->post('voucher/store',                     'Voucher::store');
    $routes->get ('voucher/edit/(:num)',               'Voucher::edit/$1');
    $routes->post('voucher/update/(:num)',             'Voucher::update/$1');
    $routes->get ('voucher/delete/(:num)',             'Voucher::delete/$1');
    $routes->get ('voucher/force-delete/(:num)',       'Voucher::forceDelete/$1');
    $routes->get ('voucher/toggle-status/(:num)',      'Voucher::toggleStatus/$1');
    $routes->get ('voucher/detail/(:num)',             'Voucher::detail/$1');
    $routes->post('voucher/validate',                  'Voucher::validateVoucher');
    $routes->get ('voucher/export',                    'Voucher::export');
    $routes->get ('voucher/statistics',                'Voucher::statistics');
    $routes->get ('voucher/usage/(:num)',              'Voucher::usageHistory/$1');
    $routes->post('voucher/generate-bulk',             'Voucher::generateBulk');

    // ===== FASILITAS & BENEFIT =====
    $routes->get ('fasilitas',                         'Fasilitas::index');
    $routes->get ('fasilitas/get-by-event/(:num)',     'Fasilitas::getFasilitasByEvent/$1');
    $routes->post('fasilitas/store',                   'Fasilitas::store');
    $routes->post('fasilitas/update/(:num)',           'Fasilitas::update/$1');
    $routes->post('fasilitas/delete/(:num)',           'Fasilitas::delete/$1');
    $routes->post('fasilitas/toggle-status/(:num)',    'Fasilitas::toggleStatus/$1');
    $routes->get ('fasilitas/detail/(:num)',           'Fasilitas::getDetail/$1');
    $routes->post('fasilitas/copy-to-event',           'Fasilitas::copyToEvent');
    
    // ===== KELOLA LANDING PAGE =====
    $routes->group('landing', static function ($routes) {
    $routes->get('/', 'KelolaLanding::index');

    // set / unset event landing + clear
    $routes->post('set-event/(:num)',   'KelolaLanding::setEvent/$1');
    $routes->post('unset-event/(:num)', 'KelolaLanding::unsetEvent/$1');
    $routes->post('clear',              'KelolaLanding::clearLanding');

    // halaman detail pengaturan landing
    $routes->get('detail/(:num)',       'KelolaLanding::detail/$1');

    // aksi poster / speaker / sponsor
    $routes->post('poster/save/(:num)',           'KelolaLanding::savePoster/$1');
    $routes->post('speaker/save/(:num)',          'KelolaLanding::saveSpeaker/$1');

    $routes->post('sponsor/save/(:num)',          'KelolaLanding::saveSponsor/$1');
    $routes->post('sponsor/update/(:num)/(:num)', 'KelolaLanding::updateSponsor/$1/$2');
    $routes->post('sponsor/delete/(:num)/(:num)', 'KelolaLanding::deleteSponsor/$1/$2');
    });

    // ===== LAPORAN =====
    $routes->get('laporan',                            'Laporan::index');
    $routes->get('laporan/export',                     'Laporan::export');
    $routes->get('laporan/chart-data',                 'Laporan::getChartData');
});

// ---------------------------------------------------
// Presenter Routes (rapih & konsisten)
// ---------------------------------------------------
$routes->group('presenter', [
    'filter'    => 'role:presenter',
    'namespace' => 'App\Controllers\Role\Presenter',
], static function ($routes) {

    // Redirect root presenter → dashboard
    $routes->addRedirect('/', 'presenter/dashboard');
    $routes->get('dashboard', 'Dashboard::index');

    // ====== Event (opsional: sesuaikan dengan controllernya) ======
     $routes->get('events', 'Event::index');
    $routes->get('events/detail/(:num)', 'Event::detail/$1');
    $routes->get('events/register/(:num)', 'Event::register/$1');
    $routes->post('events/register/(:num)', 'Event::registerPost/$1');  // ← TAMBAH INI (POST)
    $routes->get('events/cancel/(:num)', 'Event::cancel/$1');

    // ====== Abstrak (presenter) ======
    $routes->get ('abstrak',                           'Abstrak::index');
    $routes->get ('abstrak/create/(:num)',             'Abstrak::create/$1'); 
    $routes->post('abstrak/store',                     'Abstrak::store');
    $routes->get ('abstrak/detail/(:num)',             'Abstrak::detail/$1');  
    $routes->get ('abstrak/download/(:segment)',       'Abstrak::download/$1');
    $routes->post('abstrak/cancel/(:num)',             'Abstrak::cancel/$1'); 
    $routes->post('abstrak/revisi/(:num)',             'Abstrak::revisi/$1');
    
    $routes->get ('abstrak/file/(:num)',           'Abstrak::fileStream/$1');    // buka/preview di iframe/tab baru
    $routes->get ('abstrak/download/(:num)',       'Abstrak::fileDownload/$1');

        // ====== Full Paper (presenter) ======
    $routes->group('fullpaper', static function ($routes) {
        $routes->get ('',                'Fullpaper::index');
        $routes->get ('create/(:num)',   'Fullpaper::create/$1');
        $routes->post('store',           'Fullpaper::store');
        $routes->get ('detail/(:num)',   'Fullpaper::detail/$1');
        $routes->get ('download/(:any)', 'Fullpaper::download/$1'); 
        $routes->post('cancel/(:num)',   'Fullpaper::cancel/$1');
        $routes->post('delete/(:num)',   'Fullpaper::delete/$1');
});


    // ====== Kontributor (presenter) ======
    $routes->group('kontributor', static function ($routes) {
        $routes->get ('start/(:num)',  'Kontributor::start/$1');
        $routes->post('save/(:num)',   'Kontributor::save/$1'); 
    });

    // ====== Pembayaran (Midtrans) ======
    $routes->get ('pembayaran',                       'Pembayaran::index');
    $routes->get ('pembayaran/instruction/(:num)',    'Pembayaran::instruction/$1');
    $routes->get ('pembayaran/create/(:num)',         'Pembayaran::create/$1');
    $routes->get ('pembayaran/event/(:num)',          'Pembayaran::eventHistory/$1');
    $routes->post('pembayaran/process-payment',       'Pembayaran::processPayment');
    $routes->get ('pembayaran/finish',                'Pembayaran::finish');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/check-status/(:num)',   'Pembayaran::checkStatus/$1');
    $routes->post('pembayaran/cancel/(:num)',         'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher',      'Pembayaran::validateVoucher');

    // ====== Absensi (presenter) ======
    $routes->get ('absensi',              'Absensi::index');
    $routes->get ('absensi/event/(:num)', 'Absensi::show/$1');
    $routes->post('absensi/scan',         'Absensi::scan');

    // ====== Dokumen (presenter) ======
    $routes->get('dokumen', 'Dokumen::index');
    $routes->get ('dokumen/loa',                            'Dokumen::loa');
    $routes->get('dokumen/loa/download/(:any)', 'Dokumen::downloadLoa/$1');
    $routes->get('dokumen/sertifikat/download/(:any)', 'Dokumen::downloadSertifikat/$1');
    $routes->get('dokumen/lainnya/download/(:any)', 'Dokumen::downloadLainnya/$1');
});


// ---------------------------------------------------
// ENHANCED: Audience Routes with Better Payment Handling
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
    $routes->get ('pembayaran/instruction/(:num)',    'Pembayaran::instruction/$1');
    $routes->get ('pembayaran/create/(:num)',         'Pembayaran::create/$1');
    $routes->get ('pembayaran/upload/(:num)',         'Pembayaran::upload/$1');
    $routes->post('pembayaran/store',                 'Pembayaran::store');
    $routes->post('pembayaran/process-payment',       'Pembayaran::processPayment');
    $routes->get ('pembayaran/finish',                'Pembayaran::finish');
    $routes->get ('pembayaran/detail/(:num)',         'Pembayaran::detail/$1');
    $routes->get ('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->post('pembayaran/reupload/(:num)',       'Pembayaran::reupload/$1');
    $routes->get ('pembayaran/cancel/(:num)',         'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher',      'Pembayaran::validateVoucher');

    // Additional payment endpoints
    $routes->get ('pembayaran/status/(:segment)',     'Pembayaran::checkStatus/$1');
    $routes->post('pembayaran/retry/(:num)',          'Pembayaran::retry/$1');

    // Absensi
    $routes->get ('absensi',              'Absensi::index');
    $routes->get ('absensi/event/(:num)', 'Absensi::show/$1');
    $routes->get ('absensi/token',        'Absensi::token');
    $routes->post('absensi/scan',         'Absensi::scan');
    $routes->post('absensi/scan-ajax',    'Absensi::scanAjax');

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

    // DASHBOARD
    $routes->get('dashboard', 'Dashboard::index');
    $routes->post('dashboard/confirm', 'Dashboard::confirm');

    // ABSTRAK
    $routes->get('abstrak', 'Abstrak::index');
    $routes->get('abstrak/(:num)', 'Abstrak::detail/$1');
    $routes->get('abstrak/preview/(:num)', 'Abstrak::preview/$1');
    $routes->get('abstrak/blob/(:num)', 'Abstrak::blob/$1');
    $routes->get('abstrak/download/(:num)', 'Abstrak::download/$1');
    $routes->post('abstrak/confirm/(:num)', 'Abstrak::confirm/$1');
    $routes->post('abstrak/review/(:num)', 'Abstrak::review/$1');

    // FULL PAPER
    $routes->group('fullpaper', static function ($routes) {
        $routes->get('', 'FullPaper::index');
        $routes->get('(:num)', 'FullPaper::detail/$1');
        $routes->post('submit/(:num)', 'FullPaper::submit/$1');
        $routes->post('(:num)/submit', 'FullPaper::submit/$1');
        $routes->post('review/(:num)', 'FullPaper::submit/$1'); // kalau memang di-reuse ke submit
        $routes->get('(:num)/download', 'FullPaper::download/$1');
        $routes->get('download/(:num)', 'FullPaper::download/$1');
        $routes->get('blob/(:num)', 'FullPaper::blob/$1');
        $routes->get('preview/(:num)', 'FullPaper::preview/$1');
        $routes->get('inline/(:num)', 'FullPaper::inline/$1');
    });

    // RIWAYAT
    $routes->get('riwayat', 'Riwayat::index');
});


// ---------------------------------------------------
// ENHANCED: Public API with Payment Support
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

    // Payment status & validation
    $routes->get ('payments/(:segment)/status', 'Api\Payment::getStatus/$1');
    $routes->post('payments/validate',           'Api\Payment::validatePayment');
    $routes->get ('payments/(:segment)/details', 'Api\Payment::getDetails/$1', ['filter' => 'auth']);

    // Voucher API
    $routes->post('voucher/validate',            'Api\Voucher::validate');
    $routes->get ('voucher/(:segment)/info',     'Api\Voucher::getInfo/$1', ['filter' => 'auth']);
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
// ENHANCED: Payment Processing Routes
// ---------------------------------------------------

// FIX: Callback eksternal harus publik (tanpa auth)
$routes->post('payment/notification', 'Payment::notification');

$routes->group('payment', ['filter' => 'auth'], static function ($routes) {
    $routes->get ('success',      'Payment::success');
    $routes->get ('pending',      'Payment::pending');
    $routes->get ('error',        'Payment::error');
    $routes->get ('finish',       'Payment::finish');
    // $routes->post('notification', 'Payment::notification'); // dipindah jadi publik
    $routes->get ('status/(:segment)', 'Payment::checkStatus/$1');
    $routes->post('retry/(:num)', 'Payment::retry/$1');
});

// ---------------------------------------------------
// Middleware
// ---------------------------------------------------
$routes->group('middleware', ['filter' => 'auth'], static function ($routes) {
    $routes->get('check-payment-status', 'Middleware\PaymentCheck::checkStatus');
    $routes->get('unlock-features',      'Middleware\FeatureUnlock::process');
});

// ---------------------------------------------------
// Mobile API (opsional, enhanced with payment)
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

    // Mobile payment endpoints
    $routes->post('payments/create',         'Payment::create', ['filter' => 'auth']);
    $routes->get ('payments/(:num)/status',  'Payment::getStatus/$1', ['filter' => 'auth']);
    $routes->get ('payments/history',        'Payment::getHistory', ['filter' => 'auth']);
});

// ---------------------------------------------------
// CRITICAL: Admin Payment Management (Enhanced)
// ---------------------------------------------------
$routes->group('admin/payments', [
    'filter'    => 'role:admin',
    'namespace' => 'App\Controllers\Role\Admin',
], static function ($routes) {
    $routes->get ('/',                           'Payment::index');
    $routes->get ('pending',                     'Payment::pending');
    $routes->get ('verified',                    'Payment::verified');
    $routes->get ('rejected',                    'Payment::rejected');
    $routes->get ('midtrans',                    'Payment::midtransPayments');
    $routes->get ('manual',                      'Payment::manualPayments');
    $routes->post('bulk-action',                 'Payment::bulkAction');
    $routes->get ('analytics',                   'Payment::analytics');
    $routes->get ('reconciliation',              'Payment::reconciliation');
    $routes->post('sync-midtrans/(:segment)',    'Payment::syncWithMidtrans/$1');
    $routes->get ('webhook-logs',                'Payment::webhookLogs');
    $routes->post('resend-webhook/(:segment)',   'Payment::resendWebhook/$1');
});

// ---------------------------------------------------
// Errors
// ---------------------------------------------------
$routes->set404Override(static function () {
    return view('errors/404');
});

// ---------------------------------------------------
// ENHANCED: Health Check and System Status
// ---------------------------------------------------
if (ENVIRONMENT === 'development') {
    $routes->group('system', static function ($routes) {
        $routes->get('health',           'System::health');
        $routes->get('payment-config',   'System::paymentConfig');
        $routes->get('webhook-test',     'System::webhookTest');
        $routes->get('database-check',   'System::databaseCheck');
        $routes->get('file-permissions', 'System::filePermissions');
    });
}

// ---------------------------------------------------
// Maintenance (aktifkan bila perlu)
// ---------------------------------------------------
// $routes->add('.*', 'Maintenance::index');