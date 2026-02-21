<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'title' => [
                'ar' => fake()->word,
                'en' => fake()->word,
            ],
            'about' => [
                'ar' => fake()->paragraph,
                'en' => fake()->paragraph,
            ],
            'terms_and_conditions' => [
                'ar' => fake()->paragraph,
                'en' => fake()->paragraph,
            ],
            'banner' => fake()->imageUrl(),
            'registration_closed_date' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
