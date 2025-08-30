<?php
namespace App\Models;
use CodeIgniter\Model;

class KategoriAbstrakModel extends Model
{
    protected $table      = 'kategori_abstrak';
    protected $primaryKey = 'id_kategori';
    
    protected $allowedFields = ['nama_kategori', 'deskripsi'];
}