<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappingKelasMatakuliah extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_matakuliah',
        'id_tahun_ajaran',
        'id_program_studi',
        'nama_kelas',
        'kuota',
        'team_teaching'
    ];

    public function matakuliah()
    {
        return $this->belongsTo(Matakuliah::class, 'id_matakuliah');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran');
    }
    public function plottinganPengajarans()
    {
        return $this->hasMany(PlottinganPengajaran::class, 'id_mapping_kelas_matakuliah');
    }
    public function koordinatorMatakuliah()
    {
        return $this->hasOne(KoordinatorMatakuliah::class, 'id_mapping_kelas_matakuliah', 'id');
    }
    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'id_program_studi');
    }
}
