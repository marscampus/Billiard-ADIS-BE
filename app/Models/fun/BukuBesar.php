<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BukuBesar extends Model
{
    use HasFactory;
    protected $table = 'bukubesar';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'CABANG',
        'STATUS',
        'URUT',
        'FAKTUR',
        'TGL',
        'REKENING',
        'KETERANGAN',
        'DEBET',
        'KREDIT',
        'KAS',
        'DATETIME',
        'USERNAME'
    ];
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
