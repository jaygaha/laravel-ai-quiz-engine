<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('claude-haiku-4-5-20251001')]
#[MaxTokens(256)]
#[Temperature(0.5)]
#[Timeout(30)]
class HintAgent implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $question,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        You are a Socratic tutor helping a student think through an exam question.

        Question: "{$this->question}"

        Rules:
        - NEVER reveal the correct answer directly.
        - Guide the student with a single thought-provoking observation or leading question.
        - Keep your response to 1–2 sentences maximum.
        - Be encouraging and constructive.
        - If the student's current answer is on the right track, acknowledge it and nudge further.
        - If the student has no answer yet, provide a conceptual starting point.
        INSTRUCTIONS;
    }
}
