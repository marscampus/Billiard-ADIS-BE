<?php

namespace App\Models\pembelian;

use App\Models\master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelunasanHutang extends Model
{
    use HasFactory;
    protected $table = 'pelunasanhutang';
    protected $primaryKey = 'FAKTUR';
    protected $fillable = [
        'FAKTUR',
        'TGL',
        'FKT',
        'TGLFAKTUR',
        'TOTALFAKTUR',
        'BAYARFAKTUR',
        'DISCOUNT',
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

    public function totpelunasanhutang(): BelongsTo
    {
        return $this->belongsTo(TotPelunasanHutang::class, 'FAKTUR', 'FAKTUR');
    }
}
