<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;
    protected $table = 'log';
    protected $primaryKey = 'ID';
    protected $guarded = ['ID'];
    // protected $fillable = [
    //     'Tgl',
    //     'Keterangan',
    //     'Request',
    //     'Response',
    //     'User',
    //     'DateTime'
    // ];
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
