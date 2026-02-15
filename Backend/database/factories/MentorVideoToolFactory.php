<?php

namespace Database\Factories;

use App\Models\Mentor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MentorVideoTool>
 */
class MentorVideoToolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $toolTypes = ['zoom', 'teams', 'google_meet'];
        $toolType = $this->faker->randomElement($toolTypes);

        return [
            'mentor_id' => Mentor::factory(),
            'tool_type' => $toolType,
            'account_id' => $this->faker->uuid(),
            'account_email' => $this->faker->email(),
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'tool_settings' => [
                'timezone' => $this->faker->timezone(),
                'language' => $this->faker->randomElement(['en', 'ar']),
            ],
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'is_default' => false,
            'last_sync_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the video tool is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the video tool is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the video tool is for Zoom.
     */
    public function zoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'tool_type' => 'zoom',
        ]);
    }

    /**
     * Indicate that the video tool is for Teams.
     */
    public function teams(): static
    {
        return $this->state(fn (array $attributes) => [
            'tool_type' => 'teams',
        ]);
    }

    /**
     * Indicate that the video tool is for Google Meet.
     */
    public function googleMeet(): static
    {
        return $this->state(fn (array $attributes) => [
            'tool_type' => 'google_meet',
        ]);
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }
}
