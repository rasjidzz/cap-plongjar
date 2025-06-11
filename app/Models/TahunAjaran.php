<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahunAjaran extends Model
{
    /** @use HasFactory<\Database\Factories\TahunAjaranFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = ['tahun_ajaran', 'semester', 'active'];
}
