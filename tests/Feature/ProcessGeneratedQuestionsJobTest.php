<?php

use App\Enums\QuestionType;
use App\Jobs\ProcessGeneratedQuestionsJob;
use App\Models\Exam;
use App\Models\User;

it('creates questions from valid data', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    $questions = [
        ['question' => 'What is PHP?', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'A scripting language'],
        ['question' => 'Pick one', 'type' => QuestionType::MultipleChoice->value, 'options' => ['A', 'B', 'C'], 'correct_answer' => 'A'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    expect($exam->fresh()->questions)->toHaveCount(2);
});

it('skips entries missing the question field', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    $questions = [
        ['question' => 'Valid?', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'Yes'],
        ['type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'No question text'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    expect($exam->fresh()->questions)->toHaveCount(1);
});

it('skips entries missing the correct_answer field', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    $questions = [
        ['question' => 'Missing answer', 'type' => QuestionType::ShortAnswer->value],
        ['question' => 'Has answer', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'Yes'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    expect($exam->fresh()->questions)->toHaveCount(1)
        ->and($exam->fresh()->questions->first()->question)->toBe('Has answer');
});

it('defaults to ShortAnswer when type is invalid or missing', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    $questions = [
        ['question' => 'No type', 'correct_answer' => 'Answer'],
        ['question' => 'Bad type', 'type' => 'invalid_type', 'correct_answer' => 'Answer'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    $created = $exam->fresh()->questions;

    expect($created)->toHaveCount(2)
        ->and($created[0]->type)->toBe(QuestionType::ShortAnswer)
        ->and($created[1]->type)->toBe(QuestionType::ShortAnswer);
});

it('assigns sequential order values starting after existing questions', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    $exam->questions()->create([
        'question' => 'Existing',
        'type' => QuestionType::ShortAnswer->value,
        'correct_answer' => 'Yes',
        'order' => 3,
    ]);

    $questions = [
        ['question' => 'New Q1', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'A1'],
        ['question' => 'New Q2', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => 'A2'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    $orders = $exam->fresh()->questions->sortBy('order')->pluck('order')->values()->all();

    expect($orders)->toBe([3, 4, 5]);
});

it('stores options for multiple choice questions', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    $questions = [
        [
            'question' => 'Capital of France?',
            'type' => QuestionType::MultipleChoice->value,
            'options' => ['Paris', 'London', 'Berlin', 'Rome'],
            'correct_answer' => 'Paris',
        ],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    $q = $exam->fresh()->questions->first();

    expect($q->options)->toBe(['Paris', 'London', 'Berlin', 'Rome'])
        ->and($q->correct_answer)->toBe('Paris');
});

it('sets options to null for short answer questions', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    $questions = [
        ['question' => 'What is 2+2?', 'type' => QuestionType::ShortAnswer->value, 'correct_answer' => '4'],
    ];

    (new ProcessGeneratedQuestionsJob($exam->id, $questions))->handle();

    expect($exam->fresh()->questions->first()->options)->toBeNull();
});

it('handles an empty questions array gracefully', function () {
    $exam = Exam::factory()->for(User::factory()->teacher())->create();

    (new ProcessGeneratedQuestionsJob($exam->id, []))->handle();

    expect($exam->fresh()->questions)->toHaveCount(0);
});
