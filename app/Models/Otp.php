<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;
    
    protected $table = 'otp_codes';
    protected $primaryKey = 'id';
    protected $guard = ['id'];
    protected $fillable = ['email','otp','email_verified','expired_at'];

    protected $hidden = ['id','updated_at'];
}
