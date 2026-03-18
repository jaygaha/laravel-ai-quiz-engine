<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'exam_id' => Exam::factory()->published(),
            'user_id' => User::factory()->student(),
            'answers' => null,
            'score' => null,
            'started_at' => $startedAt,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $attributes['started_at'] ?? now()->subHour();

            return [
                // answers are provided by the caller (seeder or test) so they
                // can be keyed to real question IDs; null means not yet answered.
                'answers' => $attributes['answers'] ?? null,
                'score' => $attributes['score'] ?? fake()->numberBetween(0, 100),
                'completed_at' => $startedAt instanceof \DateTime
                    ? (clone $startedAt)->modify('+30 minutes')
                    : now(),
            ];
        });
    }
}
