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
        Schema::create('plottingan_pengajarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_dosen')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('id_mapping_kelas_matakuliah')->constrained('mapping_kelas_matakuliahs')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plottingan_pengajarans');
    }
};
