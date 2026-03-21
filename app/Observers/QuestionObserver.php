<?php

namespace App\Observers;

use App\Jobs\GenerateQuestionEmbeddingJob;
use App\Models\Question;

class QuestionObserver
{
    public function created(Question $question): void
    {
        GenerateQuestionEmbeddingJob::dispatch($question);
    }

    public function updated(Question $question): void
    {
        if ($question->wasChanged(['question', 'correct_answer'])) {
            GenerateQuestionEmbeddingJob::dispatch($question);
        }
    }
}
