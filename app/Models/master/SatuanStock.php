<?php

namespace App\Models\master;

use App\Models\master\Stock as MasterStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Stock; // Pastikan namespace ini benar jika Stock berada di tempat yang berbeda

class SatuanStock extends Model
{
    use HasFactory;

    // Menentukan tabel yang digunakan
    protected $table = 'satuanstock';

    // Menentukan primary key
    protected $primaryKey = 'KODE';

    // Mengizinkan atribut yang dapat diisi secara massal
    protected $fillable = [
        'KODE',
        'KETERANGAN'
    ];

    // Menonaktifkan timestamps
    public $timestamps = false;

    // Menentukan tipe data primary key
    protected $keyType = 'string';

    /**
     * Definisikan relasi ke model Stock dengan kolom SATUAN
     *
     * @return HasMany
     */
    public function stock(): HasMany
    {
        return $this->hasMany(MasterStock::class, 'SATUAN', 'KODE');
    }

    /**
     * Definisikan relasi ke model Stock dengan kolom SATUAN2
     *
     * @return HasMany
     */
    public function stock2(): HasMany
    {
        return $this->hasMany(MasterStock::class, 'SATUAN2', 'KODE');
    }

    /**
     * Definisikan relasi ke model Stock dengan kolom SATUAN3
     *
     * @return HasMany
     */
    public function stock3(): HasMany
    {
        return $this->hasMany(MasterStock::class, 'SATUAN3', 'KODE');
    }
}
