<?php

namespace Database\Seeders;

use App\Models\Matakuliah;
use App\Models\Pic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatakuliahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // public function run(): void
    // {
    //     // Membuat 10 data Matakuliah secara manual
    //     Matakuliah::create([
    //         'nama_matakuliah' => 'CONTOH MATAKULIAH SATU',
    //         'kode_matkul' => 'MTK101',
    //         'sks' => 3,
    //         'praktikum' => true,
    //         'id_pic' => 1,
    //         'mandatory_status' => 'wajib_prodi',
    //         'mode_perkuliahan' => 'online',
    //     ]);

    //     Matakuliah::create([
    //         'nama_matakuliah' => 'CONTOH MATAKULIAH DUA',
    //         'kode_matkul' => 'MTK102',
    //         'sks' => 2,
    //         'praktikum' => false,
    //         'id_pic' => 2,
    //         'mandatory_status' => 'pilihan',
    //         'mode_perkuliahan' => 'onsite',
    //     ]);

    //     Matakuliah::create([
    //         'nama_matakuliah' => 'CONTOH MATAKULIAH TIGA',
    //         'kode_matkul' => 'MTK103',
    //         'sks' => 4,
    //         'praktikum' => true,
    //         'id_pic' => 3,
    //         'mandatory_status' => 'wajib_prodi',
    //         'mode_perkuliahan' => 'hybrid',
    //     ]);

    //     Matakuliah::create([
    //         'nama_matakuliah' => 'CONTOH MATAKULIAH EMPAT',
    //         'kode_matkul' => 'MTK104',
    //         'sks' => 3,
    //         'praktikum' => false,
    //         'id_pic' => 4,
    //         'mandatory_status' => 'pilihan',
    //         'mode_perkuliahan' => 'online',
    //     ]);
    // }
    public function run(): void
    {
        $sks = 4;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF2121'], // Kunci untuk pengecekan
            [
                'nama_matakuliah' => 'Algoritma dan Struktur Data',
                'sks' => $sks,
                'hour_target' => $sks * 16, // Asumsi 1 SKS = 16 jam pertemuan (bisa berbeda)
                'praktikum' => true,
                'id_pic' => 2, // Asumsi ID PIC sudah ada dan relevan
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'hybrid',
                'matakuliah_eksepsi' => 'tidak', // Contoh nilai
                'tingkat_matakuliah' => 'Tingkat 2',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF2230'],
            [
                'nama_matakuliah' => 'Sistem Operasi',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 2,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 2',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF3110'],
            [
                'nama_matakuliah' => 'Jaringan Komputer',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 2,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'hybrid',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 3',
            ]
        );

        $sks = 4;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF3240'],
            [
                'nama_matakuliah' => 'Basis Data',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 3,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 3',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF4071'],
            [
                'nama_matakuliah' => 'Kecerdasan Buatan',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 3,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'online',
                'matakuliah_eksepsi' => 'ya', // Contoh 'ya' untuk matkul pilihan/lanjutan
                'tingkat_matakuliah' => 'Tingkat 4',
            ]
        );

        $sks = 4;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF4031'],
            [
                'nama_matakuliah' => 'Rekayasa Perangkat Lunak',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 8,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'hybrid',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 4',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF3270'],
            [
                'nama_matakuliah' => 'Pemrograman Berorientasi Objek',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 8,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 3',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF4081'],
            [
                'nama_matakuliah' => 'Keamanan Informasi',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => false,
                'id_pic' => 8,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'online',
                'matakuliah_eksepsi' => 'ya', // Contoh
                'tingkat_matakuliah' => 'Tingkat 4',
            ]
        );

        $sks = 2;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF2250'],
            [
                'nama_matakuliah' => 'Logika Informatika',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => false,
                'id_pic' => 1,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 2',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF4092'],
            [
                'nama_matakuliah' => 'Pengembangan Aplikasi Web',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 2,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'hybrid',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 4',
            ]
        );
    }
}
