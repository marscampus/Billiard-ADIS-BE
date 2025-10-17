<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TglTransaksi extends Model
{
    use HasFactory;
    protected $table = 'tgltransaksi';
    public $fillable = [
        'STATUS',
        'TGL',
        'POSTINGHARIAN'
    ];
}
