<?php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\RawSql;

/**
 * Model FullPaper yang menempel ke tabel EXISTING:
 * - Prefer: submissions
 * - Fallback: abstrak
 *
 * Kolom-kolom yang dipakai sesuai migrasi:
 * - full_paper_path           (TEXT, nullable)
 * - full_paper_uploaded_at    (TIMESTAMP, nullable)
 * - full_paper_status         (VARCHAR(20), default 'NONE')
 * - eligible_to_pay           (BOOLEAN, default false)
 *
 * Catatan:
 * - Kita hanya update kolom-kolom di atas (allowedFields fokus ke kolom FP).
 * - Primary key & table dideteksi dinamis dari DB.
 */
class FullPaperModel extends Model
{
    /** @var string */
    protected $table;

    /** @var string */
    protected $primaryKey;

    protected $returnType    = 'array';
    protected $useTimestamps = false; // kolom FP punya timestamp sendiri (full_paper_uploaded_at)

    // Hanya kolom full-paper agar aman terhadap skema abstrak/submissions yang variatif
    protected $allowedFields = [
        'full_paper_path',
        'full_paper_uploaded_at',
        'full_paper_status',
        'eligible_to_pay',
    ];

    // --- Status helper (opsional) ---
    public const STATUS_NONE      = 'NONE';
    public const STATUS_UPLOADED  = 'UPLOADED';
    public const STATUS_REVISION  = 'REVISION';
    public const STATUS_ACCEPTED  = 'ACCEPTED';
    public const STATUS_REJECTED  = 'REJECTED';

    public function __construct(?array $data = null)
    {
        parent::__construct($data);

        // Resolve target table
        $this->table = $this->resolveTargetTable();

        // Resolve primary key (paling umum yang sering dipakai)
        $this->primaryKey = $this->resolvePrimaryKey($this->table);
    }

