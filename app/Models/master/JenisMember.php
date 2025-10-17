<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisMember extends Model
{
    use HasFactory;
    protected $table = 'jenismember';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        'KODE',
        'KETERANGAN'
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
