<?php

namespace App\Models\transaksistock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiGudangDari extends Model
{
    use HasFactory;
    protected $table = 'mutasigudang_dari';
    protected $primaryKey = 'ID';
    protected $guarded = [
        'ID'
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
