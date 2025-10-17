<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;
    protected $table = 'gudang';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'KETERANGAN'
    ];
    public $timestamps = false;
    protected $keyType = 'string';

    // protected $casts = [
    //     'KODE' => 'string', // Mengubah tipe data kolom KODE menjadi string
    // ];

    public function setUpdatedAt($value)
    {
        return NULL;
    }


    public function setCreatedAt($value)
    {
        return NULL;
    }
}
