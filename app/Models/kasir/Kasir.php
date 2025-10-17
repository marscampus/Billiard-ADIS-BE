<?php

namespace App\Models\kasir;

use App\Models\master\SatuanStock;
use App\Models\master\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kasir extends Model
{
    use HasFactory;
    protected $table = 'kasir';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'TGL',
        'KODE',
        'BARCODE',
        'QTY',
        'HARGA',
        'GUDANG',
        'SATUAN',
        'DISCOUNT',
        'KETERANGAN',
        'JUMLAH',
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

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'KODE', 'KODE');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(SatuanStock::class, 'SATUAN', 'KODE');
    }
}
