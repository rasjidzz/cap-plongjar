<?php

namespace Database\Factories;

use App\Models\Matakuliah;
use App\Models\Pic;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatakuliahFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Matakuliah::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $sks = $this->faker->numberBetween(1, 6); // Dapatkan SKS terlebih dahulu
        $hourTarget = $sks * 16; // Hitung hour_target berdasarkan SKS

        return [
            'nama_matakuliah' => $this->faker->sentence(3),
            'kode_matkul' => $this->faker->unique()->regexify('[A-Z0-9]{5}'),
            'sks' => $sks, // Gunakan SKS yang sudah dihitung
            'praktikum' => $this->faker->boolean,
            'id_pic' => Pic::factory(),
            'mandatory_status' => $this->faker->randomElement(['wajib_prodi', 'pilihan']),
            'mode_perkuliahan' => $this->faker->randomElement(['online', 'onsite', 'hybrid']),
            'matakuliah_eksepsi' => $this->faker->randomElement(['ya', 'tidak']),
            'tingkat_matakuliah' => $this->faker->randomElement(['Tingkat 1', 'Tingkat 2', 'Tingkat 3', 'Tingkat 4']),
            'hour_target' => $hourTarget, // <-- TAMBAHKAN INI
        ];
    }
}
