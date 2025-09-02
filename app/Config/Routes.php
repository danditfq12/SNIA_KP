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

    $routes->get('verify', 'Verify::index');
    $routes->post('verify', 'Verify::check');
    $routes->get('resend', 'Verify::resend');
});

$routes->get('notif/read-all', 'Notif::readAll', ['filter' => 'auth']);

// ---------------------------------------------------
// Dashboard redirect (wajib login)
// ---------------------------------------------------
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

// ---------------------------------------------------
// Admin Routes - Enhanced Payment Management
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
    $routes->get('users/detail/(:num)', 'User::detail/$1');

    // Enhanced Abstrak Management with PDF support
    $routes->get('abstrak', 'Abstrak::index');
    $routes->get('abstrak/detail/(:num)', 'Abstrak::detail/$1');
    $routes->post('abstrak/assign/(:num)', 'Abstrak::assign/$1');
    $routes->post('abstrak/update-status', 'Abstrak::updateStatus');
    $routes->post('abstrak/bulk-update-status', 'Abstrak::bulkUpdateStatus');
    $routes->get('abstrak/delete/(:num)', 'Abstrak::delete/$1');
    $routes->get('abstrak/download/(:num)', 'Abstrak::downloadFile/$1');
    $routes->get('abstrak/export', 'Abstrak::export');
    $routes->get('abstrak/statistics', 'Abstrak::statistics');
    $routes->get('abstrak/reviewers-by-category/(:num)', 'Abstrak::getReviewersByCategory/$1');

    // Reviewer Management
    $routes->get('reviewer', 'Reviewer::index');
    $routes->post('reviewer/store', 'Reviewer::store');
    $routes->post('reviewer/assignCategory', 'Reviewer::assignCategory');
    $routes->get('reviewer/removeCategory/(:num)', 'Reviewer::removeCategory/$1');
    $routes->get('reviewer/toggleStatus/(:num)', 'Reviewer::toggleStatus/$1');
    $routes->get('reviewer/detail/(:num)', 'Reviewer::detail/$1');
    $routes->get('reviewer/delete/(:num)', 'Reviewer::delete/$1');
    $routes->get('reviewer/performance', 'Reviewer::performance');

    // Event Management with role-based pricing
    $routes->get('event', 'Event::index');
    $routes->post('event/store', 'Event::store');
    $routes->get('event/edit/(:num)', 'Event::edit/$1');
    $routes->post('event/update/(:num)', 'Event::update/$1');
    $routes->get('event/delete/(:num)', 'Event::delete/$1');
    $routes->get('event/detail/(:num)', 'Event::detail/$1');
    $routes->get('event/toggle-registration/(:num)', 'Event::toggleRegistration/$1');
    $routes->get('event/toggle-abstract/(:num)', 'Event::toggleAbstractSubmission/$1');
    $routes->get('event/toggle-status/(:num)', 'Event::toggleStatus/$1');
    $routes->get('event/export', 'Event::export');
    $routes->get('event/statistics', 'Event::statistics');

    // Enhanced Pembayaran Management with Auto-Verification
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->post('pembayaran/verifikasi/(:num)', 'Pembayaran::verifikasi/$1');
    $routes->get('pembayaran/detail/(:num)', 'Pembayaran::detail/$1');
    $routes->get('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->post('pembayaran/bulk-verifikasi', 'Pembayaran::bulkVerifikasi');
    $routes->get('pembayaran/export', 'Pembayaran::export');
    $routes->get('pembayaran/statistik', 'Pembayaran::statistik');

    // Absensi Management
    $routes->get('absensi', 'Absensi::index');
    $routes->get('absensi/export', 'Absensi::export');

    // Dokumen Management - FIXED ROUTES
    $routes->get('dokumen', 'Dokumen::index');
    $routes->post('dokumen/uploadLoa/(:num)', 'Dokumen::uploadLoa/$1');
    $routes->post('dokumen/uploadSertifikat/(:num)', 'Dokumen::uploadSertifikat/$1');
    $routes->get('dokumen/download/(:num)', 'Dokumen::download/$1');
    $routes->get('dokumen/delete/(:num)', 'Dokumen::delete/$1');
    // Changed from POST to GET to match JavaScript calls
    $routes->get('dokumen/generateBulkLOA', 'Dokumen::generateBulkLOA');
    $routes->get('dokumen/generateBulkSertifikat', 'Dokumen::generateBulkSertifikat');

    // Voucher Management
    $routes->get('voucher', 'Voucher::index');
    $routes->post('voucher/store', 'Voucher::store');
    $routes->get('voucher/edit/(:num)', 'Voucher::edit/$1');
    $routes->post('voucher/update/(:num)', 'Voucher::update/$1');
    $routes->get('voucher/delete/(:num)', 'Voucher::delete/$1');
    $routes->get('voucher/toggle-status/(:num)', 'Voucher::toggleStatus/$1');
    $routes->get('voucher/detail/(:num)', 'Voucher::detail/$1');
    $routes->get('voucher/generate-code', 'Voucher::generateCode');
    $routes->post('voucher/validate', 'Voucher::validateVoucher');
    $routes->get('voucher/export', 'Voucher::export');

    // Laporan
    $routes->get('laporan', 'Laporan::index');
    $routes->get('laporan/export', 'Laporan::export');
    $routes->get('laporan/chart-data', 'Laporan::getChartData');
});

