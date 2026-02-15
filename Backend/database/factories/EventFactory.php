<?php

namespace Database\Factories;

use App\Models\Competition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d');

        $competitionId = Competition::first()->id;

        return [
            'competition_id' => $competitionId,
            'title' => [
                'en' => $this->faker->word,
                'ar' => $this->faker->word,
            ],
            'brief' => [
                'en' => $this->faker->sentence,
                'ar' => $this->faker->sentence,
            ],

            'badge' => Carbon::parse($date)->isPast() ? 'completed' : 'upcoming',
            'date' => $date,
            'time' => $this->faker->time,
            'location' => $this->faker->randomElement(['virtual', 'onsite']),
            'speaker_photo' => $this->faker->imageUrl,
            'speaker_name' => [
                'en' => $this->faker->name,
                'ar' => $this->faker->name,
            ],
            'speaker_experience' => [
                'en' => $this->faker->sentence,
                'ar' => $this->faker->sentence,
            ],
            'speaker_brief' => [
                'en' => $this->faker->sentence,
                'ar' => $this->faker->sentence,
            ],
            'event_link' => $this->faker->url,
        ];
    }
}
