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
// Enhanced QR System Routes - FIXED ORDER
// ---------------------------------------------------
$routes->group('qr', function ($routes) {
    // SPECIFIC routes MUST come BEFORE generic routes
    
    // QR scanner interface (specific routes first)
    $routes->get('scanner', 'QRAttendance::showScannerInterface');
    
    // Mobile-friendly scanner 
    $routes->get('mobile', 'QRAttendance::showScannerInterface');
    
    // Process attendance submission
    $routes->post('process', 'QRAttendance::process');
    
    // QR code generation for admin (with authentication)
    $routes->post('generate/(:num)', 'QRAttendance::generateEventQRCodes/$1', ['filter' => 'role:admin']);
    
    // Debug routes (only in development)
    if (ENVIRONMENT === 'development') {
        $routes->get('debug/(:segment)', 'QRAttendance::debugQR/$1');
        $routes->get('test/(:num)', 'QRAttendance::generateTestQR/$1');
    }
    
    // GENERIC route MUST be LAST to avoid conflicts
    $routes->get('(:segment)', 'QRAttendance::scan/$1');
});

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
// Debug Routes (Development Only)
// ---------------------------------------------------
if (ENVIRONMENT === 'development') {
    $routes->group('debug', function ($routes) {
        $routes->get('system', 'DebugHelper::systemCheck');
        $routes->get('qr/(:segment)', 'DebugHelper::qrDebug/$1');
        $routes->get('qr', 'DebugHelper::qrDebug');
        $routes->get('generate/(:num)', 'DebugHelper::generateTestQR/$1');
        $routes->get('errors', 'DebugHelper::errorLog');
        $routes->get('db', 'DebugHelper::dbTest');
        // Event debugging routes
        $routes->get('event/status/(:num)', 'Role\Admin\Event::refreshEventStatus/$1', ['filter' => 'role:admin']);
    });
}

// ---------------------------------------------------
// Admin Routes - COMPLETELY FIXED AND SECURED
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
    $routes->post('users/delete/(:num)', 'User::delete/$1');
    $routes->get('users/detail/(:num)', 'User::detail/$1');

    // Enhanced Abstrak Management with PDF support
    $routes->get('abstrak', 'Abstrak::index');
    $routes->get('abstrak/detail/(:num)', 'Abstrak::detail/$1');
    $routes->post('abstrak/assign/(:num)', 'Abstrak::assign/$1');
    $routes->post('abstrak/update-status', 'Abstrak::updateStatus');
    $routes->post('abstrak/bulk-update-status', 'Abstrak::bulkUpdateStatus');
    $routes->post('abstrak/delete/(:num)', 'Abstrak::delete/$1');
    $routes->get('abstrak/download/(:num)', 'Abstrak::downloadFile/$1');
    $routes->get('abstrak/export', 'Abstrak::export');
    $routes->get('abstrak/statistics', 'Abstrak::statistics');
    $routes->get('abstrak/reviewers-by-category/(:num)', 'Abstrak::getReviewersByCategory/$1');

    // Reviewer Management - FIXED
