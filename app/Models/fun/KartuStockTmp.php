<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuStockTmp extends Model
{
    use HasFactory;
    protected $table = 'kartustock_tmp';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'STATUS',
        'FAKTUR',
        'URUT',
        'TGL',
        'GUDANG',
        'KODE',
        'SATUAN',
        'QTY',
        'DEBET',
        'KREDIT',
        'KETERANGAN',
        'HARGA',
        'DISCITEM',
        'DISCFAKTUR1',
        'DISCFAKTUR2',
        'PPN',
        'HP',
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
