<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mapping_kelas_matakuliahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_matakuliah')->constrained('matakuliahs')->onDelete('cascade');
            $table->foreignId('id_tahun_ajaran')->constrained('tahun_ajarans')->onDelete('cascade');
            $table->string('nama_kelas');
            $table->integer('kuota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping_kelas_matakuliahs');
    }
};
