<?php
namespace App\Models;
use CodeIgniter\Model;

class ReviewModel extends Model
{
    protected $table      = 'review';
    protected $primaryKey = 'id_review';
    
    protected $allowedFields = [
        'id_abstrak', 'id_reviewer', 'keputusan', 
        'komentar', 'tanggal_review'
    ];
    
    protected $useTimestamps = false;
}