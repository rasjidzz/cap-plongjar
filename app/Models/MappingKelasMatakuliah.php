<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MappingKelasMatakuliah extends Model
{
    protected $fillable = [
        'id_matakuliah',
        'id_tahun_ajaran',
        'nama_kelas',
        'kuota',
    ];

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class, 'id_matakuliah');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran');
    }
}
