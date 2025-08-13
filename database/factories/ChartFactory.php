<?php

namespace Database\Factories;

use App\Models\Chart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chart>
 */
class ChartFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Chart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $trackData = [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->words(3, true),
            'artists' => [
                ['name' => $this->faker->name()]
            ],
            'album' => [
                'name' => $this->faker->words(2, true),
                'images' => [
                    ['url' => $this->faker->imageUrl()],
                    ['url' => $this->faker->imageUrl()],
                    ['url' => $this->faker->imageUrl()],
                ]
            ],
            'popularity' => $this->faker->numberBetween(1, 100),
            'duration_ms' => $this->faker->numberBetween(120000, 300000),
            'external_urls' => [
                'spotify' => $this->faker->url(),
            ],
        ];

        return [
            'user_id' => User::factory(),
            'period' => $this->faker->numberBetween(1, 10),
            'track_spotify_id' => $this->faker->uuid(),
            'track_name' => $this->faker->words(3, true),
            'track_artist' => $this->faker->name(),
            'track_data' => json_encode($trackData),
            'position' => $this->faker->numberBetween(1, 20),
            'last_position' => $this->faker->optional()->numberBetween(1, 20),
            'periods_on_chart' => $this->faker->numberBetween(1, 5),
            'peak_position' => $this->faker->numberBetween(1, 20),
            'is_reentry' => $this->faker->optional()->boolean() ? 1 : null,
        ];
    }

    /**
     * Indicate that the chart entry is for position 1.
     */
    public function numberOne(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 1,
            'peak_position' => 1,
        ]);
    }

    /**
     * Indicate that the chart entry is a reentry.
     */
    public function reentry(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_reentry' => 1,
            'periods_on_chart' => $this->faker->numberBetween(2, 10),
        ]);
    }

    /**
     * Set a specific period for the chart entry.
     */
    public function period(int $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => $period,
        ]);
    }

    /**
     * Set a specific position for the chart entry.
     */
    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
