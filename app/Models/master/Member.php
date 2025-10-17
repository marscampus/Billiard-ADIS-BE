<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    use HasFactory;
    protected $table = 'member';
    protected $primaryKey = 'KODE';
    protected $fillable  = [
        'KODE',
        'STATUS',
        'NAMA',
        'ALAMAT',
        'TELEPON',
        'KOTA',
        'JENIS_MEMBER',
        'REKENING',
        'NAMA_CP_1',
        'ALAMAT_CP_1',
        'TELEPON_CP_1',
        'HP_CP_1',
        'EMAIL_CP_1',
        'NAMA_CP_2',
        'ALAMAT_CP_2',
        'TELEPON_CP_2',
        'HP_CP_2',
        'EMAIL_CP_2',
        'PLAFOND_1',
        'PLAFOND_2'
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
    public function mutasiMember(): BelongsTo
    {
        return $this->belongsTo(MutasiMember::class, 'KODE', 'MEMBER');
    }
}
