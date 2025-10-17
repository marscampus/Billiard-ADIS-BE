<?php

namespace App\Models\pembelian;

use App\Models\master\Stock;
use App\Models\master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtnPembelian extends Model
{
    use HasFactory;
    protected $table = 'rtnpembelian';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'TGL',
        'KODE',
        'BARCODE',
        'QTY',
        'HARGA',
        'SATUAN',
        'PPN',
        'DISCOUNT',
        'JUMLAH',
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
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'KODE', 'KODE');
    }

    public function totrtnpembelian(): BelongsTo
    {
        return $this->belongsTo(TotRtnPembelian::class, 'FAKTUR', 'FAKTUR');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'SUPPLIER', 'KODE');
    }
}
