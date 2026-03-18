<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

// --- Index ---

it('teacher can view their exams list', function () {
    $teacher = User::factory()->teacher()->create();
    Exam::factory()->count(3)->for($teacher)->create();

    $this->actingAs($teacher)
        ->get(route('teacher.exams.index'))
        ->assertOk();
});

it('student cannot access teacher exam list', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('teacher.exams.index'))
        ->assertForbidden();
});

it('guest is redirected from teacher routes', function () {
    $this->get(route('teacher.exams.index'))
        ->assertRedirect(route('login'));
});

// --- Create ---

it('teacher can create an exam', function () {
    $teacher = User::factory()->teacher()->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.create')
        ->set('title', 'Algebra 101')
        ->set('description', 'Basic algebra concepts')
        ->set('time_limit', 60)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Exam::where('title', 'Algebra 101')->exists())->toBeTrue();
});

it('creates exam owned by authenticated teacher', function () {
    $teacher = User::factory()->teacher()->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.create')
        ->set('title', 'My Exam')
        ->call('save');

    $exam = Exam::where('title', 'My Exam')->first();
    expect($exam->user_id)->toBe($teacher->id);
});

it('validates required title on create', function () {
    $teacher = User::factory()->teacher()->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.create')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

// --- Edit ---

it('teacher can update their own exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create(['title' => 'Old Title']);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.edit', ['exam' => $exam])
        ->set('title', 'New Title')
        ->call('save')
        ->assertHasNoErrors();

    expect($exam->fresh()->title)->toBe('New Title');
});

it('teacher cannot edit another teacher exam', function () {
    $teacher = User::factory()->teacher()->create();
    $other = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($other)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.edit', ['exam' => $exam])
        ->assertForbidden();
});

// --- Delete ---

it('teacher can delete their own exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.index')
        ->call('deleteExam', $exam->id)
        ->assertHasNoErrors();

    expect(Exam::find($exam->id))->toBeNull();
});

// --- Publish ---

it('teacher can publish a draft exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->draft()->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.index')
        ->call('togglePublish', $exam->id);

    expect($exam->fresh()->isPublished())->toBeTrue();
});

it('teacher can unpublish a published exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->published()->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.index')
        ->call('togglePublish', $exam->id);

    expect($exam->fresh()->isPublished())->toBeFalse();
});

// --- Questions ---

it('teacher can add a multiple choice question', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('question', 'What is 2+2?')
        ->set('type', 'multiple_choice')
        ->set('options', ['4', '3', '5', '6'])
        ->set('correct_answer', '4')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    expect($exam->questions()->count())->toBe(1);
    expect($exam->questions()->first()->correct_answer)->toBe('4');
});

it('teacher can edit an existing question', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();
    $question = Question::factory()->for($exam)->create(['question' => 'Old question?']);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->call('editQuestion', $question->id)
        ->set('question', 'Updated question?')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    expect($question->fresh()->question)->toBe('Updated question?');
});

it('teacher can delete a question', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();
    $question = Question::factory()->for($exam)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->call('deleteQuestion', $question->id)
        ->assertHasNoErrors();

    expect(Question::find($question->id))->toBeNull();
});

it('validates question fields are required', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('question', '')
        ->set('correct_answer', '')
        ->call('saveQuestion')
        ->assertHasErrors(['question', 'correct_answer']);
});
