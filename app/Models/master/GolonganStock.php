<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GolonganStock extends Model
{
    use HasFactory;
    protected $table = 'golonganstock';
    protected $primaryKey = 'KODE';
    protected $fillable = [
        "KODE",
        "KETERANGAN"
    ];
    protected $keyType = 'string';

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'KODE', 'GOLONGAN');
    }

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
