<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerubahanHargaStock extends Model
{
    use HasFactory;
    protected $table = 'perubahanhargastock';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'KODE',
        'TANGGAL_PERUBAHAN',
        'FAKTUR',
        'KETERANGAN',
        'DATETIME',
        'USERNAME',
        'DISCOUNT',
        'PAJAK',
        'HBLAMA',
        'HB2LAMA',
        'HB3LAMA',
        'HJLAMA',
        'HJ2LAMA',
        'HJ3LAMA',
        'HB',
        'HB2',
        'HB3',
        'HJ',
        'HJ2',
        'HJ3',
        'HJ_TINGKAT1',
        'MIN_TINGKAT1',
        'HJ_TINGKAT2',
        'MIN_TINGKAT2',
        'HJ_TINGKAT3',
        'MIN_TINGKAT3',
        'HJ_TINGKAT4',
        'MIN_TINGKAT4',
        'HJ_TINGKAT5',
        'MIN_TINGKAT5',
        'HJ_TINGKAT6',
        'MIN_TINGKAT6',
        'HJ_TINGKAT7',
        'MIN_TINGKAT7'
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
}
