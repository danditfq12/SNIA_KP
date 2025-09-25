<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Alias filter -> class
     *
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // Custom
        'auth'          => \App\Filters\AuthFilter::class,
        'role'          => \App\Filters\RoleFilter::class,
    ];

    /**
     * Filter “wajib” sebelum/sesudah SEMUA request (bahkan 404).
     * Biarkan kosong supaya kita kendalikan via $globals.
     */
    public array $required = [
        'before' => [],
        'after'  => [],
    ];

    /**
     * Filter global untuk semua request.
     * Debug Toolbar kita taruh di AFTER dengan pengecualian route PDF/unduh.
     */
    public array $globals = [
        'before' => [
            // contoh: 'csrf', 'invalidchars'
        ],
        'after' => [
            // Jalankan toolbar hanya saat CI_DEBUG true (kelasnya sudah cek internal)
            'toolbar' => [
                'except' => [
                    // === Semua endpoint yang harus bersih dari inject HTML ===
                    'admin/fullpaper/view/*',
                    'admin/fullpaper/download/*',

                    // (opsional) kalau ada viewer/unduh lain:
                    'presenter/fullpaper/download/*',
                    'reviewer/fullpaper/download/*',
                    'dokumen/preview/*',
                    'dokumen/download/*',
                ],
            ],
            // 'secureheaders', // aktifkan kalau perlu
        ],
    ];

    /**
     * Filter per-method (GET/POST/…) — tidak dipakai.
     */
    public array $methods = [];

    /**
     * Filter pada pola URI tertentu — tidak dipakai.
     */
    public array $filters = [];
}
