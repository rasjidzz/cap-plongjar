<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    /** @use HasFactory<\Database\Factories\DosenFactory> */
    use HasFactory;
    protected $fillable = [
        'nama_dosen',
        'kode_dosen',
        'jabatan_fungsional_akademik',
        'email_dosen',
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
