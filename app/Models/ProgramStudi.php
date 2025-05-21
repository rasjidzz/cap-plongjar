<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramStudiFactory> */
    use HasFactory;
    public function pic()
    {
        return $this->morphOne(Pic::class, 'picable');
    }
    protected static function booted()
    {
        static::created(function ($prodi) {
            Pic::create([
                'name' => $prodi->nama,
                'picable_id' => $prodi->id,
                'picable_type' => self::class
            ]);
        });
    }
}
