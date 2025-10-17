<?php

namespace App\Models\pembelian;

use App\Models\master\Stock;
use App\Models\master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSupplier extends Model
{
    use HasFactory;
    protected $table = 'stock_supplier';
    protected $primaryKey = 'ID';
    protected $guarded = [
        'ID'
    ];
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'SUPPLIER', 'KODE');
    }
}
