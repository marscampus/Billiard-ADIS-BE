<?php

namespace App\Models\kasir;

use App\Models\master\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotKasirTmp extends Model
{
    use HasFactory;
    protected $table = 'totkasir_tmp';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'TGL',
        'MEMBER',
        'GUDANG',
        'DISCOUNT',
        'ADMIN',
        'SUBTOTAL',
        'VOUCHER',
        'KARTU',
        'NOKARTU',
        'NOTRACE',
        'BAYARKARTU',
        'BAYAR',
        'TUNAI',
        'TOTAL',
        'PENARIKANTUNAI',
        'PEMBULATAN',
        'DATETIME',
        'USERNAME',
        'CABANG'
    ];

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
