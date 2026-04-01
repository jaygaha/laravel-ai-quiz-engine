<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Livewire\Livewire;

it('stats show zero when student has no attempts', function () {
    $student = User::factory()->student()->create();

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSet('totalAttempts', 0)
        ->assertSet('averageScore', 0)
        ->assertSet('bestScore', 0);
});

it('stats count only completed attempts', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($student)->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->count(3)->create();
    Attempt::factory()->for($exam)->for($student, 'student')->create(); // in-progress

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSet('totalAttempts', 3);
});

it('stats compute average score correctly', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($student)->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 80]);
    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 60]);

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSet('averageScore', 70);
});

it('stats compute best score correctly', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($student)->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 55]);
    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 92]);
    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 78]);

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSet('bestScore', 92);
});

it('stats only count own attempts', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    Attempt::factory()->completed()->for($exam)->for($other, 'student')->count(5)->create();

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSet('totalAttempts', 0)
        ->assertSet('bestScore', 0);
});
