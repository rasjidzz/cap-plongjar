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
        'nip',
        'jabatan_fungsional_akademik',
        'id_jabatan_struktural',
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
    public function plottinganPengajarans()
    {
        return $this->hasMany(PlottinganPengajaran::class, 'id_dosen');
    }
    public function jabatanStruktural()
    {
        return $this->belongsTo(JabatanStruktural::class, 'id_jabatan_struktural');
    }
}