// ---------------------------------------------------
// Presenter Routes - Enhanced with Complete Payment Flow
// ---------------------------------------------------
$routes->group('presenter', [
    'filter' => 'role:presenter',
    'namespace' => 'App\Controllers\Role\Presenter'
], function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'Dashboard::index');
    
    // Event Management
    $routes->get('events', 'Event::index');
    $routes->get('events/detail/(:num)', 'Event::detail/$1');
    $routes->get('events/register/(:num)', 'Event::showRegistrationForm/$1');
    $routes->post('events/register/(:num)', 'Event::register/$1');
    $routes->post('events/calculate-price', 'Event::calculatePrice');
    
    // Abstrak Management - Fixed for PDF upload
    $routes->get('abstrak', 'Abstrak::index');
    $routes->post('abstrak/upload', 'Abstrak::upload');
    $routes->get('abstrak/status', 'Abstrak::status');
    $routes->get('abstrak/detail/(:num)', 'Abstrak::detail/$1');
    $routes->get('abstrak/download/(:segment)', 'Abstrak::download/$1');
    
    // Debug routes (remove in production)
    $routes->get('abstrak/debug/test', 'AbstractDebug::testUpload');
    $routes->post('abstrak/debug/simple', 'AbstractDebug::simpleUpload');
    
    // Enhanced Pembayaran Management - Complete Flow
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->get('pembayaran/create/(:num)', 'Pembayaran::create/$1');
    $routes->post('pembayaran/store', 'Pembayaran::store');
    $routes->get('pembayaran/detail/(:num)', 'Pembayaran::detail/$1');
    $routes->get('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->get('pembayaran/cancel/(:num)', 'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher', 'Pembayaran::validateVoucher');
    
    // Absensi Management (unlocked after payment verification)
    $routes->get('absensi', 'Absensi::index');
    $routes->post('absensi/scan', 'Absensi::scan');
    
    // Dokumen Management (unlocked after payment verification)
    $routes->get('dokumen/loa', 'Dokumen::loa');
    $routes->get('dokumen/loa/download/(:segment)', 'Dokumen::downloadLoa/$1');
    $routes->get('dokumen/sertifikat', 'Dokumen::sertifikat');
    $routes->get('dokumen/sertifikat/download/(:segment)', 'Dokumen::downloadSertifikat/$1');
});

// ---------------------------------------------------
// Audience Routes - Enhanced with Payment Integration
// ---------------------------------------------------
$routes->group('audience', [
    'filter' => 'role:audience',
    'namespace' => 'App\Controllers\Role\Audience'
], function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'Dashboard::index');
    
    // Event Registration
    $routes->get('events', 'Event::index');
    $routes->get('events/detail/(:num)', 'Event::detail/$1');
    $routes->get('events/register/(:num)', 'Event::showRegistrationForm/$1');
    $routes->post('events/register/(:num)', 'Event::register/$1');
    $routes->post('events/calculate-price', 'Event::calculatePrice');
    
    // Pembayaran Management
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->get('pembayaran/create/(:num)', 'Pembayaran::create/$1');
    $routes->post('pembayaran/store', 'Pembayaran::store');
    $routes->get('pembayaran/detail/(:num)', 'Pembayaran::detail/$1');
    $routes->get('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->get('pembayaran/cancel/(:num)', 'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher', 'Pembayaran::validateVoucher');
    
    // Absensi Management (unlocked after payment verification)
    $routes->get('absensi', 'Absensi::index');
    $routes->post('absensi/scan', 'Absensi::scan');
    
    // Dokumen Management (unlocked after payment verification)
    $routes->get('dokumen/sertifikat', 'Dokumen::sertifikat');
    $routes->get('dokumen/sertifikat/download/(:segment)', 'Dokumen::downloadSertifikat/$1');
});

// ---------------------------------------------------
// Reviewer Routes
// ---------------------------------------------------
$routes->group('reviewer', [
    'filter' => 'role:reviewer',
    'namespace' => 'App\Controllers\Role\Reviewer'
], function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'Dashboard::index');

    // Abstrak
    $routes->get('abstrak', 'Abstrak::index');
    $routes->get('abstrak/(:num)', 'Abstrak::detail/$1');
    $routes->post('review/(:num)', 'Review::store/$1');
    $routes->get('riwayat', 'Review::riwayat');
});

// ---------------------------------------------------
// Public API Routes for Event Information
// ---------------------------------------------------
$routes->group('api/v1', function ($routes) {
    $routes->get('events/active', 'Api\Event::getActiveEvents');
    $routes->get('events/(:num)/pricing', 'Api\Event::getPricing/$1');
    $routes->get('events/(:num)/details', 'Api\Event::getEventDetails/$1');
    $routes->post('events/calculate-price', 'Api\Event::calculatePrice');
    $routes->post('vouchers/validate', 'Api\Voucher::validateVoucher');
});

// ---------------------------------------------------
// User Profile Routes (untuk semua role yang login)
// ---------------------------------------------------
$routes->group('profile', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Profile::index');
    $routes->post('update', 'Profile::update');
    $routes->post('change-password', 'Profile::changePassword');
    $routes->post('upload-photo', 'Profile::uploadPhoto');
});

// ---------------------------------------------------
// Payment Verification Middleware Routes (Auto-Check)
// ---------------------------------------------------
$routes->group('middleware', ['filter' => 'auth'], function ($routes) {
    $routes->get('check-payment-status', 'Middleware\PaymentCheck::checkStatus');
    $routes->get('unlock-features', 'Middleware\FeatureUnlock::process');
});

$routes->get('absensi/ticket/(:num)', 'Absensi::ticket/$1');
