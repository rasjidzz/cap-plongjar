<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelompokKeahlian extends Model
{
    /** @use HasFactory<\Database\Factories\KelompokKeahlianFactory> */
    use HasFactory;
    protected $primaryKey = 'id';

    protected $fillable = [
        'nama',
    ];
    public function dosen()
    {
        return $this->hasMany(Dosen::class, 'id_dosen');
    }
}
