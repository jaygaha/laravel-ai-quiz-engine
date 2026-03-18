<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Features;

// --- Registration with role ---

it('can register as a student', function () {
    $this->skipUnlessFortifyFeature(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Test Student',
        'email' => 'student@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
    ])->assertRedirect();

    expect(User::where('email', 'student@test.com')->first()->role)->toBe(UserRole::Student);
});

it('can register as a teacher', function () {
    $this->skipUnlessFortifyFeature(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Test Teacher',
        'email' => 'teacher@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'teacher',
    ])->assertRedirect();

    expect(User::where('email', 'teacher@test.com')->first()->role)->toBe(UserRole::Teacher);
});

it('rejects registration with invalid role', function () {
    $this->skipUnlessFortifyFeature(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Test',
        'email' => 'test@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ])->assertSessionHasErrors('role');
});

// --- Role helpers on User model ---

it('user model isTeacher returns correct value', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    expect($teacher->isTeacher())->toBeTrue()
        ->and($teacher->isStudent())->toBeFalse()
        ->and($student->isStudent())->toBeTrue()
        ->and($student->isTeacher())->toBeFalse();
});

// --- Teacher role enforcement ---

it('teacher role middleware blocks student from teacher routes', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('teacher.exams.index'))
        ->assertForbidden();
});

it('teacher role middleware allows teacher to access teacher routes', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('teacher.exams.index'))
        ->assertOk();
});

// --- Student role enforcement ---

it('student role middleware blocks teacher from student routes', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

it('student role middleware allows student to access student routes', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk();
});

// --- Dashboard redirect by role ---

it('teacher is redirected to exams index from /dashboard', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('dashboard'))
        ->assertRedirect(route('teacher.exams.index'));
});

it('student is redirected to student dashboard from /dashboard', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.dashboard'));
});
