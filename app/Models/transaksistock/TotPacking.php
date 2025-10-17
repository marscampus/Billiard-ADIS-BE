<?php

namespace App\Models\transaksistock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TotPacking extends Model
{
    use HasFactory;
    protected $table = 'totpacking';
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
