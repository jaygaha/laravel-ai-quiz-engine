<?php

use App\Http\Controllers\PdfDownloadController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Redirect /dashboard by role
Route::middleware(['auth', 'verified'])->get('dashboard', function () {
    return auth()->user()->isTeacher()
        ? redirect()->route('teacher.exams.index')
        : redirect()->route('student.dashboard');
})->name('dashboard');

// Teacher routes
Route::middleware(['auth', 'verified', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::livewire('/exams', 'pages::teacher.exams.index')->name('exams.index');
        Route::livewire('/exams/create', 'pages::teacher.exams.create')->name('exams.create');
        Route::livewire('/exams/{exam}/edit', 'pages::teacher.exams.edit')->name('exams.edit');
        Route::livewire('/exams/{exam}/questions', 'pages::teacher.exams.questions')->name('exams.questions');
        Route::livewire('/exams/{exam}/results', 'pages::teacher.exams.results')->name('exams.results');
        Route::livewire('/questions', 'pages::teacher.question-bank')->name('questions.index');
        Route::livewire('/ai-usage', 'pages::teacher.ai-usage')->name('ai-usage');
    });

// Student routes
Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::livewire('/dashboard', 'pages::student.dashboard')->name('dashboard');
        Route::livewire('/exams/{exam}/take', 'pages::student.take-exam')->name('exams.take');
        Route::livewire('/attempts/{attempt}/results', 'pages::student.exam-results')->name('attempts.results');
        Route::livewire('/attempts', 'pages::student.attempts')->name('attempts');
        Route::livewire('/exams/{exam}/leaderboard', 'pages::student.leaderboard')->name('exams.leaderboard');
    });

// PDF export download (signed URL — auth not required, signature acts as the token)
Route::get('/exports/download', PdfDownloadController::class)
    ->name('exports.download')
    ->middleware('signed');

require __DIR__.'/settings.php';
