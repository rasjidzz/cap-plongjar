<?php

namespace Database\Factories;

use App\Models\KoordinatorMatakuliah;
use App\Models\Dosen; // Import Dosen model
use App\Models\MappingKelasMatakuliah; // Import MappingKelasMatakuliah model
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KoordinatorMatakuliah>
 */
class KoordinatorMatakuliahFactory extends Factory
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
            'id_dosen' => Dosen::factory(), // Akan membuat Dosen baru jika tidak ada
            'id_mapping_kelas_matakuliah' => MappingKelasMatakuliah::factory(), // Akan membuat MappingKelasMatakuliah baru jika tidak ada
        ];
    }
}
