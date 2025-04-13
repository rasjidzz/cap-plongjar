<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoordinatorMatakuliah extends Model
{
    protected $fillable = [
        'id_dosen',
        'id_mapping_kelas_matakuliah',
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
