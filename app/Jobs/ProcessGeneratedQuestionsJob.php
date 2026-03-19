<?php

namespace App\Jobs;

use App\Enums\QuestionType;
use App\Models\Exam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessGeneratedQuestionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    public function __construct(
        public readonly int $examId,
        public readonly array $questions,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exam = Exam::findOrFail($this->examId);
        $nextOrder = $exam->questions()->max('order') + 1;

        foreach ($this->questions as $data) {
            if (empty($data['question']) || empty($data['correct_answer'])) {
                continue;
            }

            $type = QuestionType::tryFrom($data['type'] ?? '') ?? QuestionType::ShortAnswer;

            $exam->questions()->create([
                'question' => $data['question'],
                'type' => $type->value,
                'options' => $type->hasOptions() && ! empty($data['options']) ? $data['options'] : null,
                'correct_answer' => $data['correct_answer'],
                'order' => $nextOrder++,
            ]);
        }
    }
}
