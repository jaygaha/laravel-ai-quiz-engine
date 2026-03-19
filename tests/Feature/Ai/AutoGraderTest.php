<?php

use App\Ai\Agents\AutoGraderAgent;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

it('ai grades a short answer question and stores structured result', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $question = Question::factory()->shortAnswer()->for($exam)->create([
        'correct_answer' => 'A server-side scripting language',
    ]);

    AutoGraderAgent::fake([
        [
            'score' => 90,
            'is_correct' => true,
            'explanation' => 'The answer captures the key concept.',
            'suggestion' => 'You could also mention it is open source.',
        ],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->set("answers.{$question->id}", 'A scripting language for servers')
        ->call('submitExam');

    $attempt = Attempt::where('user_id', $student->id)->first();
    $storedAnswer = $attempt->answers[$question->id];

    expect($storedAnswer)->toBeArray()
        ->and($storedAnswer['ai_graded'])->toBeTrue()
        ->and($storedAnswer['ai_score'])->toBe(90)
        ->and($storedAnswer['raw_answer'])->toBe('A scripting language for servers');
});

it('counts ai graded short answer as correct when score >= 50', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    Question::factory()->shortAnswer()->for($exam)->create(['correct_answer' => 'Photosynthesis']);

    AutoGraderAgent::fake([
        ['score' => 75, 'is_correct' => true, 'explanation' => 'Partially correct.', 'suggestion' => ''],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->set("answers.{$exam->questions->first()->id}", 'A process plants use for energy')
        ->call('submitExam');

    $attempt = Attempt::where('user_id', $student->id)->first();
    expect($attempt->score)->toBe(100);
});

it('counts ai graded short answer as incorrect when score < 50', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    Question::factory()->shortAnswer()->for($exam)->create(['correct_answer' => 'Photosynthesis']);

    AutoGraderAgent::fake([
        ['score' => 20, 'is_correct' => false, 'explanation' => 'Wrong concept.', 'suggestion' => 'Think about plants.'],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.take-exam', ['exam' => $exam])
        ->set("answers.{$exam->questions->first()->id}", 'Respiration')
        ->call('submitExam');

    $attempt = Attempt::where('user_id', $student->id)->first();
    expect($attempt->score)->toBe(0);
});

it('exam results correctly count ai graded answers', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $q = Question::factory()->shortAnswer()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 100,
        'answers' => [
            $q->id => [
                'raw_answer' => 'The capital of France is Paris',
                'ai_score' => 85,
                'ai_explanation' => 'Correct.',
                'ai_suggestion' => '',
                'ai_graded' => true,
            ],
        ],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt])
        ->assertSee('AI graded')
        ->assertSee('85%');
});

it('exam results show ai suggestion for incorrect short answers', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $q = Question::factory()->shortAnswer()->for($exam)->create(['correct_answer' => 'Paris']);

    $attempt = Attempt::factory()->completed()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'score' => 0,
        'answers' => [
            $q->id => [
                'raw_answer' => 'Berlin',
                'ai_score' => 0,
                'ai_explanation' => 'That is the capital of Germany.',
                'ai_suggestion' => 'Think about France.',
                'ai_graded' => true,
            ],
        ],
    ]);

    Livewire::actingAs($student)
        ->test('pages::student.exam-results', ['attempt' => $attempt])
        ->assertSee('Think about France.');
});
