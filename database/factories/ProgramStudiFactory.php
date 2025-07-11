<?php

namespace Database\Factories;

use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgramStudi>
 */
class ProgramStudiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->unique()->word() . ' ' . $this->faker->randomElement(['Informatika', 'Sistem Informasi', 'Rekayasa Perangkat Lunak', 'Data Sains']), // <-- TAMBAHKAN INI
        ];
    }
}
