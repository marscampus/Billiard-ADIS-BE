<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiskonPeriode extends Model
{
    use HasFactory;
    protected $table = 'diskon_periode';
    // protected $primaryKey = 'KODEDISKON';
    protected $fillable = [
        'KODEDISKON',
        'KODE',
        'BARCODE',
        // 'NAMA',
        'TGL',
        'HJ_AWAL',
        'HJ_DISKON',
        'KUOTA_QTY',
        'TGL_MULAI',
        'TGL_AKHIR',
    ];
    public $timestamps = false;
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'KODE', 'KODE');
    }
}
