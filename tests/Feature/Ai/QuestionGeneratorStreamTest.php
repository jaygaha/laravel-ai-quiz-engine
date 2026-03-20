<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\User;
use Livewire\Livewire;

it('streamGenerateWithAi populates pendingAiQuestions from faked response', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                [
                    'question' => 'What is the capital of France?',
                    'type' => QuestionType::MultipleChoice->value,
                    'options' => ['Berlin', 'Paris', 'Rome', 'Madrid'],
                    'correct_answer' => 'Paris',
                    'explanation' => 'Paris is the capital city of France.',
                    'difficulty' => 1,
                ],
            ],
        ],
    ]);

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'World Capitals')
        ->set('aiType', QuestionType::MultipleChoice->value)
        ->set('aiCount', 1)
        ->set('aiDifficulty', 1)
        ->call('streamGenerateWithAi');

    expect($component->get('pendingAiQuestions'))->toHaveCount(1)
        ->and($component->get('pendingAiQuestions')[0]['question'])->toBe('What is the capital of France?')
        ->and($component->get('pendingAiQuestions')[0]['correct_answer'])->toBe('Paris');
});

it('streamGenerateWithAi sets aiGenerating to false after completion', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'Q?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A', 'explanation' => '', 'difficulty' => 1],
            ],
        ],
    ]);

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'Science')
        ->set('aiCount', 1)
        ->call('streamGenerateWithAi');

    expect($component->get('aiGenerating'))->toBeFalse();
});

it('streamGenerateWithAi parses multiple questions', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'Q1?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A1', 'explanation' => '', 'difficulty' => 1],
                ['question' => 'Q2?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A2', 'explanation' => '', 'difficulty' => 2],
                ['question' => 'Q3?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A3', 'explanation' => '', 'difficulty' => 3],
            ],
        ],
    ]);

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'History')
        ->set('aiCount', 3)
        ->call('streamGenerateWithAi');

    expect($component->get('pendingAiQuestions'))->toHaveCount(3);
});

it('generation history is stored in session after successful generation', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'What is PHP?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A scripting language', 'explanation' => '', 'difficulty' => 1],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'PHP Basics')
        ->set('aiType', QuestionType::ShortAnswer->value)
        ->set('aiCount', 1)
        ->call('streamGenerateWithAi');

    $history = session("ai_gen_history_{$exam->id}", []);

    expect($history)->toHaveCount(1)
        ->and($history[0]['topic'])->toBe('PHP Basics')
        ->and($history[0]['count'])->toBe(1)
        ->and($history[0]['questions'])->toHaveCount(1);
});

it('loadFromHistory restores pendingAiQuestions from the session', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $savedQuestions = [
        ['question' => 'Saved Q?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'Saved A', 'explanation' => '', 'difficulty' => 1],
    ];

    session(["ai_gen_history_{$exam->id}" => [
        ['topic' => 'Old Topic', 'type' => QuestionType::ShortAnswer->value, 'count' => 1, 'questions' => $savedQuestions, 'generated_at' => '10:00'],
    ]]);

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->call('loadFromHistory', 0);

    expect($component->get('pendingAiQuestions'))->toHaveCount(1)
        ->and($component->get('pendingAiQuestions')[0]['question'])->toBe('Saved Q?')
        ->and($component->get('aiTopic'))->toBe('Old Topic');
});

it('session history is capped at 5 entries', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $existingHistory = array_fill(0, 5, [
        'topic' => 'Old', 'type' => QuestionType::ShortAnswer->value, 'count' => 1, 'questions' => [], 'generated_at' => '09:00',
    ]);
    session(["ai_gen_history_{$exam->id}" => $existingHistory]);

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'New Q?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'New A', 'explanation' => '', 'difficulty' => 1],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'New Topic')
        ->set('aiCount', 1)
        ->call('streamGenerateWithAi');

    $history = session("ai_gen_history_{$exam->id}", []);

    expect($history)->toHaveCount(5)
        ->and($history[0]['topic'])->toBe('New Topic');
});

it('streamGenerateWithAi clears aiError on success', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                ['question' => 'Q?', 'type' => QuestionType::ShortAnswer->value, 'options' => [], 'correct_answer' => 'A', 'explanation' => '', 'difficulty' => 1],
            ],
        ],
    ]);

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiError', 'Previous error')
        ->set('aiTopic', 'Chemistry')
        ->set('aiCount', 1)
        ->call('streamGenerateWithAi');

    expect($component->get('aiError'))->toBe('');
});
