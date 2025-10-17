<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $table = 'supplier';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'NAMA',
        'ALAMAT',
        'TELEPON',
        'KOTA',
        'JENIS_USAHA',
        'REKENING',
        'NAMA_CP_1',
        'ALAMAT_CP_1',
        'TELEPON_CP_1',
        'HP_CP_1',
        'EMAIL_CP_1',
        'NAMA_CP_2',
        'ALAMAT_CP_2',
        'TELEPON_CP_2',
        'HP_CP_2',
        'EMAIL_CP_2',
        'PLAFOND_1',
        'PLAFOND_2'
    ];
    public $timestamps = false;
    protected $keyType = 'string';

    public function setUpdatedAt($value)
    {
        return NULL;
    }


    public function setCreatedAt($value)
    {
        return NULL;
    }
}
