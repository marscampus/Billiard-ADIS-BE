<?php

namespace App\Models\pembelian;

use App\Models\fun\KartuStock;
use App\Models\master\Gudang;
use App\Models\master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotRtnPembelian extends Model
{
    use HasFactory;
    protected $table = 'totrtnpembelian';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'FAKTURPEMBELIAN',
        'FAKTURASLI',
        'FAKTURPO',
        'TGL',
        'JTHTMP',
        'GUDANG',
        'SUPPLIER',
        'PPN',
        'PERSDISC',
        'SUBTOTAL',
        'PAJAK',
        'DISCOUNT',
        'PEMBULATAN',
        'TOTAL',
        'TUNAI',
        'HUTANG',
        'DATETIME',
        'KETERANGAN',
        'USERNAME'
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

    public function kartustock(): BelongsTo
    {
        return $this->belongsTo(KartuStock::class, 'FAKTUR', 'FAKTUR');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GUDANG', 'KODE');
    }

    public function rtnpembelian(): BelongsTo
    {
        return $this->belongsTo(RtnPembelian::class, 'FAKTUR', 'FAKTUR');
    }

    public function totpembelian(): BelongsTo
    {
        return $this->belongsTo('FAKTURPEMBELIAN', 'FAKTUR');
    }
}
