<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $options = [
            fake()->sentence(3),
            fake()->sentence(3),
            fake()->sentence(3),
            fake()->sentence(3),
        ];

        return [
            'exam_id' => Exam::factory(),
            'question' => fake()->sentence(8).'?',
            'type' => QuestionType::MultipleChoice,
            'options' => $options,
            'correct_answer' => $options[0],
            'order' => fake()->numberBetween(0, 20),
        ];
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::TrueFalse,
            'options' => ['True', 'False'],
            'correct_answer' => fake()->randomElement(['True', 'False']),
        ]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::ShortAnswer,
            'options' => null,
            'correct_answer' => fake()->word(),
        ]);
    }
}
