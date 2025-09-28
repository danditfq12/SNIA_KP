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

        // custom
        'auth'          => \App\Filters\AuthFilter::class,
        'role'          => \App\Filters\RoleFilter::class,
        'nocache'       => \App\Filters\NoCacheFilter::class,
    ];

    public array $required = [
        'before' => [],
        'after'  => [],
    ];

    public array $globals = [
        'before' => [
            // kosong — akses diatur via Routes (auth/role dsb).
        ],
        'after' => [
            // ⚠️ pastikan DebugToolbar TIDAK menyentuh response biner (PDF)
            'toolbar' => [
                'except' => [
                    'admin/fullpaper/view/*',
                    'admin/fullpaper/download/*',
                    'presenter/fullpaper/download/*',
                    'reviewer/fullpaper/download/*',
                    'dokumen/preview/*',
                    'dokumen/download/*',
                ],
            ],
            // secure headers bisa memblok iframe; exclude endpoint PDF
            'secureheaders' => [
                'except' => [
                    'admin/fullpaper/view/*',
                    'admin/fullpaper/blob/*',
                    'admin/fullpaper/download/*',
                    'presenter/fullpaper/download/*',
                    'reviewer/fullpaper/download/*',
                    'dokumen/preview/*',
                    'dokumen/download/*',
                    
                ],
            ],
        ],
    ];

    public array $methods = [];

    // bisa dipakai bila mau paksa nocache di endpoint PDF
    public array $filters = [
        'nocache' => [
            'after' => [
                'admin/fullpaper/view/*',
                'dokumen/preview/*',
            ],
        ],
    ];
}