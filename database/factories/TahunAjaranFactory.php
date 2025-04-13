<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TahunAjaran>
 */
class TahunAjaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tahun_ajaran = $this->faker->year();

        return [
            'tahun_ajaran' => $tahun_ajaran . '/' . ($tahun_ajaran + 1),
            'semester' => $this->faker->randomElement(['ganjil', 'genap']),
        ];
    }
}
