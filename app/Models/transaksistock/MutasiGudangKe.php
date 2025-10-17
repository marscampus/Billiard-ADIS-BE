<?php

namespace App\Models\transaksistock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiGudangKe extends Model
{
    use HasFactory;
    protected $table = 'mutasigudang_ke';
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
