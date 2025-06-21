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
                'id_pic' => 3,
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
                'id_pic' => 4,
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
                'id_pic' => 4,
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
                'id_pic' => 5,
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
                'id_pic' => 5,
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
                'id_pic' => 6,
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
                'id_pic' => 6,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'hybrid',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 4',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF2150'],
            [
                'nama_matakuliah' => 'Struktur Diskret',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => false,
                'id_pic' => 7,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 2',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF3051'],
            [
                'nama_matakuliah' => 'Manajemen Proyek Perangkat Lunak',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => false,
                'id_pic' => 7,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'hybrid',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 3',
            ]
        );

        $sks = 2;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF3010'],
            [
                'nama_matakuliah' => 'Etika Profesi TI',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => false,
                'id_pic' => 8,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 3',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF3132'],
            [
                'nama_matakuliah' => 'Pemrosesan Data Terdistribusi',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 8,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'online',
                'matakuliah_eksepsi' => 'ya',
                'tingkat_matakuliah' => 'Tingkat 3',
            ]
        );

        $sks = 4;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF2290'],
            [
                'nama_matakuliah' => 'Metode Numerik',
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
            ['kode_matkul' => 'IF3102'],
            [
                'nama_matakuliah' => 'Pemrograman Mobile',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 3,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'hybrid',
                'matakuliah_eksepsi' => 'ya',
                'tingkat_matakuliah' => 'Tingkat 3',
            ]
        );

        $sks = 2;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF2062'],
            [
                'nama_matakuliah' => 'Komunikasi Data',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => false,
                'id_pic' => 4,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 2',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF4020'],
            [
                'nama_matakuliah' => 'Machine Learning',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 5,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'online',
                'matakuliah_eksepsi' => 'ya',
                'tingkat_matakuliah' => 'Tingkat 4',
            ]
        );

        $sks = 3;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF2081'],
            [
                'nama_matakuliah' => 'Interaksi Manusia dan Komputer',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 6,
                'mandatory_status' => 'wajib_prodi',
                'mode_perkuliahan' => 'onsite',
                'matakuliah_eksepsi' => 'tidak',
                'tingkat_matakuliah' => 'Tingkat 2',
            ]
        );

        $sks = 2;
        Matakuliah::updateOrCreate(
            ['kode_matkul' => 'IF4091'],
            [
                'nama_matakuliah' => 'Teknologi Cloud Computing',
                'sks' => $sks,
                'hour_target' => $sks * 16,
                'praktikum' => true,
                'id_pic' => 7,
                'mandatory_status' => 'pilihan',
                'mode_perkuliahan' => 'online',
                'matakuliah_eksepsi' => 'ya',
                'tingkat_matakuliah' => 'Tingkat 4',
            ]
        );
    }
}
