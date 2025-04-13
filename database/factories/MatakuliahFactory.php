<?php

namespace Database\Factories;

use App\Models\Matakuliah;
use App\Models\Pic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matakuliah>
 */
class MatakuliahFactory extends Factory
{
    protected $model = Matakuliah::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'kode_matkul' => $this->faker->unique()->bothify('??###'),
            'sks' => $this->faker->numberBetween(2, 4),
            'praktikum' => $this->faker->boolean,
            'id_pic' => Pic::factory(), // Menghubungkan dengan PicFactory
            'mandatory_status' => $this->faker->randomElement(['wajib_prodi', 'pilihan']),
            'mode_perkuliahan' => $this->faker->randomElement(['online', 'onsite', 'hybrid']),
        ];
    }
}
