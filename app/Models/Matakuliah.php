<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matakuliah extends Model
{
    protected $table = 'matakuliahs'; // Menyebutkan tabel yang digunakan

    // Menentukan kolom yang bisa diisi
    protected $fillable = [
        'nama_matakuliah',
        'kode_matkul',
        'sks',
        'hour_target',
        'praktikum',
        'id_pic',
        'mandatory_status',
        'mode_perkuliahan',
        'matakuliah_eksepsi',
        'matakuliah_eksepsi'
    ];

    // Relasi dengan model Pic
    public function pic()
    {
        return $this->belongsTo(Pic::class, 'id_pic');
    }
}
