<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Database_users extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $table = 'database_users';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    // protected $fillable = ['nama','id_users'];

}
