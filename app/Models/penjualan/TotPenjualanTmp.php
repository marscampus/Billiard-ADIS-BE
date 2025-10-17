<?php

namespace App\Models\penjualan;

use App\Models\master\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotPenjualanTmp extends Model
{
    use HasFactory;
    protected $table = 'totpenjualan_tmp';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'KODESESI',
        'CATATAN',
        // 'KODESESI_RETUR',
        'FAKTUR',
        'TGL',
        'GUDANG',
        'DISCOUNT',
        'DISCOUNT2',
        'PAJAK',
        'TOTAL',
        'CARABAYAR',
        'TUNAI',
        'BAYARKARTU',
        'AMBILKARTU',
        'EPAYMENT',
        'NAMAKARTU',
        'NOMORKARTU',
        'NAMAPEMILIK',
        'TIPEEPAYMENT',
        'KEMBALIAN',
        'DATETIME',
        'USERNAME',
    ];
    // protected $fillable = [
    //     'FAKTUR',
    //     'FAKTURASLI',
    //     'TGL',
    //     'JTHTMP',
    //     'GUDANG',
    //     'MEMBER',
    //     'PPN',
    //     'PERSDISC',
    //     'SUBTOTAL',
    //     'PAJAK',
    //     'DISCOUNT',
    //     'PEMBULATAN',
    //     'TOTAL',
    //     'TUNAI',
    //     'PIUTANG',
    //     'DATETIME',
    //     'USERNAME'
    // ];
    public $keyType = 'string';
    public $timestamps = false;
    public function setUpdatedAt($value)
    {
        return NULL;
    }

    public function setCreatedAt($value)
    {
        return NULL;
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'MEMBER', 'KODE');
    }
}
