<?php

namespace App\Models;

use CodeIgniter\Model;

class AbstrakModel extends Model
{
    protected $table            = 'abstrak';
    protected $primaryKey       = 'id_abstrak';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'id_user',
        'id_kategori',
        'event_id',
        'judul',
        'file_abstrak',
        'status',            // menunggu | sedang_direview | diterima | ditolak | revisi
        'tanggal_upload',
        'revisi_ke',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    /* =========================
     * LIST/TABLE (lama - dipakai admin)
     * ========================= */
    public function getAbstrakWithDetails(?int $limit = null): array
    {
        $builder = $this->db->table($this->table)
            ->select('abstrak.*, users.nama_lengkap, users.email, kategori_abstrak.nama_kategori, events.title AS event_title')
            ->join('users', 'users.id_user = abstrak.id_user')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
            ->join('events', 'events.id = abstrak.event_id', 'left')
            ->orderBy('abstrak.tanggal_upload', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }

    /* =========================
     * DETAIL (lama - dipakai admin)
     * ========================= */
    public function getDetailWithRelations(int $idAbstrak): ?array
    {
        $row = $this->db->table($this->table)
            ->select('abstrak.*, users.nama_lengkap, users.email, kategori_abstrak.nama_kategori, events.title AS event_title')
            ->join('users', 'users.id_user = abstrak.id_user')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
            ->join('events', 'events.id = abstrak.event_id', 'left')
            ->where('abstrak.id_abstrak', $idAbstrak)
            ->get()->getRowArray();

        return $row ?: null;
    }

    /* =========================
     * Tambahan: dipakai Presenter (seragam style admin)
     * ========================= */

    // Daftar abstrak milik user + relasi (untuk index presenter)
    public function getByUserWithDetails(int $userId): array
    {
        return $this->db->table('abstrak a')
            ->select("
                a.*,
                e.title AS event_title,
                e.event_date,
                e.abstract_deadline,
                e.abstract_submission_active,
                k.nama_kategori,
                COALESCE((SELECT COUNT(*) FROM review r WHERE r.id_abstrak = a.id_abstrak),0) AS review_count
            ", false)
            ->join('events e', 'e.id = a.event_id', 'left')
            ->join('kategori_abstrak k', 'k.id_kategori = a.id_kategori', 'left')
            ->where('a.id_user', $userId)
            ->orderBy('a.tanggal_upload', 'DESC')
            ->get()->getResultArray();
    }

    // Detail 1 abstrak milik user (untuk detail presenter)
    public function getDetailWithRelationsForUser(int $abstrakId, int $userId): ?array
    {
        $row = $this->db->table('abstrak a')
            ->select("
                a.*,
                e.title AS event_title,
                e.event_date,
                e.abstract_deadline,
                e.abstract_submission_active,
                k.nama_kategori,
                u.nama_lengkap AS author_name
            ", false)
            ->join('events e', 'e.id = a.event_id', 'left')
            ->join('kategori_abstrak k', 'k.id_kategori = a.id_kategori', 'left')
            ->join('users u', 'u.id_user = a.id_user', 'left')
            ->where('a.id_abstrak', $abstrakId)
            ->where('a.id_user', $userId)
            ->get()->getRowArray();

        return $row ?: null;
    }

    // Standarisasi simpan file (match admin): WRITEPATH/uploads/abstrak
    public function moveUploadedFile($file, int $userId, int $eventId): string
    {
        if (!$file || !$file->isValid()) {
            $err = method_exists($file,'getErrorString') ? $file->getErrorString() : 'File tidak valid';
            throw new \RuntimeException('File tidak valid: '.$err);
        }

        $dir = WRITEPATH.'uploads/abstrak/'; // singular (standar baru)
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ts   = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('YmdHis');
        $ext  = strtolower($file->getClientExtension());
        $name = "abstract_{$userId}_{$eventId}_{$ts}.{$ext}";

        if (!$file->move($dir, $name)) {
            throw new \RuntimeException('Gagal menyimpan file.');
        }
        return $name;
    }

    /* =========================
     * UTIL lama (tetap disimpan)
     * ========================= */
    public function getAbstrakByUser(int $userId): array
    {
        return $this->where('id_user', $userId)
            ->orderBy('tanggal_upload', 'DESC')
            ->findAll();
    }

    public function getAbstrakByStatus(string $status): array
    {
        return $this->where('status', $status)->findAll();
    }

    public function getAbstrakByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->findAll();
    }

    /* =========================
     * KPI/Stats (fix bug penumpukan where)
     * ========================= */
    public function getStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*)                                                AS total,
                SUM(CASE WHEN status = 'menunggu'        THEN 1 ELSE 0 END) AS menunggu,
                SUM(CASE WHEN status = 'sedang_direview' THEN 1 ELSE 0 END) AS sedang_direview,
                SUM(CASE WHEN status = 'diterima'        THEN 1 ELSE 0 END) AS diterima,
                SUM(CASE WHEN status = 'ditolak'         THEN 1 ELSE 0 END) AS ditolak,
                SUM(CASE WHEN status = 'revisi'          THEN 1 ELSE 0 END) AS revisi
            FROM abstrak
        ")->getRowArray() ?: [];

        foreach ($row as $k => $v) {
            $row[$k] = (int) $v;
        }
        return [
            'total'           => $row['total'] ?? 0,
            'menunggu'        => $row['menunggu'] ?? 0,
            'sedang_direview' => $row['sedang_direview'] ?? 0,
            'diterima'        => $row['diterima'] ?? 0,
            'ditolak'         => $row['ditolak'] ?? 0,
            'revisi'          => $row['revisi'] ?? 0,
        ];
    }

    public function latest(int $limit = 10): array
    {
        return $this->orderBy('tanggal_upload', 'DESC')->findAll($limit);
    }
}