$routes->get('reviewer', 'Reviewer::index');
$routes->post('reviewer/store', 'Reviewer::store');
$routes->get('reviewer/detail/(:num)', 'Reviewer::detail/$1');
$routes->get('reviewer/toggleStatus/(:num)', 'Reviewer::toggleStatus/$1');
$routes->post('reviewer/assignCategory', 'Reviewer::assignCategory');
$routes->get('reviewer/removeCategory/(:num)', 'Reviewer::removeCategory/$1');
$routes->get('reviewer/delete/(:num)', 'Reviewer::delete/$1');
$routes->get('reviewer/export', 'Reviewer::export');
$routes->get('reviewer/statistics', 'Reviewer::getStatistics');

    // FIXED: Event Management Routes - All methods properly secured with POST for sensitive operations
    $routes->get('event', 'Event::index');
    $routes->post('event/store', 'Event::store');
    $routes->get('event/edit/(:num)', 'Event::edit/$1');
    $routes->post('event/update/(:num)', 'Event::update/$1');
    $routes->post('event/delete/(:num)', 'Event::delete/$1');
    $routes->get('event/detail/(:num)', 'Event::detail/$1');
    
    // CRITICAL FIX: All toggle functions MUST use POST for security
    $routes->post('event/toggle-registration/(:num)', 'Event::toggleRegistration/$1');
    $routes->post('event/toggle-abstract-submission/(:num)', 'Event::toggleAbstractSubmission/$1');
    $routes->post('event/toggle-status/(:num)', 'Event::toggleStatus/$1');
    
    // Export and statistics
    $routes->get('event/export', 'Event::export');
    $routes->get('event/statistics', 'Event::statistics');

    // Enhanced Pembayaran Management with Auto-Verification AND DELETE
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->post('pembayaran/verifikasi/(:num)', 'Pembayaran::verifikasi/$1');
    $routes->get('pembayaran/detail/(:num)', 'Pembayaran::detail/$1');
    $routes->get('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->get('pembayaran/view-bukti/(:num)', 'Pembayaran::viewBukti/$1');
    $routes->post('pembayaran/bulk-verifikasi', 'Pembayaran::bulkVerifikasi');
    $routes->get('pembayaran/export', 'Pembayaran::export');
    $routes->get('pembayaran/statistik', 'Pembayaran::statistik');
    $routes->post('pembayaran/delete/(:num)', 'Pembayaran::delete/$1');
    
    // NEW: Delete payment route
    $routes->post('pembayaran/delete/(:num)', 'Pembayaran::delete/$1');

    // Enhanced Absensi Management with Multiple QR Codes
    $routes->get('absensi', 'Absensi::index');
    $routes->post('absensi/generateMultipleQRCodes', 'Absensi::generateMultipleQRCodes');
    $routes->post('absensi/markAttendance', 'Absensi::markAttendance');
    $routes->post('absensi/removeAttendance', 'Absensi::removeAttendance');
    $routes->post('absensi/bulkMarkAttendance', 'Absensi::bulkMarkAttendance');
    $routes->get('absensi/getEligibleUsers', 'Absensi::getEligibleUsers');
    $routes->get('absensi/export', 'Absensi::export');
    $routes->get('absensi/liveStats', 'Absensi::liveStats');
    $routes->get('absensi/getEventStatus', 'Absensi::getEventStatus');

    // Document Management
    $routes->get('dokumen', 'Dokumen::index');
    $routes->post('dokumen/uploadLoa/(:num)', 'Dokumen::uploadLoa/$1');
    $routes->post('dokumen/uploadSertifikat/(:num)', 'Dokumen::uploadSertifikat/$1');
    $routes->get('dokumen/download/(:num)', 'Dokumen::download/$1');
    $routes->get('dokumen/preview/(:num)', 'Dokumen::preview/$1');
    $routes->post('dokumen/delete/(:num)', 'Dokumen::delete/$1');
    $routes->post('dokumen/generateBulkLOA', 'Dokumen::generateBulkLOA');
    $routes->post('dokumen/generateBulkSertifikat', 'Dokumen::generateBulkSertifikat');
    // AJAX endpoints untuk modal forms
    $routes->get('dokumen/getVerifiedPresenters/(:num)', 'Dokumen::getVerifiedPresenters/$1');
    $routes->get('dokumen/getAttendees/(:num)', 'Dokumen::getAttendees/$1');

    // Voucher Management  
    $routes->get('voucher', 'Voucher::index');
    $routes->post('voucher/store', 'Voucher::store');
    $routes->get('voucher/edit/(:num)', 'Voucher::edit/$1');
    $routes->post('voucher/update/(:num)', 'Voucher::update/$1');
    $routes->get('voucher/delete/(:num)', 'Voucher::delete/$1');
    $routes->get('voucher/force-delete/(:num)', 'Voucher::forceDelete/$1'); // Route baru untuk force delete
    $routes->get('voucher/toggle-status/(:num)', 'Voucher::toggleStatus/$1');
    $routes->get('voucher/detail/(:num)', 'Voucher::detail/$1');
    $routes->get('voucher/generate-code', 'Voucher::generateCode');
    $routes->post('voucher/validate', 'Voucher::validateVoucher');
    $routes->get('voucher/export', 'Voucher::export');

    // Reports
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
    
    // Abstract Management - Fixed for PDF upload
    $routes->get('abstrak', 'Abstrak::index');
    $routes->post('abstrak/upload', 'Abstrak::upload');
    $routes->get('abstrak/status', 'Abstrak::status');
    $routes->get('abstrak/detail/(:num)', 'Abstrak::detail/$1');
    $routes->get('abstrak/download/(:segment)', 'Abstrak::download/$1');
    
    // Enhanced Payment Management - Complete Flow
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->get('pembayaran/create/(:num)', 'Pembayaran::create/$1');
    $routes->post('pembayaran/store', 'Pembayaran::store');
    $routes->get('pembayaran/detail/(:num)', 'Pembayaran::detail/$1');
    $routes->get('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->post('pembayaran/cancel/(:num)', 'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher', 'Pembayaran::validateVoucher');
    
    // Enhanced Attendance Management (unlocked after payment verification)
    $routes->get('absensi', 'Absensi::index');
    $routes->post('absensi/scan', 'Absensi::scan');
    
    // Document Management (unlocked after payment verification)
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
    
    // Payment Management
    $routes->get('pembayaran', 'Pembayaran::index');
    $routes->get('pembayaran/create/(:num)', 'Pembayaran::create/$1');
    $routes->post('pembayaran/store', 'Pembayaran::store');
    $routes->get('pembayaran/detail/(:num)', 'Pembayaran::detail/$1');
    $routes->get('pembayaran/download-bukti/(:num)', 'Pembayaran::downloadBukti/$1');
    $routes->post('pembayaran/cancel/(:num)', 'Pembayaran::cancel/$1');
    $routes->post('pembayaran/validate-voucher', 'Pembayaran::validateVoucher');
    
    // Enhanced Attendance Management (unlocked after payment verification)
    $routes->get('absensi', 'Absensi::index');
    $routes->post('absensi/scan', 'Absensi::scan');
    
    // Document Management (unlocked after payment verification)
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

    // Abstract Review
    $routes->get('abstrak', 'Abstrak::index');
    $routes->get('abstrak/(:num)', 'Abstrak::detail/$1');
    $routes->post('review/(:num)', 'Review::store/$1');
    $routes->get('riwayat','Riwayat::index');
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
    
    // QR Code API endpoints
    $routes->get('qr/validate/(:segment)', 'Api\QR::validateQRCode/$1');
    $routes->post('qr/scan', 'Api\QR::processScan');
    $routes->get('events/(:num)/qr-codes', 'Api\Event::getQRCodes/$1', ['filter' => 'role:admin']);
});

