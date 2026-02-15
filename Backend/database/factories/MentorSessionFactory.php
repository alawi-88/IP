<?php

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\Participant;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MentorSession>
 */
class MentorSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];
        $videoTools = ['zoom', 'teams', 'google_meet'];
        
        $scheduledAt = $this->faker->dateTimeBetween('now', '+1 month');
        $durationMinutes = $this->faker->randomElement([30, 60, 90, 120]);
        
        return [
            'mentor_id' => Mentor::factory(),
            'participant_id' => Participant::factory(),
            'competition_id' => Competition::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'status' => $this->faker->randomElement($statuses),
            'video_tool' => $this->faker->randomElement($videoTools),
            'meeting_id' => $this->faker->uuid(),
            'join_url' => $this->faker->url(),
            'password' => $this->faker->optional(0.3)->password(8, 12), // 30% chance of having password
            'calendar_event_id' => [
                'primary' => $this->faker->uuid(),
            ],
            'notes' => $this->faker->optional(0.4)->paragraph(), // 40% chance of having notes
            'feedback' => $this->faker->optional(0.3)->paragraph(), // 30% chance of having feedback
            'rating' => $this->faker->optional(0.3)->numberBetween(1, 5), // 30% chance of having rating
            'started_at' => null,
            'ended_at' => null,
        ];
    }

    /**
     * Indicate that the session is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'started_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the session is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'started_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the session is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'scheduled_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'started_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the session is completed.
     */
    public function completed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-2 hours', '-1 hour');
        $durationMinutes = $this->faker->randomElement([30, 60, 90]);
        
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'scheduled_at' => $startedAt,
            'duration_minutes' => $durationMinutes,
            'started_at' => $startedAt,
            'ended_at' => $startedAt->modify("+{$durationMinutes} minutes"),
            'feedback' => $this->faker->paragraph(),
            'rating' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the session is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'started_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the session is no show.
     */
    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_show',
            'scheduled_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'started_at' => null,
            'ended_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the session uses Zoom.
     */
    public function zoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_tool' => 'zoom',
            'meeting_id' => $this->faker->numerify('##########'),
            'join_url' => 'https://zoom.us/j/' . $this->faker->numerify('##########'),
            'password' => $this->faker->optional(0.5)->password(6, 8),
        ]);
    }

    /**
     * Indicate that the session uses Teams.
     */
    public function teams(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_tool' => 'teams',
            'meeting_id' => $this->faker->uuid(),
            'join_url' => 'https://teams.microsoft.com/l/meetup-join/' . $this->faker->uuid(),
            'password' => null, // Teams doesn't use passwords
        ]);
    }

    /**
     * Indicate that the session uses Google Meet.
     */
    public function googleMeet(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_tool' => 'google_meet',
            'meeting_id' => $this->faker->uuid(),
            'join_url' => 'https://meet.google.com/' . $this->faker->bothify('???-????-???'),
            'password' => null, // Google Meet doesn't use passwords
        ]);
    }

    /**
     * Indicate that the session is upcoming (scheduled or confirmed).
     */
    public function upcoming(): static
    {
        $status = $this->faker->randomElement(['scheduled', 'confirmed']);
        
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'started_at' => null,
            'ended_at' => null,
        ]);
    }
}
