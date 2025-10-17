<?php

namespace App\Models\pembelian;

use App\Models\master\Gudang;
use App\Models\master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotPembelian extends Model
{
    use HasFactory;
    protected $table = 'totpembelian';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'PO',
        'FAKTURASLI',
        'TGL',
        'JTHTMP',
        'GUDANG',
        'SUPPLIER',
        'SUBTOTAL',
            'PERSDISC',
            'DISCOUNT',
            'DISCOUNT2',
            'PPN',
            'PAJAK',
        'PEMBULATAN',
        'TOTAL',
        'TUNAI',
        'HUTANG',
        'DATETIME',
        'USERNAME',
        'KONSINYASI',
        'TOTITEMPO',
        'TOTITEMTERIMA',
        'PEMBAYARAN',
        'KETERANGAN'
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'SUPPLIER', 'KODE');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GUDANG', 'KODE');
    }

    public function totpo(): BelongsTo
    {
        return $this->belongsTo(TotPo::class, 'PO', 'FAKTUR');
    }

    public function totrtnpembelian(): BelongsTo
    {
        return $this->belongsTo(TotRtnPembelian::class, 'FAKTURPEMBELIAN', 'FAKTUR');
    }
}
