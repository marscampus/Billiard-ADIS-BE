<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;
    protected $table = 'shift';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        "KODE",
        "KETERANGAN"
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
