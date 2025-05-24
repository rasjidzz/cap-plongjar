<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pic;

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
    public function pic()
    {
        return $this->morphOne(Pic::class, 'picable');
    }
    protected static function booted()
    {
        static::created(function ($kk) {
            Pic::create([
                'name' => $kk->nama,
                'picable_id' => $kk->id,
                'picable_type' => self::class,
            ]);
        });
    }
    public function getRoleableTypeAttribute(): string
    {
        return self::class;
    }

    public function getUnitTypeAttribute(): string
    {
        return 'kelompok_keahlian';
    }
    protected $appends = ['roleable_type', 'unit_type'];
}
