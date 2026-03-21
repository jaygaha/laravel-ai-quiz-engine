<?php

use App\Ai\Agents\AutoGraderAgent;
use App\Models\Exam;
use App\Models\Question;
use App\Services\QuestionSimilarityService;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Contracts\HasTools;

it('returns similar questions via QuestionSimilarityService', function () {
    $exam = Exam::factory()->published()->create();

    $questions = Question::factory()->count(3)->for($exam)->create();

    foreach ($questions as $question) {
        $question->update(['embedding' => array_fill(0, 1536, 0.1)]);
    }

    $service = new QuestionSimilarityService;
    $results = $service->findSimilar('sample query', limit: 5);

    expect($results)->toBeInstanceOf(Collection::class);
});

it('excludes specified exam ID from results', function () {
    $exam1 = Exam::factory()->published()->create();
    $exam2 = Exam::factory()->published()->create();

    Question::factory()->for($exam1)->create(['embedding' => array_fill(0, 1536, 0.1)]);
    Question::factory()->for($exam2)->create(['embedding' => array_fill(0, 1536, 0.1)]);

    $service = new QuestionSimilarityService;
    $results = $service->findSimilar('test', limit: 10, excludeExamId: $exam1->id);

    expect($results->pluck('exam_id')->contains($exam1->id))->toBeFalse();
});

it('returns empty collection when no embeddings exist', function () {
    Question::factory()->count(2)->create();

    $service = new QuestionSimilarityService;
    $results = $service->findSimilar('query');

    expect($results)->toBeEmpty();
});

it('AutoGraderAgent implements HasTools', function () {
    $agent = new AutoGraderAgent('What is PHP?', 'A programming language');

    expect($agent)->toBeInstanceOf(HasTools::class);
});

it('AutoGraderAgent grades correctly with RAG tools', function () {
    AutoGraderAgent::fake();

    $response = (new AutoGraderAgent('What is PHP?', 'A programming language'))
        ->prompt('PHP is a server-side scripting language');

    expect($response->structured)->toBeArray()
        ->and($response->structured)->toHaveKeys(['score', 'is_correct', 'explanation', 'suggestion']);

    AutoGraderAgent::assertPrompted(fn ($prompt) => $prompt->contains('PHP is a server-side'));
});
