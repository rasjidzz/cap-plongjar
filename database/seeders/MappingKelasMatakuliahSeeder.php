<?php

namespace Database\Seeders;

use App\Models\MappingKelasMatakuliah;
use App\Models\Matakuliah;
use App\Models\TahunAjaran;
use App\Models\ProgramStudi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MappingKelasMatakuliahSeeder extends Seeder
{
    public function run(): void
    {
        $matakuliahIds = Matakuliah::orderBy('id')->take(10)->pluck('id')->toArray();

        $tahunAjaran = TahunAjaran::find(1);

        $progamStudi = ProgramStudi::find(2);

        $classNames = [];
        for ($i = 1; $i <= 3; $i++) {
            $classNames[] = 'SE-45-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        $defaultKuota = 40; // Kuota default untuk setiap kelas
        $tahunAjaranId = $tahunAjaran->id; // Dapatkan ID dari objek TahunAjaran
        $programStudiId = $progamStudi->id;

        foreach ($matakuliahIds as $matakuliahId) {
            foreach ($classNames as $className) {
                $isTeamTeaching = mt_rand(0, 1);

                MappingKelasMatakuliah::updateOrCreate(
                    [
                        'id_matakuliah' => $matakuliahId,
                        'id_tahun_ajaran' => $tahunAjaranId, // Hanya menggunakan Tahun Ajaran dengan ID 1
                        'nama_kelas' => $className,
                    ], // Kunci untuk pengecekan duplikasi
                    [
                        'id_program_studi' => $programStudiId,
                        'kuota' => $defaultKuota, // Menggunakan kuota default
                        'team_teaching' => $isTeamTeaching, // Menggunakan kolom 'team_teaching'
                    ] // Data yang akan diisi atau diupdate
                );
            }
        }
        $progamStudi = ProgramStudi::find(1);
        $classNames = [];
        for ($i = 1; $i <= 3; $i++) {
            $classNames[] = 'IF-45-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        $defaultKuota = 40; // Kuota default untuk setiap kelas
        $tahunAjaranId = $tahunAjaran->id; // Dapatkan ID dari objek TahunAjaran
        $programStudiId = $progamStudi->id;

        foreach ($matakuliahIds as $matakuliahId) {
            foreach ($classNames as $className) {
                $isTeamTeaching = mt_rand(0, 1);

                MappingKelasMatakuliah::updateOrCreate(
                    [
                        'id_matakuliah' => $matakuliahId,
                        'id_tahun_ajaran' => $tahunAjaranId, // Hanya menggunakan Tahun Ajaran dengan ID 1
                        'nama_kelas' => $className,
                    ], // Kunci untuk pengecekan duplikasi
                    [
                        'id_program_studi' => $programStudiId,
                        'kuota' => $defaultKuota, // Menggunakan kuota default
                        'team_teaching' => $isTeamTeaching, // Menggunakan kolom 'team_teaching'
                    ] // Data yang akan diisi atau diupdate
                );
            }
        }
    }
}
