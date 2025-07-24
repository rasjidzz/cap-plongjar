<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matakuliah extends Model
{
    use SoftDeletes, HasFactory;
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
        'tingkat_matakuliah',
        'created_by',
    ];

    // Relasi dengan model Pic
    public function pic()
    {
        return $this->belongsTo(Pic::class, 'id_pic');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
