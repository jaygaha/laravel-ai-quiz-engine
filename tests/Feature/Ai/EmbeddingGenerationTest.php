<?php

use App\Jobs\GenerateAttemptEmbeddingJob;
use App\Jobs\GenerateQuestionEmbeddingJob;
use App\Models\Attempt;
use App\Models\Question;
use Illuminate\Support\Facades\Queue;

it('stores embedding on question via GenerateQuestionEmbeddingJob', function () {
    Queue::fake();

    $question = Question::factory()->create();

    Queue::assertPushed(GenerateQuestionEmbeddingJob::class);

    expect($question->embedding)->toBeNull();

    // Run the job directly with Embeddings already faked globally
    (new GenerateQuestionEmbeddingJob($question))->handle();

    $question->refresh();

    expect($question->embedding)->toBeArray()
        ->and($question->embedding)->toHaveCount(1536);
});

it('dispatches GenerateQuestionEmbeddingJob on question create', function () {
    Queue::fake();

    Question::factory()->create();

    Queue::assertPushed(GenerateQuestionEmbeddingJob::class);
});

it('dispatches GenerateQuestionEmbeddingJob on question text update', function () {
    Queue::fake();

    $question = Question::factory()->create();

    Queue::assertPushedTimes(GenerateQuestionEmbeddingJob::class, 1);

    $question->update(['question' => 'Updated question text']);

    Queue::assertPushedTimes(GenerateQuestionEmbeddingJob::class, 2);
});

it('does not dispatch embedding job when only order changes', function () {
    Queue::fake();

    $question = Question::factory()->create();

    Queue::assertPushedTimes(GenerateQuestionEmbeddingJob::class, 1);

    $question->update(['order' => 99]);

    Queue::assertPushedTimes(GenerateQuestionEmbeddingJob::class, 1);
});

it('stores embedding on attempt via GenerateAttemptEmbeddingJob', function () {
    $attempt = Attempt::factory()->create([
        'answers' => [
            1 => ['value' => 'Photosynthesis', 'flagged' => false],
            2 => ['value' => 'Mitochondria', 'flagged' => false],
        ],
    ]);

    (new GenerateAttemptEmbeddingJob($attempt))->handle();

    $attempt->refresh();

    expect($attempt->embedding)->toBeArray()
        ->and($attempt->embedding)->toHaveCount(1536);
});

it('dispatches backfill jobs for questions without embeddings', function () {
    Queue::fake();

    // Create 3 questions (observer dispatches create jobs, but Queue is faked)
    Question::factory()->count(3)->create();

    // Clear the queue fake to reset counts
    Queue::fake();

    $this->artisan('questions:backfill-embeddings')
        ->expectsOutputToContain('Dispatched embedding jobs for 3 questions')
        ->assertSuccessful();

    Queue::assertPushedTimes(GenerateQuestionEmbeddingJob::class, 3);
});
