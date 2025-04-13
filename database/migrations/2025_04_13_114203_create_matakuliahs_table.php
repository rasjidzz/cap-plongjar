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
            $table->char('kode_matkul', 10);
            $table->integer('sks');
            $table->boolean('praktikum');
            $table->unsignedBigInteger('id_pic');
            $table->enum('mandatory_status', ['wajib_prodi', 'pilihan']);
            $table->enum('mode_perkuliahan', ['online', 'onsite', 'hybrid']);
            $table->timestamps();

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
