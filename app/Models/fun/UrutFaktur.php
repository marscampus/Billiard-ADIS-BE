<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrutFaktur extends Model
{
    use HasFactory;
    protected $table = 'urutfaktur';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'ID',
        'FAKTUR',
        'TGL',
        'DATETIME',
        'USERNAME'
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
