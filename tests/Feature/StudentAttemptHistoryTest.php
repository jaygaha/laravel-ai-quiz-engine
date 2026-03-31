<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Livewire\Livewire;

it('student can view attempt history page', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('student.attempts'))
        ->assertOk();
});

it('attempt history only shows completed attempts', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create();
    Attempt::factory()->for($exam)->for($student, 'student')->create(); // in-progress

    Livewire::actingAs($student)
        ->test('pages::student.attempts')
        ->assertSet('attempts', fn ($attempts) => $attempts->total() === 1);
});

it('attempt history is paginated', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->count(16)->create();

    Livewire::actingAs($student)
        ->test('pages::student.attempts')
        ->assertSet('attempts', fn ($attempts) => $attempts->total() === 16 && $attempts->count() === 15);
});

it('attempt history can be filtered by exam name', function () {
    $student = User::factory()->student()->create();
    $examA = Exam::factory()->published()->create(['title' => 'Algebra 101']);
    $examB = Exam::factory()->published()->create(['title' => 'Biology Basics']);

    Attempt::factory()->completed()->for($examA)->for($student, 'student')->create();
    Attempt::factory()->completed()->for($examB)->for($student, 'student')->create();

    Livewire::actingAs($student)
        ->test('pages::student.attempts')
        ->set('search', 'Algebra')
        ->assertSet('attempts', fn ($attempts) => $attempts->total() === 1);
});

it('attempt history can be sorted by score', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 90]);
    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 40]);
    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 70]);

    $component = Livewire::actingAs($student)
        ->test('pages::student.attempts')
        ->call('sort', 'score');

    $scores = $component->instance()->attempts->pluck('score')->toArray();

    expect($scores)->toBe([90, 70, 40]);
});

it('attempt history only shows own attempts', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create(['title' => 'Secret Exam']);

    Attempt::factory()->completed()->for($exam)->for($other, 'student')->create();

    Livewire::actingAs($student)
        ->test('pages::student.attempts')
        ->assertSet('attempts', fn ($attempts) => $attempts->total() === 0);
});

it('guest is redirected from attempt history', function () {
    $this->get(route('student.attempts'))
        ->assertRedirect(route('login'));
});
