<?php

namespace App\Ai;

use Laravel\Ai\Enums\Lab;

class ResolvedProviders
{
    /**
     * Return only providers that have an API key configured.
     * Order: Anthropic → Gemini → OpenAI → Ollama (local dev).
     *
     * @return array<int, Lab>
     */
    public static function list(): array
    {
        $providers = [];

        if (config('ai.providers.anthropic.key')) {
            $providers[] = Lab::Anthropic;
        }

        if (config('ai.providers.gemini.key')) {
            $providers[] = Lab::Gemini;
        }

        if (config('ai.providers.openai.key')) {
            $providers[] = Lab::OpenAI;
        }

        if (config('ai.providers.ollama.url')) {
            $providers[] = Lab::Ollama;
        }

        return $providers;
    }
}
