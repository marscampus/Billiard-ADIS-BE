<?php

namespace App\Models\pembelian;

use App\Models\master\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Po extends Model
{
    use HasFactory;
    protected $table = 'po';
    protected $primaryKey = 'FAKTUR';
    public $timestamps = false;
    public $keyType = 'string';
    public $fillable = [
        'FAKTUR',
        'TGL',
        'KODE',
        'BARCODE',
        'QTY',
        'HARGA',
        'SATUAN',
        'DISCOUNT',
        'PPN',
        'KETERANGAN',
        'TGLEXP',
        'JUMLAH'
    ];
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
}
