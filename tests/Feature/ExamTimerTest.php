<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

it('timeRemaining returns zero when exam has no time limit', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    Question::factory()->for($exam)->create();

    $component = Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam]);

    expect($component->get('timeRemaining'))->toBe(0);
});

it('timeRemaining returns correct seconds on mount', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => 10]);
    Question::factory()->for($exam)->create();

    // Manually create the attempt so we control started_at
    Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'started_at' => now()->subMinutes(2),
        'completed_at' => null,
    ]);

    $component = Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam]);

    // 10 min limit − 2 min elapsed = ~8 min = ~480 seconds (allow ±5s for test runtime)
    expect($component->get('timeRemaining'))
        ->toBeGreaterThan(474)
        ->toBeLessThanOrEqual(480);
});

it('checkTimer does nothing when time has not expired', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => 10]);
    Question::factory()->for($exam)->create();

    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'started_at' => now()->subMinutes(2),
        'completed_at' => null,
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('checkTimer');

    expect($attempt->fresh()->isCompleted())->toBeFalse();
});

it('checkTimer auto-submits and marks attempt completed when time has expired', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => 10]);
    Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'started_at' => now()->subMinutes(11),
        'completed_at' => null,
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('checkTimer');

    expect($attempt->fresh()->isCompleted())->toBeTrue()
        ->and($attempt->fresh()->completed_at)->not->toBeNull();
});

it('auto-submit produces a valid score and sets completed_at', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => 10]);
    $q1 = Question::factory()->for($exam)->create(['correct_answer' => 'Paris', 'options' => ['Paris', 'London', 'Berlin', 'Rome']]);
    $q2 = Question::factory()->for($exam)->create(['correct_answer' => 'London', 'options' => ['Paris', 'London', 'Berlin', 'Rome']]);

    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'started_at' => now()->subMinutes(11),
        'completed_at' => null,
        'answers' => [$q1->id => 'Paris'], // answered 1 of 2 before time ran out
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('checkTimer');

    $fresh = $attempt->fresh();

    expect($fresh->isCompleted())->toBeTrue()
        ->and($fresh->score)->toBe(50) // 1 correct out of 2 = 50%
        ->and($fresh->completed_at)->not->toBeNull();
});

it('submitExam is idempotent — double submit redirects without re-grading', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    // First submit — creates and completes the attempt via the component
    $component = Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('submitExam');

    $attempt = Attempt::where('exam_id', $exam->id)
        ->where('user_id', $student->id)
        ->whereNotNull('completed_at')
        ->first();

    $originalScore = $attempt->score;

    // Second submit on the same component — should redirect without re-grading
    $component->call('submitExam')
        ->assertRedirect(route('student.attempts.results', $attempt));

    // Score must not change
    expect($attempt->fresh()->score)->toBe($originalScore);
});
