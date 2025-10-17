<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Karyawan extends Model
{
    use HasApiTokens,HasFactory,HasUuids;

    protected $table = 'karyawans';
    protected $primaryKey = 'id';
    public $incrementing=false;
    protected $keyType='string';
    protected $guarded = ['id'];
}
