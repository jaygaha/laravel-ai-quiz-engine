<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

it('teacher can bulk delete selected questions', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();
    $questions = Question::factory()->for($exam)->count(3)->create();

    $toDelete = $questions->take(2)->pluck('id')->all();
    $keepId = $questions->last()->id;

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('selectedQuestions', $toDelete)
        ->call('deleteSelected');

    expect(Question::whereIn('id', $toDelete)->count())->toBe(0)
        ->and(Question::find($keepId))->not->toBeNull();
});

it('bulk delete only deletes questions belonging to the exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();
    $otherExam = Exam::factory()->for($teacher)->create();
    $ownQuestion = Question::factory()->for($exam)->create();
    $otherQuestion = Question::factory()->for($otherExam)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('selectedQuestions', [$ownQuestion->id, $otherQuestion->id])
        ->call('deleteSelected');

    expect(Question::withTrashed()->find($ownQuestion->id)->deleted_at)->not->toBeNull()
        ->and(Question::find($otherQuestion->id))->not->toBeNull();
});

it('toggling select all populates all question ids', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();
    Question::factory()->for($exam)->count(3)->create();

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('selectAll', true);

    expect($component->get('selectedQuestions'))->toHaveCount(3);
});

it('toggling select all off clears the selection', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();
    Question::factory()->for($exam)->count(3)->create();

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('selectAll', true)
        ->set('selectAll', false);

    expect($component->get('selectedQuestions'))->toBeEmpty();
});

it('delete selected does nothing when selection is empty', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();
    Question::factory()->for($exam)->count(2)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->call('deleteSelected');

    expect(Question::count())->toBe(2);
});
