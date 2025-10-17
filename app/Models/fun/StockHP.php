<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHP extends Model
{
    use HasFactory;
    protected $table = 'stock_hp';
    protected $primaryKey = 'ID';
    protected $fillable = [
        "ID",
        "Status",
        "Kode",
        "Tgl",
        "HP",
        "HPLifo",
        "HargaBeliAwal",
        "HargaBeliAkhir",
        "HargaJualAwal",
        "HargaJualAkhir",
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
