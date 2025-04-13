<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pic extends Model
{
    /** @use HasFactory<\Database\Factories\PicFactory> */
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function matakuliahs()
    {
        return $this->hasMany(Matakuliah::class, 'id_pic');
    }
}
