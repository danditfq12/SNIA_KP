<?php
namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;

class Dokumen extends BaseController
{
    protected DokumenModel $dm;
    protected PembayaranModel $payM;
    protected EventModel $eventM;

    public function __construct()
    {
        $this->dm     = new DokumenModel();
        $this->payM   = new PembayaranModel();
        $this->eventM = new EventModel();
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? 0);
    }

    /** List sertifikat milik user + coba isi event terkait */
    public function sertifikat()
    {
        $idUser = $this->uid();
        if (!$idUser) return redirect()->to(site_url('auth/login'));

        // Ambil semua sertifikat user
        $certs = $this->dm
            ->where('id_user', $idUser)
            ->whereIn('tipe', ['Sertifikat','sertifikat','CERTIFICATE','Certificate'])
            ->orderBy('uploaded_at', 'DESC')
            ->findAll();

        if (!$certs) {
            return view('role/audience/dokumen/sertifikat', [
                'title' => 'Sertifikat Saya',
                'certs' => [],
                'pk'    => $this->dm->primaryKey ?: 'id_dokumen',
            ]);
        }

        // 1) Kumpulkan event_id yang sudah ada di dokumen (jika kolom tersebut ada)
        $eventIdsInDoc = [];
        foreach ($certs as $c) {
            if (array_key_exists('event_id', $c) && !empty($c['event_id'])) {
                $eventIdsInDoc[] = (int) $c['event_id'];
            }
        }
        $eventIdsInDoc = array_values(array_unique($eventIdsInDoc));

        // 2) Ambil pembayaran verified user + judul event
        $pays = $this->payM->select('id_pembayaran,event_id,verified_at,tanggal_bayar')
            ->where('id_user', $idUser)
            ->where('status', 'verified')
            ->orderBy('COALESCE(verified_at, tanggal_bayar)', 'DESC', false)
            ->findAll();

        $payEventIds = $pays ? array_values(array_unique(array_map(fn($p)=> (int)$p['event_id'], $pays))) : [];

        // 3) Ambil judul semua event yang mungkin dibutuhkan (dari dokumen & pembayaran)
        $needEventIds = array_values(array_unique(array_merge($eventIdsInDoc, $payEventIds)));
        $eventMap = [];
        if ($needEventIds) {
            $rows = $this->eventM->select('id,title')->whereIn('id', $needEventIds)->findAll();
            foreach ($rows as $r) $eventMap[(int)$r['id']] = (string)$r['title'];
        }

        // 4) Lengkapi masing-masing sertifikat dengan event_id & event_title
        foreach ($certs as &$c) {
            $c['event_title'] = null;

            // Prefer: kolom event_id di dokumen
            if (array_key_exists('event_id', $c) && !empty($c['event_id'])) {
                $eid = (int)$c['event_id'];
                $c['event_title'] = $eventMap[$eid] ?? null;
                continue;
            }

            // Fallback: cari pembayaran terdekat berdasarkan waktu terhadap uploaded_at
            $upAt = !empty($c['uploaded_at']) ? strtotime($c['uploaded_at']) : null;
            if ($upAt && $pays) {
                $best = null; $bestDiff = PHP_INT_MAX;
                foreach ($pays as $p) {
                    $ts = $p['verified_at'] ?? $p['tanggal_bayar'] ?? null;
                    if (!$ts) continue;
                    $pt = strtotime($ts);
                    if (!$pt) continue;
                    $diff = abs($upAt - $pt);
                    if ($diff < $bestDiff) {
                        $bestDiff = $diff;
                        $best = $p;
                    }
                }
                if ($best) {
                    $eid = (int)$best['event_id'];
                    $c['event_id']    = $eid;
                    $c['event_title'] = $eventMap[$eid] ?? null;
                }
            }

            // Kalau masih belum ada juga → biarkan null
        }
        unset($c);

        return view('role/audience/dokumen/sertifikat', [
            'title' => 'Sertifikat Saya',
            'certs' => $certs,
            'pk'    => $this->dm->primaryKey ?: 'id_dokumen',
        ]);
    }

    /**
     * GET /audience/dokumen/sertifikat/download/{id|namaFile}?preview=1
     */
    public function downloadSertifikat($segment)
    {
        $idUser = $this->uid();
        if (!$idUser) return redirect()->to(site_url('auth/login'));

        $pk = $this->dm->primaryKey ?: 'id_dokumen';

        // Base query: milik user + tipe sertifikat
        $qb = $this->dm->where('id_user', $idUser)
                       ->whereIn('tipe', ['Sertifikat','sertifikat','CERTIFICATE','Certificate']);

        // By ID atau by file_path
        if (ctype_digit((string)$segment)) {
            $doc = $qb->where($pk, (int)$segment)->first();
        } else {
            $base = basename((string)$segment);
            $doc  = $qb->groupStart()
                        ->where('file_path', $segment)
                        ->orWhere('file_path', $base)
                      ->groupEnd()
                      ->first();
        }

        if (!$doc) {
            return redirect()->back()->with('error', 'Sertifikat tidak ditemukan / bukan milik Anda.');
        }

        // Resolve ke path absolut / URL
        $abs = method_exists($this->dm, 'resolveAbsolutePath')
             ? $this->dm->resolveAbsolutePath($doc)
             : null;

        if (!$abs) {
            $fp = (string)($doc['file_path'] ?? '');
            if (preg_match('~^https?://~i', $fp)) {
                $abs = $fp; // URL
            } elseif (is_file($fp)) {
                $abs = $fp; // path langsung
            } else {
                $abs = rtrim(WRITEPATH, '/\\').'/uploads/dokumen/'.$fp; // default lokal
            }
        }

        // Jika URL (S3/Drive) → redirect
        if (preg_match('~^https?://~i', $abs)) {
            return redirect()->to($abs);
        }

        if (!is_file($abs)) {
            return redirect()->back()->with('error', 'File sertifikat tidak ditemukan di server.');
        }

        // Preview inline?
        $pv = strtolower((string)$this->request->getGet('preview'));
        $wantInline = in_array($pv, ['1','true','yes'], true);

        // Tentukan MIME
        $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf'        => 'application/pdf',
            'jpg','jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            default      => (function ($p) {
                if (function_exists('mime_content_type')) {
                    return mime_content_type($p) ?: 'application/octet-stream';
                }
                return 'application/octet-stream';
            })($abs),
        };

        if ($wantInline && in_array($mime, ['application/pdf','image/jpeg','image/png'], true)) {
            if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
            if (function_exists('ini_set')) { @ini_set('zlib.output_compression', '0'); }
            if (ob_get_length()) { @ob_end_clean(); }

            $this->response->setHeader('Content-Type', $mime);
            $this->response->setHeader('Content-Disposition', 'inline; filename="'.basename($abs).'"');
            $this->response->setHeader('X-Content-Type-Options', 'nosniff');
            $this->response->setHeader('Cache-Control', 'private, max-age=0, must-revalidate');
            $this->response->setHeader('Pragma', 'public');

            return $this->response->setBody(file_get_contents($abs));
        }

        // Default: download
        return $this->response->download($abs, null)->setFileName(basename($abs));
    }
}
