<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

it('toggleFlag persists the flagged state to the database', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    $question = Question::factory()->for($exam)->create();

    Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'completed_at' => null,
    ]);

    $component = Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('toggleFlag', $question->id);

    $attempt = Attempt::where('user_id', $student->id)->whereNull('completed_at')->first();
    $saved = $attempt->answers[$question->id] ?? null;

    expect($saved)->toBeArray()
        ->and($saved['flagged'])->toBeTrue();
});

it('toggling flag twice unflags the question', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    $question = Question::factory()->for($exam)->create();

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('toggleFlag', $question->id)
        ->call('toggleFlag', $question->id)
        ->assertSet("answers.{$question->id}.flagged", false);
});

it('flagged questions survive a page reload', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    $question = Question::factory()->for($exam)->create();

    // First visit — flag the question
    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('toggleFlag', $question->id);

    // Second visit — flag should be restored from DB
    $component = Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam]);

    expect($component->get("answers.{$question->id}.flagged"))->toBeTrue();
});

it('flaggedCount reflects the number of flagged questions', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    $q1 = Question::factory()->for($exam)->create();
    $q2 = Question::factory()->for($exam)->create();

    $component = Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam]);

    expect($component->get('flaggedCount'))->toBe(0);

    $component->call('toggleFlag', $q1->id);
    expect($component->get('flaggedCount'))->toBe(1);

    $component->call('toggleFlag', $q2->id);
    expect($component->get('flaggedCount'))->toBe(2);
});

it('filteredQuestions returns only flagged questions when filter is set to flagged', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    $q1 = Question::factory()->for($exam)->create();
    $q2 = Question::factory()->for($exam)->create();

    $component = Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('toggleFlag', $q1->id)
        ->set('filter', 'flagged');

    expect($component->get('filteredQuestions'))->toHaveCount(1)
        ->and($component->get('filteredQuestions')->first()->id)->toBe($q1->id);
});

it('submitExam grades answers correctly using the nested value shape', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    $question = Question::factory()->for($exam)->create([
        'options' => ['Paris', 'London', 'Berlin', 'Rome'],
        'correct_answer' => 'Paris',
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->set("answers.{$question->id}.value", 'Paris')
        ->call('submitExam');

    $attempt = Attempt::where('user_id', $student->id)->first();
    expect($attempt->score)->toBe(100);
});

it('submitExam preserves flag state in the saved answers', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    $question = Question::factory()->for($exam)->create([
        'options' => ['Paris', 'London', 'Berlin', 'Rome'],
        'correct_answer' => 'Paris',
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('toggleFlag', $question->id)
        ->set("answers.{$question->id}.value", 'Paris')
        ->call('submitExam');

    $attempt = Attempt::where('user_id', $student->id)->first();
    $saved = $attempt->answers[$question->id];

    expect($saved['flagged'])->toBeTrue();
});

it('results page shows bookmark badge on flagged questions', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $q = Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 0,
        'answers' => [
            $q->id => ['value' => 'Berlin', 'flagged' => true],
        ],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt])
        ->assertSeeHtml('Flagged for review');
});

it('results page correctCount handles the nested answer shape', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $q1 = Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);
    $q2 = Question::factory()->for($exam)->create(['correct_answer' => 'London']);

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 50,
        'answers' => [
            $q1->id => ['value' => 'Paris', 'flagged' => false],  // correct
            $q2->id => ['value' => 'Berlin', 'flagged' => true],  // wrong, flagged
        ],
    ]);

    $component = Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt]);

    expect($component->get('correctCount'))->toBe(1);
});
