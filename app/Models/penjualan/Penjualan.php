<?php

namespace App\Models\penjualan;

use App\Models\master\Member;
use App\Models\master\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penjualan extends Model
{
    use HasFactory;
    protected $table = 'penjualan';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'TGL',
        'KODE',
        'BARCODE',
        'QTY',
        'HARGA',
        'SATUAN',
        'DISCOUNT',
        'HARGADISC',
        'KETERANGAN',
        'HP',
        'PPN',
        'JUMLAH'
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

    public function totpenjualan(): BelongsTo
    {
        return $this->belongsTo(TotPenjualan::class, 'FAKTUR', 'FAKTUR');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'KODE', 'MEMBER');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'KODE', 'KODE');
    }
}
