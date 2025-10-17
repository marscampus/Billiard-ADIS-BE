<?php

namespace App\Models\fun;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Username extends Model
{
    use HasFactory;
    protected $table = 'username';
    protected $primaryKey = 'ID';
    protected $guarded = ['ID'];
    // protected $fillable = [
    //     'ID',
    //     'USERNAME',
    //     'USERPASSWORD',
    //     'SECRET',
    //     'FULLNAME',
    //     'LOGIN',
    //     'KASTELLER',
    //     'KODE',
    //     'ONLINE',
    //     'PLAFOND',
    //     'TIMEOUT',
    //     'ADMIN',
    //     'TELLER',
    //     'CS',
    //     'TABUNGAN',
    //     'KREDIT',
    //     'DEPOSITO',
    //     'AKUTANSI',
    //     'CABANG',
    //     'GABUNGAN',
    //     'CABANGINDUK',
    //     'AKTIF',
    //     'PLAFONDSETORAN',
    //     'STATUSOTORISASI',
    //     'UNIT',
    //     'USERNAMEACC',
    //     'LOGO',
    //     'DATETIMELOGIN',
    //     'HOSTLOGIN',
    //     'IPLOGIN'
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
