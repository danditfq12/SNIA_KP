<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 | --------------------------------------------------------------------
 | Application Events
 | --------------------------------------------------------------------
 */

Events::on('pre_system', static function (): void {
    // Standar buffer & zlib guard dari CI4
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    // Daftarkan kolektor DB untuk Toolbar saat debug (bukan CLI)
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');

        // Hot Reload (dev only)
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});

/**
 * JANGAN panggil $toolbar->respond() di sini.
 * Kita sudah pakai DebugToolbar via Filter ($globals['after']) dengan daftar "except".
 * Ini mencegah injeksi ganda & menjaga response PDF tetap bersih.
 */
