<?php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\RawSql;

class FullPaperModel extends Model
{
    protected $table;
    protected $primaryKey;
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    
    protected $allowedFields = [
        'full_paper_path',
        'full_paper_uploaded_at',
        'full_paper_status',
        'eligible_to_pay',
    ];

    public const STATUS_NONE      = 'NONE';
    public const STATUS_UPLOADED  = 'UPLOADED';
    public const STATUS_REVISION  = 'REVISION';
    public const STATUS_ACCEPTED  = 'ACCEPTED';
    public const STATUS_REJECTED  = 'REJECTED';

    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        $this->table = $this->resolveTargetTable();
        $this->primaryKey = $this->resolvePrimaryKey($this->table);
    }

    /**
     * Get primary key name (compatibility method)
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get table name (compatibility method)
     */
    public function getTable(): string
    {
        return $this->table;
    }

    private function resolveTargetTable(): string
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('submissions')) return 'submissions';
        if ($db->tableExists('abstrak'))     return 'abstrak';
        throw new \RuntimeException('Tidak menemukan tabel target untuk full paper.');
    }

    private function resolvePrimaryKey(string $table): string
    {
        $db     = \Config\Database::connect();
        $fields = array_map(fn($f) => strtolower($f->name), $db->getFieldData($table));

        foreach (['id', 'id_abstrak', 'submission_id', 'id_submission'] as $cand) {
            if (in_array(strtolower($cand), $fields, true)) {
                return $cand;
            }
        }
        return 'id';
    }

    private function hasColumn(string $column): bool
    {
        $db = \Config\Database::connect();
        $fields = $db->getFieldData($this->table);
        foreach ($fields as $f) {
            if (strcasecmp($f->name, $column) === 0) return true;
        }
        return false;
    }

    public function getLatestRowByUserEvent(int $userId, int $eventId): ?array
    {
        $orderCols = ['updated_at', 'tanggal_upload', 'created_at'];
        $builder = $this->where('id_user', $userId)->where('event_id', $eventId);
        
        foreach ($orderCols as $c) {
            if ($this->hasColumn($c)) {
                $builder = $builder->orderBy($c, 'DESC');
                break;
            }
        }
        
        return $builder->orderBy($this->primaryKey, 'DESC')->first();
    }

    public function setEligibleToPay(int $rowId, bool $eligible = true): bool
    {
        if (!$this->hasColumn('eligible_to_pay')) return true;
        
        $db   = \Config\Database::connect();
        $isPg = strtolower($db->DBDriver) === 'postgre';
        $val  = $isPg ? new RawSql($eligible ? 'TRUE' : 'FALSE') : $eligible;
        
        return (bool) $this->update($rowId, ['eligible_to_pay' => $val]);
    }

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

    public function moveUploadedFile(\CodeIgniter\HTTP\Files\UploadedFile $file, int $userId, int $eventId): string
    {
        $dir = WRITEPATH.'uploads/fullpaper';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ext      = strtolower($file->getClientExtension() ?: 'pdf');
        $basename = 'fp_'.$userId.'_'.$eventId.'_'.time();
        $name     = $basename.'.'.$ext;

        $file->move($dir, $name, true);
        return $name;
    }

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

        if (!$data) return true;
        return (bool) $this->update($rowId, $data);
    }

    public function attachFullPaperByUserEvent(int $userId, int $eventId, string $storedFilename, ?string $status = null): bool
    {
        $row = $this->getLatestRowByUserEvent($userId, $eventId);
        if (!$row) return false;

        return $this->attachFullPaperToRow((int)$row[$this->primaryKey], $storedFilename, $status);
    }

    public function listUserPapersWithEvent(int $userId): array
    {
        $builder = $this->select($this->table.'.*, e.title AS event_title, e.event_date');

        if ($this->hasColumn('event_id')) {
            $builder = $builder->join('events e', "e.id = {$this->table}.event_id", 'left');
        }

        return $builder->where("{$this->table}.id_user", $userId)
                       ->orderBy($this->primaryKey, 'DESC')
                       ->findAll();
    }
}