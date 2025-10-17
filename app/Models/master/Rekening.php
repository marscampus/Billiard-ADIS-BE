<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    use HasFactory;
    protected $table = 'rekening';
    protected $primaryKey =  'KODE';
    protected $fillable = [
        'KODE',
        'KETERANGAN',
        'JENISREKENING',
        'AWAL',
        'STATUS'
    ];
    protected $keyType = 'string';
    public $timestamps = false;

    public function setUpdatedAt($value)
    {
        return NULL;
    }


    public function setCreatedAt($value)
    {
        return NULL;
    }
}
