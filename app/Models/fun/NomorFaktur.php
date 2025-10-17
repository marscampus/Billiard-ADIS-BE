<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NomorFaktur extends Model
{
    use HasFactory;
    protected $table = 'nomorfaktur';
    protected $primaryKey = 'KODE';
    public $timestamps = false;
    public $keyType = 'string';
    public $fillable = [
        'KODE',
        'ID'
    ];
}
