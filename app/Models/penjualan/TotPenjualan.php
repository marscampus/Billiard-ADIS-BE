<?php

namespace App\Models\penjualan;

use App\Models\kasir\SesiJual;
use App\Models\master\Member;
use App\Models\master\Gudang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotPenjualan extends Model
{
    use HasFactory;
    protected $table = 'totpenjualan';
    protected $primaryKey = 'FAKTUR';
    protected $guarded = [
        'id'
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
    public function sesijual(): BelongsTo
    {
        return $this->belongsTo(SesiJual::class, 'KODESESI', 'SESIJUAL');
    }
    public function sesijual_retur(): BelongsTo
    {
        return $this->belongsTo(SesiJual::class, 'KODESESI_RETUR', 'SESIJUAL');
    }
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GUDANG', 'KODE');
    }
}
