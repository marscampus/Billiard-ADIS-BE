<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiMember extends Model
{
    use HasFactory;
    protected $table = 'mutasimember';
    protected $primaryKey = 'ID';
    protected $fillable = [
        "ID",
        "FAKTUR",
        "JUMLAH",
        "DEBET",
        "KREDIT",
        "POINTDEBET",
        "POINTKREDIT",
        "NOMINALPOINTDEBET",
        "NOMINALPOINTKREDIT",
        "MEMBER",
        "USERNAME",
        "TGL",
        "DATETIME"
    ];

    public $timestamps = false;

    public function setUpdatedAt($value)
    {
        return NULL;
    }

    public function setCreatedAt($value)
    {
        return NULL;
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'MEMBER', 'KODE');
    }
}
