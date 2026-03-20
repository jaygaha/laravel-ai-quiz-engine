<?php

use App\Jobs\ImportQuestionsFromCsvJob;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// ── Question Bank ─────────────────────────────────────────────────────────────

it('question bank lists all questions across teacher exams', function () {
    $teacher = User::factory()->teacher()->create();
    $exam1 = Exam::factory()->published()->for($teacher)->create();
    $exam2 = Exam::factory()->published()->for($teacher)->create();

    Question::factory()->for($exam1)->create(['question' => 'Question in exam 1']);
    Question::factory()->for($exam2)->create(['question' => 'Question in exam 2']);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.question-bank')
        ->assertSee('Question in exam 1')
        ->assertSee('Question in exam 2');
});

it('question bank does not show other teacher questions', function () {
    $teacher = User::factory()->teacher()->create();
    $other = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($other)->create();

    Question::factory()->for($exam)->create(['question' => 'Other teacher question']);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.question-bank')
        ->assertDontSee('Other teacher question');
});

it('question bank copies question to another exam', function () {
    $teacher = User::factory()->teacher()->create();
    $source = Exam::factory()->published()->for($teacher)->create();
    $target = Exam::factory()->published()->for($teacher)->create();

    $question = Question::factory()->for($source)->create(['question' => 'Copy me']);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.question-bank')
        ->call('openAddToExam', $question->id)
        ->set('targetExamId', $target->id)
        ->call('copyToExam');

    expect($target->questions()->where('question', 'Copy me')->exists())->toBeTrue();
});

it('question bank add-to-exam requires a target exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $question = Question::factory()->for($exam)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.question-bank')
        ->call('openAddToExam', $question->id)
        ->set('targetExamId', '')
        ->call('copyToExam')
        ->assertHasErrors(['targetExamId']);
});

// ── CSV Import ────────────────────────────────────────────────────────────────

it('questions page dispatches ImportQuestionsFromCsvJob when CSV is uploaded', function () {
    Queue::fake();
    Storage::fake('local');

    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $csv = UploadedFile::fake()->createWithContent('questions.csv', implode("\n", [
        'question,type,correct_answer',
        'What is 2+2?,short_answer,4',
    ]));

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('csvFile', $csv)
        ->call('importCsv');

    Queue::assertPushed(ImportQuestionsFromCsvJob::class, fn ($job) => $job->examId === $exam->id);
});

it('ImportQuestionsFromCsvJob imports valid rows and skips malformed ones', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $csv = implode("\n", [
        'question,type,correct_answer',
        'What is 2+2?,short_answer,4',
        ',short_answer,',             // malformed — empty question
        'What is PHP?,short_answer,A scripting language',
    ]);

    Storage::fake('local');
    Storage::disk('local')->put('imports/test.csv', $csv);

    (new ImportQuestionsFromCsvJob($exam->id, 'imports/test.csv'))->handle();

    expect($exam->questions()->count())->toBe(2);
});

it('ImportQuestionsFromCsvJob skips rows with fewer columns than header', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $csv = implode("\n", [
        'question,type,correct_answer',
        'Valid question,short_answer,yes',
        'only one column',
    ]);

    Storage::fake('local');
    Storage::disk('local')->put('imports/test.csv', $csv);

    (new ImportQuestionsFromCsvJob($exam->id, 'imports/test.csv'))->handle();

    expect($exam->questions()->count())->toBe(1);
});

// ── Exam Preview Mode ─────────────────────────────────────────────────────────

it('teacher can preview a draft exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create(['published_at' => null]);

    Question::factory()->for($exam)->create();

    Livewire::actingAs($teacher)
        ->withQueryParams(['preview' => 1])
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->assertSet('isPreview', true);
});

it('non-teacher cannot access exam preview', function () {
    $teacher = User::factory()->teacher()->create();
    $other = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create(['published_at' => null]);

    Question::factory()->for($exam)->create();

    Livewire::actingAs($other)
        ->withQueryParams(['preview' => 1])
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->assertForbidden();
});

it('submitExam is blocked in preview mode', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    Livewire::actingAs($teacher)
        ->withQueryParams(['preview' => 1])
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('submitExam');

    // No attempt should be completed
    expect($exam->attempts()->whereNotNull('completed_at')->count())->toBe(0);
});

it('draft exam is not accessible without preview param', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create(['published_at' => null]);

    Question::factory()->for($exam)->create();

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->assertNotFound();
});
