<?php

namespace Database\Seeders;

use App\Models\PlottinganPengajaran;
use App\Models\MappingKelasMatakuliah;
use App\Models\Dosen;
use App\Models\Matakuliah; // Untuk mengakses SKS
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlottinganPengajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil ID Dosen yang tersedia (asumsi ID 1-10)
        $dosenIds = Dosen::whereIn('id', range(1, 10))->pluck('id')->toArray();

        // Ambil MappingKelasMatakuliah yang relevan
        // Asumsi dari MappingKelasMatakuliahSeeder sebelumnya:
        // - Menggunakan id_tahun_ajaran = 1
        // - Untuk 5 matakuliah pertama
        // - Masing-masing matakuliah punya 5 kelas (SE-45-01 s/d SE-45-05)
        $matakuliahUtamaIds = Matakuliah::orderBy('id')->take(5)->pluck('id')->toArray();

        if (empty($dosenIds) || empty($matakuliahUtamaIds)) {
            $this->command->error('Data Dosen atau Matakuliah Utama tidak cukup. PlottinganPengajaranSeeder tidak dapat dijalankan.');
            return;
        }

        $mappingKelasMatakuliahs = MappingKelasMatakuliah::with('matakuliah') // Eager load matakuliah untuk SKS
            ->where('id_tahun_ajaran', 1) // Hanya untuk tahun ajaran dengan ID 1
            ->whereIn('id_matakuliah', $matakuliahUtamaIds)
            ->get();

        if ($mappingKelasMatakuliahs->isEmpty()) {
            $this->command->error('Tidak ada MappingKelasMatakuliah yang ditemukan untuk kriteria yang ditentukan. PlottinganPengajaranSeeder tidak dapat dijalankan.');
            return;
        }

        $this->command->info("Memulai plotting untuk " . $mappingKelasMatakuliahs->count() . " mapping kelas...");

        foreach ($mappingKelasMatakuliahs as $mapping) {
            if (!$mapping->matakuliah) {
                $this->command->warn("Mapping ID {$mapping->id} tidak memiliki relasi matakuliah yang valid, dilewati.");
                continue;
            }

            $sksMatakuliah = $mapping->matakuliah->sks;
            $jumlahDosenUntukPlotting = $mapping->team_teaching == 1 ? 2 : 1;

            // Pastikan jumlah dosen yang akan diplot tidak melebihi jumlah dosen yang tersedia
            if ($jumlahDosenUntukPlotting > count($dosenIds)) {
                $this->command->warn("Mapping ID {$mapping->id} butuh {$jumlahDosenUntukPlotting} dosen, tapi hanya tersedia " . count($dosenIds) . ". Akan diplot maksimal yang tersedia.");
                $jumlahDosenUntukPlotting = count($dosenIds);
            }

            if ($jumlahDosenUntukPlotting === 0 && count($dosenIds) > 0) {
                // Jika team_teaching = 0, dan kita ingin minimal 1 dosen jika ada dosen tersedia
                // Namun, berdasarkan flag team_teaching 0/1, jika team_teaching=0 artinya 1 dosen, jika 1 artinya 2 dosen.
                // Jika team_teaching = 0 menghasilkan 1 dosen, dan team_teaching = 1 menghasilkan 2 dosen, maka ini sudah benar.
            } else if ($jumlahDosenUntukPlotting === 0 && count($dosenIds) === 0) {
                $this->command->warn("Mapping ID {$mapping->id} tidak ada dosen yang bisa diplot karena daftar dosen kosong.");
                continue;
            }


            // Ambil dosen secara acak
            $dosenTerpilihIds = [];
            if (count($dosenIds) >= $jumlahDosenUntukPlotting && $jumlahDosenUntukPlotting > 0) {
                $randomKeys = array_rand($dosenIds, $jumlahDosenUntukPlotting);
                if (is_array($randomKeys)) {
                    foreach ($randomKeys as $key) {
                        $dosenTerpilihIds[] = $dosenIds[$key];
                    }
                } else { // Jika array_rand mengembalikan satu key (bukan array)
                    $dosenTerpilihIds[] = $dosenIds[$randomKeys];
                }
            } elseif (count($dosenIds) < $jumlahDosenUntukPlotting && count($dosenIds) > 0) {
                // Jika dosen yang tersedia kurang dari yang dibutuhkan, ambil semua yang tersedia
                $dosenTerpilihIds = $dosenIds;
                $this->command->warn("Mapping ID {$mapping->id} membutuhkan {$jumlahDosenUntukPlotting} dosen, namun hanya " . count($dosenIds) . " yang tersedia. Semua dosen yang tersedia akan diplot.");
            }


            if (empty($dosenTerpilihIds)) {
                $this->command->warn("Tidak ada dosen terpilih untuk Mapping ID {$mapping->id}, dilewati.");
                continue;
            }

            foreach ($dosenTerpilihIds as $dosenId) {
                // Untuk `beban_sks`, kita akan gunakan SKS penuh dari mata kuliahnya.
                // Jika Anda ingin membagi SKS untuk team teaching, logikanya bisa disesuaikan di sini.
                // Misalnya: $bebanSksPerDosen = $jumlahDosenUntukPlotting > 0 ? round($sksMatakuliah / $jumlahDosenUntukPlotting) : 0;
                $bebanSksPerDosen = $sksMatakuliah;

                PlottinganPengajaran::updateOrCreate(
                    [
                        'id_dosen' => $dosenId,
                        'id_mapping_kelas_matakuliah' => $mapping->id,
                    ],
                    [
                        'beban_sks' => $bebanSksPerDosen,
                    ]
                );
            }
            $this->command->info("Mapping ID {$mapping->id} ({$mapping->matakuliah->nama_matakuliah} - {$mapping->nama_kelas}) diplot ke " . count($dosenTerpilihIds) . " dosen.");
        }

        $this->command->info('PlottinganPengajaranSeeder berhasil dijalankan.');
    }
}
