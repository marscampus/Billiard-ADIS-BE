<?php

namespace App\Models\pembelian;

use App\Models\master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotPelunasanHutang extends Model
{
    use HasFactory;
    protected $table = 'totpelunasanhutang';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'TGL',
        'SUPPLIER',
        'TOTAL',
        'TUNAI',
        'CEK',
        'DISCOUNT',
        'BANK',
        'USERNAME',
        'DATETIME'
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

    public function pelunasanhutang(): BelongsTo
    {
        return $this->belongsTo(PelunasanHutang::class, 'FAKTUR', 'FAKTUR');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'SUPPLIER', 'KODE');
    }
}
