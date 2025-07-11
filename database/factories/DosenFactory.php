<?php

namespace Database\Factories;

use App\Models\KelompokKeahlian;
use App\Models\JabatanStruktural;
use Illuminate\Database\Eloquent\Factories\Factory;

class DosenFactory extends Factory
{
    public function definition(): array
    {
        $kode = strtoupper($this->faker->lexify('???'));

        // Pastikan JabatanStruktural ada sebelum mencoba mengambil ID-nya
        // Jika JabatanStruktural::first() bisa null, maka tambahkan null coalescing operator
        $idJabatanStruktural = $this->faker->boolean(70)
                               ? (JabatanStruktural::inRandomOrder()->first()->id ?? null) // <-- Tambahkan ?? null di sini
                               : null;

        // Jika kolom id_jabatan_struktural di tabel dosens adalah NOT NULL,
        // maka Anda tidak boleh mengassign null. Dalam kasus itu,
        // Anda harus memastikan JabatanStruktural selalu ada.
        // Pilihan lain untuk NOT NULL:
        // 'id_jabatan_struktural' => JabatanStruktural::factory(), // Ini akan membuat JabatanStruktural baru jika belum ada

        return [
            'name' => $this->faker->name(),
            'lecturer_code' => $kode,
            'nip' => $this->faker->unique()->numerify('##############'),
            'nidn' => $this->faker->unique()->numerify('##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'jabatan_fungsional_akademik' => $this->faker->randomElement([
                'lektor', 'asissten ahli', 'guru besar', 'lektor kepala', 'NJAD'
            ]),
            'status_pegawai' => $this->faker->randomElement([
                'Dosen LB', 'Dosen Perbantuan Kopertis', 'Dosen Perbantuan Telkom',
                'Dosen Profesional (full time)', 'Dosen Profesional (part time)', 'Pegawai Tetap'
            ]),
            'pendidikan_terakhir' => $this->faker->randomElement(['S-1', 'S-2', 'S-3']),
            // Pastikan KelompokKeahlian::first() tidak mengembalikan null jika digunakan
            'id_kelompok_keahlian' => KelompokKeahlian::inRandomOrder()->first()->id,
            'id_jabatan_struktural' => $idJabatanStruktural,
        ];
    }
}
