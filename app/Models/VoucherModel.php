<?php
namespace App\Models;
use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table      = 'voucher';
    protected $primaryKey = 'id_voucher';
    
    protected $allowedFields = [
        'kode_voucher', 'tipe', 'nilai', 'kuota', 
        'masa_berlaku', 'status'
    ];
}