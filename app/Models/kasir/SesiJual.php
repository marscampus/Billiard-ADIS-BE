<?php

namespace App\Models\kasir;

use App\Models\master\Gudang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesiJual extends Model
{
    use HasFactory;
    protected $table = 'sesi_jual';
    protected $primaryKey = 'SESIJUAL';
    protected $fillable =
    [
        'SESIJUAL',
        'TGL',
        'KASIR',
        'SUPERVISOR',
        'TOKO',
        'KASSA',
        'SHIFT',
        'STATUS',
        'KASAWAL',
        'TOTALJUAL',
        'BIAYAKARTU',
        'KARTU',
        'KREDIT',
        'EPAYMENT',
        'VOUCHER',
        'DISCOUNT',
        'PPN',
        'PROSES',
        'KETERANGAN',
        'DATETIME'
    ];
    public $timestamps = false;
    protected $keyType = 'string';
    public function setUpdatedAt($value)
    {
        return NULL;
    }

    public function setCreatedAt($value)
    {
        return NULL;
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'TOKO', 'KODE');
    }
}
