<?php

namespace Database\Factories;

use App\Models\KelompokKeahlian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dosen>
 */
class DosenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kode = strtoupper($this->faker->lexify('???')); // 3 huruf

        return [
            'name' => $this->faker->name(),
            'lecturer_code' => $kode,
            'email' => $this->faker->unique()->safeEmail(),
            'jabatan_fungsional_akademik' => $this->faker->randomElement(['lektor', 'asissten ahli', 'guru besar', 'lektor kepala', 'NJAD']),
            'status_pegawai' => $this->faker->randomElement([
                'Dosen Perbantuan Kopertis',
                'Dosen Perbantuan Telkom',
                'Dosen Profesional (full time)',
                'Dosen Profesional (part time)',
                'Pegawai Tetap'
            ]),
            'pendidikan_terakhir' => $this->faker->randomElement(['SMA', 'S-1', 'S-2', 'S-3']),
            'nidn' => $this->faker->numerify('##########'),
            'id_kelompok_keahlian' => $this->faker->numberBetween(1, 5),
        ];
    }
}
