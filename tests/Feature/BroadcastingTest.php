<?php

use App\Ai\Agents\AutoGraderAgent;
use App\Events\AttemptSubmittedEvent;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

// ── AttemptSubmittedEvent ────────────────────────────────────────────────────

it('AttemptSubmittedEvent is dispatched when submitExam completes', function () {
    Config::set('broadcasting.default', 'reverb');
    Event::fake([AttemptSubmittedEvent::class]);

    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    AutoGraderAgent::fake([
        ['score' => 0, 'is_correct' => false, 'explanation' => '', 'suggestion' => ''],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('submitExam');

    Event::assertDispatched(AttemptSubmittedEvent::class);
});

it('AttemptSubmittedEvent payload contains correct student_count', function () {
    Config::set('broadcasting.default', 'reverb');
    Event::fake([AttemptSubmittedEvent::class]);

    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    // Pre-seed two existing completed attempts for other students
    Attempt::factory()->completed()->count(2)->create(['exam_id' => $exam->id]);

    AutoGraderAgent::fake([
        ['score' => 0, 'is_correct' => false, 'explanation' => '', 'suggestion' => ''],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('submitExam');

    Event::assertDispatched(AttemptSubmittedEvent::class, function ($event) {
        // 2 pre-seeded + 1 just submitted = 3
        return $event->studentCount === 3;
    });
});

it('AttemptSubmittedEvent is NOT dispatched when broadcasting is not reverb', function () {
    Config::set('broadcasting.default', 'log');
    Event::fake([AttemptSubmittedEvent::class]);

    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['time_limit' => null]);
    Question::factory()->for($exam)->create(['correct_answer' => 'Paris']);

    AutoGraderAgent::fake([
        ['score' => 0, 'is_correct' => false, 'explanation' => '', 'suggestion' => ''],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->call('submitExam');

    Event::assertNotDispatched(AttemptSubmittedEvent::class);
});

// ── AttemptSubmittedEvent unit coverage ───────────────────────────────────────

it('AttemptSubmittedEvent broadcasts on the correct channel', function () {
    $event = new AttemptSubmittedEvent(examId: 42, studentCount: 5, latestStudentName: 'Alice');

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0]->name)->toBe('exam.42');
});

it('AttemptSubmittedEvent broadcastWith returns correct payload', function () {
    $event = new AttemptSubmittedEvent(examId: 1, studentCount: 3, latestStudentName: 'Bob');

    $payload = $event->broadcastWith();

    expect($payload)->toBe([
        'student_count' => 3,
        'latest_student_name' => 'Bob',
    ]);
});

// ── Leaderboard page ─────────────────────────────────────────────────────────

it('leaderboard page shows top-10 attempts ordered by score', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['leaderboard_enabled' => true]);
    $students = User::factory()->student()->count(5)->create();

    $scores = [90, 70, 50, 80, 60];

    foreach ($students as $i => $student) {
        Attempt::factory()->completed()->create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => $scores[$i],
        ]);
    }

    $viewer = $students->first();

    $component = Livewire::actingAs($viewer)
        ->test('pages::student.leaderboard', ['exam' => $exam]);

    $top = $component->get('topAttempts');

    expect($top)->toHaveCount(5)
        ->and($top->first()->score)->toBe(90)
        ->and($top->last()->score)->toBe(50);
});

it('leaderboard page returns 404 when leaderboard_enabled is false', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['leaderboard_enabled' => false]);
    $student = User::factory()->student()->create();

    Livewire::actingAs($student)
        ->test('pages::student.leaderboard', ['exam' => $exam])
        ->assertStatus(404);
});

it('leaderboard myRank returns correct position for the authenticated student', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create(['leaderboard_enabled' => true]);
    $students = User::factory()->student()->count(3)->create();

    // student[0]=100, student[1]=80, student[2]=60
    foreach ($students as $i => $student) {
        Attempt::factory()->completed()->create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => 100 - $i * 20,
        ]);
    }

    // student[1] is rank 2
    $component = Livewire::actingAs($students[1])
        ->test('pages::student.leaderboard', ['exam' => $exam]);

    expect($component->get('myRank'))->toBe(2);
});

it('leaderboard returns 404 for unpublished exam', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create(['leaderboard_enabled' => true]);
    $student = User::factory()->student()->create();

    Livewire::actingAs($student)
        ->test('pages::student.leaderboard', ['exam' => $exam])
        ->assertStatus(404);
});
