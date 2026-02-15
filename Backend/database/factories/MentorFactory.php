<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Path;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mentor>
 */
class MentorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $competitionId = Competition::first()->id;
        $pathId = Path::first()->id;

        return [
            'track_id' => $pathId,
            'competition_id' => $competitionId,
            'name' => [
                'en' => $this->faker->name,
                'id' => $this->faker->name,
            ],
            'experience' => [
                'en' => $this->faker->sentence,
                'id' => $this->faker->sentence,
            ],
            'brief' => [
                'en' => $this->faker->paragraph,
                'id' => $this->faker->paragraph,
            ],
            'image' => $this->faker->imageUrl,
            'link' => $this->faker->url,
        ];
    }
}
