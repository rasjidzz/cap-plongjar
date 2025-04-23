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
        Schema::create('dosens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('lecturer_code', 3);
            $table->string('nip')->unique();
            $table->string('nidn')->nullable();
            $table->string('email')->unique();
            $table->enum('jabatan_fungsional_akademik', [
                'lektor',
                'asissten ahli',
                'guru besar',
                'lektor kepala',
                'NJAD'
            ]);
            $table->string('jabatan_struktural')->nullable();
            $table->enum('status_pegawai', [
                'Dosen Perbantuan Kopertis',
                'Dosen Perbantuan Telkom',
                'Dosen Profesional (full time)',
                'Dosen Profesional (part time)',
                'Pegawai Tetap'
            ]);
            $table->enum('pendidikan_terakhir', [
                'SMA',
                'S-1',
                'S-2',
                'S-3'
            ]);
            $table->unsignedBigInteger('id_kelompok_keahlian');
            $table->timestamps();

            $table->foreign('id_kelompok_keahlian')->references('id')->on('kelompok_keahlians')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosens');
    }
};
