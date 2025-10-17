<?php

namespace App\Models\laporan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianFakturPajak extends Model
{
    use HasFactory;

    protected $table = 'pembelian_fakturpajak';
    protected $primaryKey = 'nomortran';
    protected $fillable = [
        'nomortran',
        'tanggal',
        'tglfaktur_pajak',
        'tglterima_faktur',
        'jumlah_faktur',
        'seri_faktur',
        'cekfaktur',
        'OprId'
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
}
