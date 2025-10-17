<?php

namespace App\Models\pembelian;

use App\Models\master\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\pembelian\TotPembelian;
use Svg\Gradient\Stop;

class Pembelian extends Model
{
    use HasFactory;
    protected $table = 'pembelian';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'TGL',
        'KODE',
        'BARCODE',
        'QTY',
        'HARGA',
        'HJ',
        'SATUAN',
        'JUMLAH',
        'DISCOUNT',
        'PPN',
        'TGLEXP',
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

    public function totpembelian(): BelongsTo
    {
        return $this->belongsTo(TotPembelian::class, 'FAKTUR', 'FAKTUR');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'KODE', 'KODE');
    }
}
