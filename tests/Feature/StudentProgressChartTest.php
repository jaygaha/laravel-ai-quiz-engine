<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Livewire\Livewire;

it('progress chart data is empty when student has no completed attempts', function () {
    $student = User::factory()->student()->create();

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSet('progressChartData', []);
});

it('progress chart data only includes completed attempts', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 75]);
    Attempt::factory()->for($exam)->for($student, 'student')->create(); // in-progress

    $data = Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->instance()
        ->progressChartData;

    expect($data)->toHaveCount(1)
        ->and($data[0]['score'])->toBe(75);
});

it('progress chart data is sorted by date ascending', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create([
        'score' => 60,
        'completed_at' => now()->subDays(2),
    ]);
    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create([
        'score' => 80,
        'completed_at' => now()->subDay(),
    ]);
    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create([
        'score' => 90,
        'completed_at' => now(),
    ]);

    $data = Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->instance()
        ->progressChartData;

    expect(array_column($data, 'score'))->toBe([60, 80, 90]);
});

it('progress chart data includes score and label fields', function () {
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create(['title' => 'PHP Basics']);

    Attempt::factory()->completed()->for($exam)->for($student, 'student')->create(['score' => 85]);

    $data = Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->instance()
        ->progressChartData;

    expect($data[0])->toHaveKeys(['score', 'label', 'date'])
        ->and($data[0]['score'])->toBe(85)
        ->and($data[0]['label'])->toContain('PHP Basics');
});

it('progress chart data only includes own attempts', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();
    $exam = Exam::factory()->published()->create();

    Attempt::factory()->completed()->for($exam)->for($other, 'student')->count(3)->create();

    Livewire::actingAs($student)
        ->test('pages::student.dashboard')
        ->assertSet('progressChartData', []);
});
