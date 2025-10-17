<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuHutang extends Model
{
    use HasFactory;
    protected $table = 'kartuhutang';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'STATUS',
        'FAKTUR',
        'URUT',
        'TGL',
        'GUDANG',
        'SC',
        'SUPPLIER',
        'KETERANGAN',
        'DEBET',
        'KREDIT',
        'FKT',
        'JTHTMP',
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
