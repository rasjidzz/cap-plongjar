<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matakuliah extends Model
{
    protected $table = 'matakuliahs'; // Menyebutkan tabel yang digunakan

    // Menentukan kolom yang bisa diisi
    protected $fillable = [
        'kode_matkul',
        'sks',
        'praktikum',
        'id_pic',
        'mandatory_status',
        'mode_perkuliahan'
    ];

    // Relasi dengan model Pic
    public function pic()
    {
        return $this->belongsTo(Pic::class, 'id_pic');
    }
}
