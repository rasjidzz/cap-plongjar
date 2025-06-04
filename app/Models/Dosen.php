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
    public function koordinatorMatakuliah() // Nama relasi bisa disesuaikan
    {
        return $this->hasMany(KoordinatorMatakuliah::class, 'id_dosen');
    }
    public function getTotalSKS(int $tahun_ajaran)
    {
        $total_sks = $this->plottinganPengajarans()
            ->join('mapping_kelas_matakuliahs', 'plottingan_pengajarans.id_mapping_kelas_matakuliah', '=', 'mapping_kelas_matakuliahs.id')
            ->join('matakuliahs', 'mapping_kelas_matakuliahs.id_matakuliah', '=', 'matakuliahs.id')
            ->where('mapping_kelas_matakuliahs.id_tahun_ajaran', $tahun_ajaran)
            ->sum('matakuliahs.sks');
        return (int) $total_sks;
    }
    public function getTotalSksMengajarPadaTahunAjaran(int $id_tahun_ajaran): int
    {
        $totalSks = $this->plottinganPengajarans() // Memulai dari relasi plottinganPengajarans milik dosen ini
            ->whereHas('mappingKelasMatakuliah', function ($query) use ($id_tahun_ajaran) {
                $query->where('id_tahun_ajaran', $id_tahun_ajaran);
            })
            ->sum('beban_sks'); // Menjumlahkan kolom 'beban_sks' dari hasil plottingan yang sudah terfilter.
        return (int) $totalSks; // Mengembalikan hasil sebagai integer.
    }
}
