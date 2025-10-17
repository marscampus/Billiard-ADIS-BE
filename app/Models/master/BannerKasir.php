<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerKasir extends Model
{
    use HasFactory;
    protected $table = 'banner_kasir';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'KETERANGAN',
        'LOGO'
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
}
