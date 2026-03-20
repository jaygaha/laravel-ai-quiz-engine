<?php

use App\Jobs\ExportExamResultsJob;
use App\Jobs\ExportStudentResultJob;
use App\Mail\ExportReadyMail;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

// ── ExportStudentResultJob ────────────────────────────────────────────────────

it('ExportStudentResultJob creates a PDF file in storage', function () {
    Storage::fake('local');
    Mail::fake();

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $question = Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 100,
        'answers' => [$question->id => ['value' => 'Paris', 'flagged' => false]],
    ]);

    (new ExportStudentResultJob($attempt->id))->handle();

    $files = Storage::disk('local')->files('exports');
    expect($files)->toHaveCount(1);
});

it('ExportStudentResultJob generated PDF file is not empty', function () {
    Storage::fake('local');
    Mail::fake();

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $question = Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 100,
        'answers' => [$question->id => ['value' => 'Paris', 'flagged' => false]],
    ]);

    (new ExportStudentResultJob($attempt->id))->handle();

    $path = Storage::disk('local')->files('exports')[0];
    $contents = Storage::disk('local')->get($path);

    expect(strlen($contents))->toBeGreaterThan(100);
});

it('ExportStudentResultJob sends an email with a signed download URL', function () {
    Storage::fake('local');
    Mail::fake();

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $question = Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 100,
        'answers' => [$question->id => ['value' => 'Paris', 'flagged' => false]],
    ]);

    (new ExportStudentResultJob($attempt->id))->handle();

    Mail::assertSent(ExportReadyMail::class, fn ($mail) => $mail->hasTo($student->email));
});

it('signed download URL is valid within 24 hours and returns 200', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/test.pdf', '%PDF test content');

    $url = URL::temporarySignedRoute('exports.download', now()->addHours(24), ['path' => 'exports/test.pdf']);

    $this->get($url)->assertOk();
});

it('expired signed download URL returns 403', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/test.pdf', '%PDF test content');

    $url = URL::temporarySignedRoute('exports.download', now()->subSecond(), ['path' => 'exports/test.pdf']);

    $this->get($url)->assertForbidden();
});

// ── ExportReadyMail ──────────────────────────────────────────────────────────

it('ExportReadyMail sets the correct subject and uses markdown content', function () {
    $mail = new ExportReadyMail(
        mailSubject: 'Your PDF is ready',
        downloadUrl: 'https://example.com/download',
        expiresAt: now()->addHours(24)->toImmutable(),
    );

    expect($mail->envelope()->subject)->toBe('Your PDF is ready')
        ->and($mail->content()->markdown)->toBe('emails.export-ready');
});

it('ExportReadyMail renders without errors', function () {
    $mail = new ExportReadyMail(
        mailSubject: 'Export ready',
        downloadUrl: 'https://example.com/download',
        expiresAt: now()->addHours(24)->toImmutable(),
    );

    $rendered = $mail->render();

    expect($rendered)->toContain('Download PDF')
        ->and($rendered)->toContain('https://example.com/download');
});

// ── ExportExamResultsJob ──────────────────────────────────────────────────────

it('ExportExamResultsJob creates a PDF file and emails the teacher', function () {
    Storage::fake('local');
    Mail::fake();

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 75,
    ]);

    (new ExportExamResultsJob($exam->id, $teacher->id))->handle();

    $files = Storage::disk('local')->files('exports');
    expect($files)->toHaveCount(1);

    Mail::assertSent(ExportReadyMail::class, fn ($mail) => $mail->hasTo($teacher->email));
});

// ── Livewire dispatch ─────────────────────────────────────────────────────────

it('student results page dispatches ExportStudentResultJob when button is clicked', function () {
    Queue::fake();

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 80,
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt])
        ->call('requestExport');

    Queue::assertPushed(ExportStudentResultJob::class, fn ($job) => $job->attemptId === $attempt->id);
});

it('teacher results page dispatches ExportExamResultsJob when export button is clicked', function () {
    Queue::fake();

    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.results', ['exam' => $exam])
        ->call('exportResults');

    Queue::assertPushed(ExportExamResultsJob::class, fn ($job) => $job->examId === $exam->id);
});

it('teacher results page shows correct stats', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $students = User::factory()->student()->count(3)->create();

    foreach ($students as $i => $student) {
        Attempt::factory()->completed()->create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => [80, 90, 40][$i],
        ]);
    }

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.results', ['exam' => $exam]);

    expect($component->get('averageScore'))->toBe(70)
        ->and($component->get('passRate'))->toBe(67); // 2/3 passed
});
