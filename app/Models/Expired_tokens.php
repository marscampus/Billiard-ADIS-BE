<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expired_tokens extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $table = 'expired_tokens';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
}
