<?php

namespace App\Ai\Agents;

use App\Models\Question;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

#[Model('claude-haiku-4-5-20251001')]
#[MaxSteps(3)]
#[MaxTokens(512)]
#[Temperature(0.1)]
class AutoGraderAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        private readonly string $question,
        private readonly string $referenceAnswer,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        You are an objective exam grader evaluating short-answer responses.

        Question: "{$this->question}"
        Reference answer: "{$this->referenceAnswer}"

        Before grading, search for similar questions to calibrate scoring consistency.

        Grading rules:
        - Award full credit (100) for answers that are semantically equivalent to the reference answer, even if worded differently.
        - Award partial credit (50–99) for answers that are partially correct or missing minor details.
        - Award zero (0) for answers that are wrong, irrelevant, or blank.
        - The "is_correct" field should be true only when score >= 50.
        - The "explanation" should briefly explain why the score was awarded.
        - The "suggestion" should guide the student toward the correct answer without revealing it outright.
        - Respond ONLY with the JSON schema. No extra text.
        INSTRUCTIONS;
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(
                model: Question::class,
                column: 'embedding',
                minSimilarity: 0.5,
                limit: 5,
                query: fn ($q) => $q->whereNotNull('embedding'),
            )->withDescription('Search similar exam questions for grading calibration.'),
        ];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'score' => $schema->integer()->min(0)->max(100)->required(),
            'is_correct' => $schema->boolean()->required(),
            'explanation' => $schema->string()->required(),
            'suggestion' => $schema->string()->required(),
        ];
    }
}
