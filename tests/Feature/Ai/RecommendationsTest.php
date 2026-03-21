<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

it('returns empty recommendations when attempt has no incorrect answers', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();
    $question = Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->completed()->for($exam)->for($student, 'student')->create([
        'answers' => [
            $question->id => ['value' => 'Paris', 'flagged' => false],
        ],
        'score' => 100,
    ]);

    $component = Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt]);

    expect($component->instance()->recommendations)->toBeEmpty();
});

it('returns questions from other exams for incorrect answers', function () {
    $student = User::factory()->student()->create();
    $exam1 = Exam::factory()->published()->create();
    $exam2 = Exam::factory()->published()->create();

    $q1 = Question::factory()->for($exam1)->create([
        'question' => 'What is the capital of France?',
        'correct_answer' => 'Paris',
    ]);

    Question::factory()->for($exam2)->create([
        'question' => 'Name the capital city of France',
        'embedding' => array_fill(0, 1536, 0.1),
    ]);

    $attempt = Attempt::factory()->completed()->for($exam1)->for($student, 'student')->create([
        'answers' => [
            $q1->id => ['value' => 'London', 'flagged' => false],
        ],
        'score' => 0,
    ]);

    $component = Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt]);

    $recommendations = $component->instance()->recommendations;
    expect($recommendations->pluck('exam_id'))->not->toContain($exam1->id);
});

it('excludes same-exam questions from recommendations', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    $q1 = Question::factory()->for($exam)->create([
        'correct_answer' => 'Paris',
        'embedding' => array_fill(0, 1536, 0.1),
    ]);

    $attempt = Attempt::factory()->completed()->for($exam)->for($student, 'student')->create([
        'answers' => [
            $q1->id => ['value' => 'London', 'flagged' => false],
        ],
        'score' => 0,
    ]);

    $component = Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt]);

    $recommendations = $component->instance()->recommendations;
    expect($recommendations->pluck('exam_id'))->not->toContain($exam->id);
});

it('shows struggled topics for teacher with lowest correct rates', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher, 'teacher')->create();

    $q1 = Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);
    $q2 = Question::factory()->for($exam)->create(['correct_answer' => 'Berlin']);

    foreach (range(1, 2) as $i) {
        $student = User::factory()->student()->create();
        Attempt::factory()->completed()->for($exam)->for($student, 'student')->create([
            'answers' => [
                $q1->id => ['value' => 'Wrong', 'flagged' => false],
                $q2->id => ['value' => 'Berlin', 'flagged' => false],
            ],
            'score' => 50,
        ]);
    }

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.results', ['exam' => $exam]);

    $topics = $component->instance()->struggledTopics;
    expect($topics)->toHaveCount(2)
        ->and($topics->first()['correct_rate'])->toBe(0)
        ->and($topics->last()['correct_rate'])->toBe(100);
});
