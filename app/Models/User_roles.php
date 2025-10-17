<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_roles extends Model
{
    use HasFactory;
    protected $table = 'user_roles';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $hidden = ['id','created_at','updated_at'];

    // public function UserOfKaryawans(){
    //     return $this->belongsTo(Karyawan::class, 'id','users_id');
    // }
}
