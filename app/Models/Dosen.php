<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    /** @use HasFactory<\Database\Factories\DosenFactory> */
    use HasFactory;
    protected $fillable = [
        'name',
        'lecturer_code',
        'jabatan_fungsional_akademik',
        'email',
        'status_pegawai',
        'pendidikan_terakhir',
        'nidn',
        'id_kelompok_keahlian',
    ];

    public function kelompokKeahlian()
    {
        return $this->belongsTo(KelompokKeahlian::class, 'id_kelompok_keahlian');
    }
}
