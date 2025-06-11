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
        Schema::create('matakuliahs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_matakuliah');
            $table->char('kode_matkul', 10);
            $table->integer('sks');
            $table->integer('hour_target');
            $table->boolean('praktikum');
            $table->unsignedBigInteger('id_pic');
            $table->enum('mandatory_status', ['wajib_prodi', 'pilihan']);
            $table->enum('mode_perkuliahan', ['online', 'onsite', 'hybrid']);
            $table->enum('matakuliah_eksepsi', ['ya', 'tidak']);
            $table->enum('tingkat_matakuliah', ['Tingkat 1', 'Tingkat 2', 'Tingkat 3', 'Tingkat 4']);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_pic')->references('id')->on('pics')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matakuliahs');
    }
};
