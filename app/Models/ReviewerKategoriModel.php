<?php
namespace App\Models;
use CodeIgniter\Model;

class ReviewerKategoriModel extends Model
{
    protected $table      = 'reviewer_kategori';
    protected $primaryKey = 'id';
    
    protected $allowedFields = ['id_reviewer', 'id_kategori'];
}