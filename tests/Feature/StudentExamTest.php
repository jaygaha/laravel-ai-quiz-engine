<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

// --- Dashboard ---

it('student can view available exams dashboard', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk();
});

it('teacher cannot access student dashboard', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

it('dashboard shows only published exams', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();

    Exam::factory()->published()->for($teacher)->create(['title' => 'Published Exam']);
    Exam::factory()->draft()->for($teacher)->create(['title' => 'Draft Exam']);

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSee('Published Exam')
        ->assertDontSee('Draft Exam');
});

// --- Take Exam ---

it('student can visit a published exam', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->has(Question::factory()->count(2), 'questions')->create();

    $this->actingAs($student)
        ->get(route('student.exams.take', $exam))
        ->assertOk();
});

it('creates attempt when student visits exam', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam]);

    expect(Attempt::where('user_id', $student->id)->count())->toBe(1);
});

it('resumes existing incomplete attempt', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $existing = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'started_at' => now()->subMinutes(5),
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam]);

    expect(Attempt::where('user_id', $student->id)->count())->toBe(1);
    expect(Attempt::where('user_id', $student->id)->first()->id)->toBe($existing->id);
});

it('returns 404 for unpublished exam', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->draft()->for($teacher)->create();

    $this->actingAs($student)
        ->get(route('student.exams.take', $exam))
        ->assertNotFound();
});

// --- Submit Exam ---

it('student gets 100% when all answers are correct', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $q1 = Question::factory()->for($exam)->create(['correct_answer' => 'Paris', 'options' => ['Paris', 'London']]);
    $q2 = Question::factory()->for($exam)->create(['correct_answer' => 'Blue', 'options' => ['Red', 'Blue']]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->set("answers.{$q1->id}", 'Paris')
        ->set("answers.{$q2->id}", 'Blue')
        ->call('submitExam');

    $attempt = Attempt::where('user_id', $student->id)->first();

    expect($attempt->score)->toBe(100)
        ->and($attempt->isCompleted())->toBeTrue();
});

it('calculates partial score correctly', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $q1 = Question::factory()->for($exam)->create(['correct_answer' => 'Paris', 'options' => ['Paris', 'London']]);
    $q2 = Question::factory()->for($exam)->create(['correct_answer' => 'Blue', 'options' => ['Red', 'Blue']]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->set("answers.{$q1->id}", 'Paris')
        ->set("answers.{$q2->id}", 'Red') // wrong
        ->call('submitExam');

    $attempt = Attempt::where('user_id', $student->id)->first();
    expect($attempt->score)->toBe(50);
});

it('submit redirects to results page', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('submitExam')
        ->assertRedirect();
});

// --- Results ---

it('student can view completed attempt results', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.attempts.results', $attempt))
        ->assertOk();
});

it('student cannot view another students results', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $other->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.attempts.results', $attempt))
        ->assertForbidden();
});

it('returns 404 for incomplete attempt results', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'started_at' => now(),
        'completed_at' => null,
    ]);

    $this->actingAs($student)
        ->get(route('student.attempts.results', $attempt))
        ->assertNotFound();
});