// ---------------------------------------------------
// User Profile Routes (for all logged in roles)
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

// ---------------------------------------------------
// Mobile App Routes (Optional - for future mobile app)
// ---------------------------------------------------
$routes->group('mobile/api/v1', ['namespace' => 'App\Controllers\Mobile'], function ($routes) {
    // Authentication
    $routes->post('auth/login', 'Auth::login');
    $routes->post('auth/logout', 'Auth::logout', ['filter' => 'auth']);
    $routes->post('auth/refresh', 'Auth::refreshToken');
    
    // QR Scanning
    $routes->post('qr/scan', 'QR::scanQRCode', ['filter' => 'auth']);
    $routes->get('qr/validate/(:segment)', 'QR::validateQRCode/$1');
    
    // Events
    $routes->get('events', 'Event::getActiveEvents', ['filter' => 'auth']);
    $routes->get('events/(:num)', 'Event::getEventDetail/$1', ['filter' => 'auth']);
    
    // Attendance
    $routes->get('attendance/history', 'Attendance::getHistory', ['filter' => 'auth']);
    $routes->get('attendance/event/(:num)', 'Attendance::getEventAttendance/$1', ['filter' => 'auth']);
});

// ---------------------------------------------------
// Webhook Routes (for payment gateways, etc.)
// ---------------------------------------------------
$routes->group('webhook', ['namespace' => 'App\Controllers\Webhook'], function ($routes) {
    // Payment gateway webhooks
    $routes->post('midtrans', 'Midtrans::handle');
    $routes->post('xendit', 'Xendit::handle');
    $routes->post('gopay', 'Gopay::handle');
    
    // QR Code analytics webhook (optional)
    $routes->post('qr-analytics', 'QRAnalytics::track');
});

// ---------------------------------------------------
// Error Pages
// ---------------------------------------------------
$routes->set404Override(function() {
    return view('errors/404');
});

// ---------------------------------------------------
// Maintenance Mode (uncomment when needed)
// ---------------------------------------------------
// $routes->add('.*', 'Maintenance::index');