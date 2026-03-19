<?php

namespace App\Ai\Agents;

use App\Enums\QuestionType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-4-6')]
#[MaxTokens(2048)]
#[Temperature(0.7)]
class QuestionGeneratorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly string $topic,
        private readonly string $type,
        private readonly int $count,
        private readonly int $difficulty,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $typeLabel = QuestionType::from($this->type)->label();
        $difficultyLabel = match ($this->difficulty) {
            1 => 'very easy (beginner)',
            2 => 'easy',
            3 => 'medium',
            4 => 'hard',
            5 => 'very hard (expert)',
            default => 'medium',
        };

        $optionsNote = QuestionType::from($this->type)->hasOptions()
            ? 'Each question must include an "options" array of 4 distinct answer choices. For True/False questions, options must be exactly ["True", "False"].'
            : 'Leave "options" as an empty array [] since this is a short answer question.';

        return <<<INSTRUCTIONS
        You are an expert exam author creating high-quality assessment questions.

        Generate exactly {$this->count} {$typeLabel} question(s) about the topic: "{$this->topic}".
        Difficulty level: {$difficultyLabel}.

        Rules:
        - Each question must be clear, unambiguous, and test genuine understanding.
        - The correct_answer must match one of the options exactly (case-sensitive) for multiple choice and true/false.
        - Never embed the correct answer in the question text itself.
        - The explanation should clarify why the correct answer is right.
        - Difficulty must be {$this->difficulty} (1=easiest, 5=hardest).
        - {$optionsNote}
        - Respond ONLY with the JSON schema. No extra text.
        INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'questions' => $schema->array()
                ->items(
                    $schema->object([
                        'question' => $schema->string()->required(),
                        'type' => $schema->string()->required(),
                        'options' => $schema->array()->items($schema->string()),
                        'correct_answer' => $schema->string()->required(),
                        'explanation' => $schema->string()->required(),
                        'difficulty' => $schema->integer()->min(1)->max(5)->required(),
                    ])
                )
                ->required(),
        ];
    }
}
