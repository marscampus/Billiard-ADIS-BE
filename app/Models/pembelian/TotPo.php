<?php

namespace App\Models\pembelian;

use App\Models\master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotPo extends Model
{
    use HasFactory;
    protected $table = 'totpo';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'ID',
        'FAKTUR',
        'FAKTURASLI',
        'TGL',
        'JTHTMP',
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
        'USERNAME',
        'KETERANGAN',
        'CABANGENTRY',
        'TGLDO'
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
    public function totpembelian(): BelongsTo
    {
        return $this->belongsTo(TotPembelian::class, 'PO', 'FAKTUR');
    }
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'FAKTUR', 'FAKTUR');
    }
}
