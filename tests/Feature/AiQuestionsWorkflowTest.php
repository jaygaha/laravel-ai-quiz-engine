<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\User;
use Livewire\Livewire;

// ── discardAiQuestion ────────────────────────────────────────────────────────

it('discards a single pending AI question by index', function () {
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
        ->set('aiTopic', 'Math')
        ->set('aiType', QuestionType::ShortAnswer->value)
        ->set('aiCount', 2)
        ->set('aiDifficulty', 1)
        ->call('streamGenerateWithAi')
        ->assertCount('pendingAiQuestions', 2)
        ->call('discardAiQuestion', 0)
        ->assertCount('pendingAiQuestions', 1);

    expect($exam->fresh()->questions)->toHaveCount(0);
});

it('discarding reindexes the pending array', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'Q1?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A1', 'explanation' => '', 'difficulty' => 1],
                ['question' => 'Q2?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A2', 'explanation' => '', 'difficulty' => 1],
                ['question' => 'Q3?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A3', 'explanation' => '', 'difficulty' => 1],
            ],
        ],
    ]);

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'Science')
        ->set('aiType', QuestionType::ShortAnswer->value)
        ->set('aiCount', 3)
        ->set('aiDifficulty', 1)
        ->call('streamGenerateWithAi')
        ->call('discardAiQuestion', 0);

    $pending = $component->get('pendingAiQuestions');
    expect($pending)->toHaveCount(2)
        ->and($pending[0]['question'])->toBe('Q2?')
        ->and($pending[1]['question'])->toBe('Q3?');
});

// ── saveToHistory / loadFromHistory ──────────────────────────────────────────

it('saves generation results to session history', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'History Q?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A', 'explanation' => '', 'difficulty' => 1],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'History')
        ->set('aiType', QuestionType::ShortAnswer->value)
        ->set('aiCount', 1)
        ->set('aiDifficulty', 1)
        ->call('streamGenerateWithAi');

    $history = session("ai_gen_history_{$exam->id}", []);
    expect($history)->toHaveCount(1)
        ->and($history[0]['topic'])->toBe('History')
        ->and($history[0]['questions'])->toHaveCount(1);
});

it('loads questions from session history', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    session(["ai_gen_history_{$exam->id}" => [
        [
            'topic' => 'Geography',
            'type' => QuestionType::MultipleChoice->value,
            'count' => 1,
            'questions' => [
                ['question' => 'Capital of Japan?', 'type' => QuestionType::MultipleChoice->value, 'options' => ['Tokyo', 'Kyoto'], 'correct_answer' => 'Tokyo'],
            ],
            'generated_at' => '14:30',
        ],
    ]]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->call('loadFromHistory', 0)
        ->assertCount('pendingAiQuestions', 1)
        ->assertSet('aiTopic', 'Geography')
        ->assertSet('aiType', QuestionType::MultipleChoice->value);
});

it('loadFromHistory ignores invalid index', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->call('loadFromHistory', 99)
        ->assertCount('pendingAiQuestions', 0);
});

it('session history is capped at 5 entries', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake(array_fill(0, 6, [
        'questions' => [
            ['question' => 'Q?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A', 'explanation' => '', 'difficulty' => 1],
        ],
    ]));

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam]);

    for ($i = 0; $i < 6; $i++) {
        $component
            ->set('aiTopic', "Topic {$i}")
            ->set('aiType', QuestionType::ShortAnswer->value)
            ->set('aiCount', 1)
            ->set('aiDifficulty', 1)
            ->call('streamGenerateWithAi');
    }

    $history = session("ai_gen_history_{$exam->id}", []);
    expect($history)->toHaveCount(5)
        ->and($history[0]['topic'])->toBe('Topic 5');
});
