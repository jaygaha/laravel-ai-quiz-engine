<?php

use App\Models\Attempt;
use App\Models\Question;
use Illuminate\Support\Facades\Schema;

it('has embedding vector column on questions table', function () {
    expect(Schema::hasColumn('questions', 'embedding'))->toBeTrue();
});

it('has embedding vector column on attempts table', function () {
    expect(Schema::hasColumn('attempts', 'embedding'))->toBeTrue();
});

it('allows nullable embedding on questions', function () {
    $question = Question::factory()->create();

    expect($question->embedding)->toBeNull();
});

it('allows nullable embedding on attempts', function () {
    $attempt = Attempt::factory()->create();

    expect($attempt->embedding)->toBeNull();
});
