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
    public function mappingKelasMatakuliahs()
    {
        return $this->hasMany(MappingKelasMatakuliah::class, 'id_program_studi');
    }
    public function getRoleableTypeAttribute(): string
    {
        return self::class; // menghasilkan "App\Models\ProgramStudi"
    }
    public function getUnitTypeAttribute(): string
    {
        return 'program_studi';
    }
    protected $appends = ['roleable_type', 'unit_type'];
}