    /** Prefer submissions > abstrak */
    private function resolveTargetTable(): string
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('submissions')) return 'submissions';
        if ($db->tableExists('abstrak'))     return 'abstrak';

        throw new \RuntimeException('Tidak menemukan tabel target untuk full paper (butuh "submissions" atau "abstrak").');
    }

    /** Cari primary key yang ada di tabel target */
    private function resolvePrimaryKey(string $table): string
    {
        $db     = \Config\Database::connect();
        $fields = array_map(fn($f) => strtolower($f->name), $db->getFieldData($table));

        foreach (['id', 'id_abstrak', 'submission_id', 'id_submission'] as $cand) {
            if (in_array(strtolower($cand), $fields, true)) {
                return $cand;
            }
        }

        // fallback “id” (banyak skema pakai ini)
        return 'id';
    }

    // ========== Helper util kolom ==========

    /** Cek apakah kolom ada pada table target */
    private function hasColumn(string $column): bool
    {
        $db = \Config\Database::connect();
        $fields = $db->getFieldData($this->table);
        foreach ($fields as $f) {
            if (strcasecmp($f->name, $column) === 0) return true;
        }
        return false;
    }

    // ========== Query helper umum ==========

    /**
     * Ambil satu baris abstrak/submission milik user & event (yang terbaru).
     * Digunakan saat mengaitkan full paper ke abstrak yang “terakhir”.
     */
    public function getLatestRowByUserEvent(int $userId, int $eventId): ?array
    {
        // Kolom tanggal yang biasanya ada pada abstrak/submissions
        $orderCols = ['updated_at', 'tanggal_upload', 'created_at'];

        $builder = $this->where('id_user', $userId)->where('event_id', $eventId);
        foreach ($orderCols as $c) {
            if ($this->hasColumn($c)) {
                $builder = $builder->orderBy($c, 'DESC');
                break;
            }
        }

        // Primary key desc sebagai fallback
        $builder = $builder->orderBy($this->primaryKey, 'DESC');

        return $builder->first();
    }

    /**
     * Update flag eligible_to_pay (boolean).
     * Gunakan ini saat full paper di-ACC agar pembayaran bisa dibuka.
     */
    public function setEligibleToPay(int $rowId, bool $eligible = true): bool
    {
        if (!$this->hasColumn('eligible_to_pay')) return true; // kolom tidak ada → anggap sukses

        $db   = \Config\Database::connect();
        $isPg = strtolower($db->DBDriver) === 'postgre';

        $val = $isPg ? new RawSql($eligible ? 'TRUE' : 'FALSE') : $eligible;
        return (bool) $this->update($rowId, ['eligible_to_pay' => $val]);
    }

    /**
     * Set status full paper (UPLOADED/REVISION/ACCEPTED/REJECTED/NONE).
     */
    public function setFullPaperStatus(int $rowId, string $status): bool
    {
        if (!$this->hasColumn('full_paper_status')) return true;

        $status = strtoupper($status);
        if (!in_array($status, [
            self::STATUS_NONE, self::STATUS_UPLOADED, self::STATUS_REVISION,
            self::STATUS_ACCEPTED, self::STATUS_REJECTED
        ], true)) {
            throw new \InvalidArgumentException('Status full paper tidak dikenal: '.$status);
        }

        return (bool) $this->update($rowId, ['full_paper_status' => $status]);
    }

    /**
     * Simpan file yang di-upload ke WRITEPATH/uploads/fullpaper dan
     * mengembalikan NAMA FILE yang disimpan.
     */
    public function moveUploadedFile(\CodeIgniter\HTTP\Files\UploadedFile $file, int $userId, int $eventId): string
    {
        $dir = WRITEPATH.'uploads/fullpaper';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ext     = strtolower($file->getClientExtension() ?: 'pdf');
        $basename= 'fp_'.$userId.'_'.$eventId.'_'.time();
        $name    = $basename.'.'.$ext;

        // overwrite true agar revisi bisa ganti nama sama (tapi kita sudah pakai timestamp)
        $file->move($dir, $name, true);
        return $name;
    }

    /**
     * Update data full paper untuk satu baris (abstrak/submission) tertentu.
     * - Set path file
     * - Set uploaded_at (NOW)
     * - Set status UPLOADED (default)
     */
    public function attachFullPaperToRow(int $rowId, string $storedFilename, ?string $status = null): bool
    {
        $data = [];

        if ($this->hasColumn('full_paper_path')) {
            $data['full_paper_path'] = $storedFilename;
        }
        if ($this->hasColumn('full_paper_uploaded_at')) {
            $data['full_paper_uploaded_at'] = date('Y-m-d H:i:s');
        }
        if ($this->hasColumn('full_paper_status')) {
            $data['full_paper_status'] = strtoupper($status ?: self::STATUS_UPLOADED);
        }

        if (!$data) return true; // kalau kolom FP tidak ada sama sekali, anggap sukses
        return (bool) $this->update($rowId, $data);
    }

    /**
     * Shortcut: cari baris abstrak/submission terbaru utk (user,event) lalu tempel full paper.
     * Mengembalikan true jika sukses, false bila tak ketemu baris target.
     */
    public function attachFullPaperByUserEvent(int $userId, int $eventId, string $storedFilename, ?string $status = null): bool
    {
        $row = $this->getLatestRowByUserEvent($userId, $eventId);
        if (!$row) return false;

        return $this->attachFullPaperToRow((int)$row[$this->primaryKey], $storedFilename, $status);
    }

    /**
     * Ambil daftar full-paper milik user (berdasarkan tabel abstrak/submissions),
     * termasuk metadata event jika tersedia.
     */
    public function listUserPapersWithEvent(int $userId): array
    {
        // SELECT fleksibel—join ke events kalau ada kolom event_id
        $builder = $this->select($this->table.'.*, e.title AS event_title, e.event_date');

        if ($this->hasColumn('event_id')) {
            $builder = $builder->join('events e', "e.id = {$this->table}.event_id", 'left');
        }

        return $builder->where("{$this->table}.id_user", $userId)
                       ->orderBy($this->primaryKey, 'DESC')
                       ->findAll();
    }
}