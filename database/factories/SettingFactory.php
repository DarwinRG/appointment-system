<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bname' => 'Appointment System',
            'email' => 'admin@darwinrg.me',
            'phone' => '09762640347',
            'meta_title' => 'Appointment System',
        ];
    }
}
