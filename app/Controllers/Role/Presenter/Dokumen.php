<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use Config\Database;

class Dokumen extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    private array $docCols = []; // cache kolom tabel dokumen (lowercase)

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /* ========== UTIL ========== */

    private function userId(): int
    {
        foreach (['id_user','user_id','id'] as $k) {
            $v = session($k);
            if (!empty($v)) return (int)$v;
        }
        return 0;
    }

    private function getDocCols(): array
    {
        if ($this->docCols) return $this->docCols;

        // 1) Coba via getFieldData
        try {
            $out = [];
            foreach ($this->db->getFieldData('dokumen') as $fd) {
                $name = strtolower($fd->name ?? '');
                if ($name) $out[$name] = true;
            }
            if ($out) return $this->docCols = $out;
        } catch (\Throwable $e) {}

        // 2) Fallback via information_schema
        try {
            $driver = strtolower((string) ($this->db->DBDriver ?? ''));
            if (strpos($driver, 'postgre') !== false) {
                $sql = "SELECT column_name FROM information_schema.columns
                        WHERE table_schema = current_schema() AND table_name = 'dokumen'";
            } else {
                $sql = "SELECT COLUMN_NAME AS column_name FROM information_schema.columns
                        WHERE table_schema = DATABASE() AND table_name = 'dokumen'";
            }
            $rows = $this->db->query($sql)->getResultArray();
            $out  = [];
            foreach ($rows as $r) {
                $name = strtolower($r['column_name'] ?? '');
                if ($name) $out[$name] = true;
            }
            if ($out) return $this->docCols = $out;
        } catch (\Throwable $e) {}

        return $this->docCols = [];
    }

    private function hasCol(string $col): bool
    {
        $cols = $this->getDocCols();
        return isset($cols[strtolower($col)]);
    }

    private function firstCol(array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if ($this->hasCol($c)) return $c;
        }
        return null;
    }

    private function existingCols(array $candidates): array
    {
        $ok = [];
        foreach ($candidates as $c) {
            if ($this->hasCol($c)) $ok[] = $c;
        }
        return $ok;
    }

    private function coalesceExpr(array $candidates): ?string
    {
        $ok = $this->existingCols($candidates);
        if (!$ok) return null;
        $parts = array_map(fn($c) => $this->db->protectIdentifiers('d.' . $c), $ok);
        return count($parts) === 1 ? $parts[0] : ('COALESCE(' . implode(',', $parts) . ')');
    }

    private function detectFilenameCol(): ?string
    {
        return $this->firstCol([
            'filename','file_name','nama_file','file',
            'path_file','filepath','path','doc_path','doc_file','document','dokumen_file'
        ]);
    }

    private function resolveFilePath(string $tipe, string $stored): ?string
    {
        $stored = trim($stored);
        if ($stored !== '' && is_file($stored)) return $stored;

        $subdir = ($tipe === 'loa') ? 'loa' : 'sertifikat';
        $candidates = [
            WRITEPATH . "uploads/dokumen/{$subdir}/" . $stored,
            WRITEPATH . "uploads/" . $stored,
            WRITEPATH . $stored,
        ];
        foreach ($candidates as $p) {
            if (is_file($p)) return $p;
        }
        return null;
    }

    private function listDocs(string $tipe, int $userId): array
    {
        $idCol   = $this->firstCol(['id_dokumen','dokumen_id','id']);
        $fileCol = $this->detectFilenameCol();
        $tsExpr  = $this->coalesceExpr(['updated_at','created_at','uploaded_at','waktu_upload','generated_at']);

        $b = $this->db->table('dokumen d');

        if ($idCol) $b->select($this->db->protectIdentifiers("d.$idCol") . ' AS id_dokumen', false);
        else        $b->select('NULL AS id_dokumen', false);

        $b->select('d.event_id, d.tipe', false);

        if ($fileCol) $b->select($this->db->protectIdentifiers("d.$fileCol") . ' AS filecol', false);
        else          $b->select('NULL AS filecol', false);

        if ($tsExpr) $b->select($tsExpr . ' AS ts', false);
        else         $b->select('NULL AS ts', false);

        $b->select('e.title', false)
          ->join('events e', 'e.id = d.event_id', 'left')
          ->where('d.id_user', $userId)
          ->where('d.tipe', $tipe);

        if ($tsExpr) {
            $b->orderBy('ts', 'DESC');
        } elseif ($idCol) {
            $b->orderBy($this->db->protectIdentifiers("d.$idCol"), 'DESC', false);
        } else {
            $b->orderBy('d.event_id', 'DESC');
        }

        $rows = $b->get()->getResultArray();

        return array_map(function($r){
            return [
                'id'       => (int)($r['id_dokumen'] ?? 0),
                'event_id' => (int)($r['event_id'] ?? 0),
                'title'    => (string)($r['title'] ?? '-'),
                'filename' => (string)($r['filecol'] ?? ''),
                'ts'       => !empty($r['ts']) ? strtotime((string)$r['ts']) : null,
            ];
        }, $rows ?? []);
    }

    /* ========== PAGES ========== */

    /** INDEX: satu halaman berisi LOA & Sertifikat */
    public function index()
    {
        $uid = $this->userId();
        if ($uid <= 0) return redirect()->to('/auth/login')->with('error','Silakan login.');

        return view('role/presenter/dokumen/index', [
            'title' => 'Dokumen Saya',
            'loa'   => $this->listDocs('loa', $uid),
            'serti' => $this->listDocs('sertifikat', $uid),
        ]);
    }

    /** Link lama diarahkan ke index dengan anchor */
    public function loa()
    {
        return redirect()->to('/presenter/dokumen#loa');
    }

    /** Link lama diarahkan ke index dengan anchor */
    public function sertifikat()
    {
        return redirect()->to('/presenter/dokumen#sertifikat');
    }

    /* ========== DOWNLOAD ========== */

    public function downloadLoa(string $filename)
    {
        return $this->downloadCommon($filename, 'loa');
    }

    public function downloadSertifikat(string $filename)
    {
        return $this->downloadCommon($filename, 'sertifikat');
    }

    private function downloadCommon(string $filename, string $tipe)
    {
        $uid = $this->userId();
        if ($uid <= 0) return redirect()->to('/auth/login')->with('error','Silakan login.');

        $rows = $this->db->table('dokumen')
            ->where('id_user', $uid)
            ->where('tipe', $tipe)
            ->get()->getResultArray();

        if (!$rows) return redirect()->back()->with('error','Dokumen tidak ditemukan.');

        $cands = [
            'filename','file_name','nama_file','file',
            'path_file','filepath','path','doc_path','doc_file','document','dokumen_file'
        ];

        $match = null;
        foreach ($rows as $r) {
            foreach ($cands as $c) {
                if (!array_key_exists($c, $r)) continue;
                $val = (string)$r[$c];
                if ($val === '') continue;

                if ($val === $filename || basename($val) === $filename) { $match = $val; break 2; }
                if (substr($val, -strlen($filename)) === $filename) { $match = $val; break 2; }
            }
        }

        if (!$match) return redirect()->back()->with('error','File tidak ditemukan atau bukan milik Anda.');

        $path = $this->resolveFilePath($tipe, $match);
        if (!$path) return redirect()->back()->with('error','File tidak tersedia di server.');

        return $this->response->download($path, null);
    }
}