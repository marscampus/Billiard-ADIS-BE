<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu_role extends Model
{
    use HasFactory;
 
    protected $table = 'menu_roles';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $hidden = ['id','created_at','updated_at'];

}
