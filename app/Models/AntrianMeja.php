<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntrianMeja extends Model
{
    use HasFactory;

    protected $table = 'antrian_meja';
    protected $primaryKey = 'id';
    protected $fillable = ['kode_antrian', 'kode_meja', 'nama', 'no_telepon', 'waktu_main', 'status'];
    protected $guarded = ['id'];
}
