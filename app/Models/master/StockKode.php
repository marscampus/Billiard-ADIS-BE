<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Svg\Gradient\Stop;

class StockKode extends Model
{
    use HasFactory;
    protected $table = 'stock_kode';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'BARCODE',
        'KETERANGAN',
        'STATUS'
    ];
    public $keyType = 'string';
    public $timestamps = false;
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'KODE', 'KODE');
    }
}
