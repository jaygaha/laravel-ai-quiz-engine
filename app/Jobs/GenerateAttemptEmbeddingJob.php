<?php

namespace App\Jobs;

use App\Models\Attempt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Embeddings;

class GenerateAttemptEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Attempt $attempt) {}

    public function handle(): void
    {
        $answers = $this->attempt->answers ?? [];

        $text = collect($answers)
            ->pluck('value')
            ->filter()
            ->implode(' ');

        if (empty(trim($text))) {
            return;
        }

        $response = Embeddings::for([$text])->generate();

        $this->attempt->update(['embedding' => $response->embeddings[0]]);
    }
}
