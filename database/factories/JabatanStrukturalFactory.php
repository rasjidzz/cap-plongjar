<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JabatanStruktural>
 */
class JabatanStrukturalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Pastikan Anda mendefinisikan 'nama' dan 'konversi_sks' di sini
            'nama' => $this->faker->unique()->jobTitle(), // Gunakan jobTitle() atau word()
            'konversi_sks' => $this->faker->numberBetween(0, 16), // Sesuaikan rentang SKS yang wajar
        ];
    }
}
