<?php

namespace Database\Factories;

use App\Models\MappingKelasMatakuliah;
use App\Models\Matakuliah; // Import Matakuliah model
use App\Models\TahunAjaran; // Import TahunAjaran model
use App\Models\ProgramStudi; // Import ProgramStudi model
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MappingKelasMatakuliah>
 */
class MappingKelasMatakuliahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Pastikan Anda memiliki factory yang berfungsi untuk model-model ini
            // atau Anda membuat instance mereka di setUp() test Anda.
            'id_matakuliah' => Matakuliah::factory(),
            'id_tahun_ajaran' => TahunAjaran::factory(),
            'id_program_studi' => ProgramStudi::factory(), // Atau ProgramStudi::first()->id jika Anda membuatnya di setUp()
            'nama_kelas' => $this->faker->randomLetter() . $this->faker->numberBetween(1, 10), // Contoh: 'A1', 'B5'
            'kuota' => $this->faker->numberBetween(20, 100),
            'team_teaching' => $this->faker->boolean,
        ];
    }
}
