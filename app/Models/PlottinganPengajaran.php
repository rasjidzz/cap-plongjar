<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlottinganPengajaran extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'id_mapping_kelas_matakuliah',
        'id_dosen',
        'beban_sks'
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'id_dosen');
    }

    public function mappingKelasMatakuliah()
    {
        return $this->belongsTo(MappingKelasMatakuliah::class, 'id_mapping_kelas_matakuliah');
    }
}
