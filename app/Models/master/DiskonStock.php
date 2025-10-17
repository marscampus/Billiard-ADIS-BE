<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiskonStock extends Model
{
    use HasFactory;
    protected $table = 'diskon_stock';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'ID',
        'KODE_STOCK',
        'BARCODE',
        'HJ1',
        'H_DISKON',
        'TGL_BERAKHIR',
        'TGL_BERMULA',
        'QTY',
        'STATUS'
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
}
