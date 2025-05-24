<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JabatanStruktural extends Model
{
    /** @use HasFactory<\Database\Factories\JabatanStrukturalFactory> */
    use HasFactory;
    public function dosen()
    {
        return $this->hasMany(Dosen::class, 'id_jabatan_struktural');
    }
}
