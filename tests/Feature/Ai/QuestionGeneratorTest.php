<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Enums\QuestionType;
use App\Jobs\ProcessGeneratedQuestionsJob;
use App\Models\Exam;
use App\Models\User;
use Livewire\Livewire;

it('generates questions synchronously for 5 or fewer and stores as pending', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                [
                    'question' => 'What is 2 + 2?',
                    'type' => QuestionType::MultipleChoice->value,
                    'options' => ['3', '4', '5', '6'],
                    'correct_answer' => '4',
                    'explanation' => 'Basic addition.',
                    'difficulty' => 1,
                ],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'Basic Math')
        ->set('aiType', QuestionType::MultipleChoice->value)
        ->set('aiCount', 1)
        ->set('aiDifficulty', 1)
        ->call('streamGenerateWithAi')
        ->assertSet('aiError', '')
        ->assertSet('aiGenerating', false)
        ->assertCount('pendingAiQuestions', 1);
});

it('queues generation and does not add pending questions when count exceeds 5', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'Science')
        ->set('aiType', QuestionType::ShortAnswer->value)
        ->set('aiCount', 6)
        ->set('aiDifficulty', 2)
        ->call('generateWithAi')
        ->assertCount('pendingAiQuestions', 0);

    QuestionGeneratorAgent::assertQueued(fn ($prompt) => str_contains($prompt->prompt, 'Science'));
});

it('can confirm a single pending ai question to save it to the exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                [
                    'question' => 'What is Laravel?',
                    'type' => QuestionType::ShortAnswer->value,
                    'options' => [],
                    'correct_answer' => 'A PHP framework',
                    'explanation' => 'Laravel is a PHP framework.',
                    'difficulty' => 2,
                ],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'Laravel')
        ->set('aiType', QuestionType::ShortAnswer->value)
        ->set('aiCount', 1)
        ->set('aiDifficulty', 2)
        ->call('streamGenerateWithAi')
        ->call('confirmAiQuestion', 0)
        ->assertCount('pendingAiQuestions', 0);

    expect($exam->fresh()->questions->count())->toBe(1);
});

it('can confirm all pending ai questions at once', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'Q1?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A1', 'explanation' => '', 'difficulty' => 1],
                ['question' => 'Q2?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A2', 'explanation' => '', 'difficulty' => 1],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'History')
        ->set('aiType', QuestionType::ShortAnswer->value)
        ->set('aiCount', 2)
        ->set('aiDifficulty', 1)
        ->call('streamGenerateWithAi')
        ->call('confirmAllAiQuestions')
        ->assertCount('pendingAiQuestions', 0);

    expect($exam->fresh()->questions->count())->toBe(2);
});

it('ProcessGeneratedQuestionsJob creates questions for the exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();

    $questions = [
        ['question' => 'What is PHP?', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'A scripting language'],
        ['question' => 'Pick one', 'type' => QuestionType::MultipleChoice->value, 'options' => ['A', 'B'], 'correct_answer' => 'A'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    expect($exam->fresh()->questions->count())->toBe(2);
});

it('ProcessGeneratedQuestionsJob skips incomplete question entries', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();

    $questions = [
        ['question' => 'Valid?', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'Yes'],
        ['question' => 'Missing answer', 'type' => QuestionType::ShortAnswer->value],
        ['type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'No question text'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    expect($exam->fresh()->questions->count())->toBe(1);
});
