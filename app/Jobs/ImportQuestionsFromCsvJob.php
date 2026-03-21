<?php

namespace App\Jobs;

use App\Enums\QuestionType;
use App\Models\Exam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ImportQuestionsFromCsvJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $examId,
        public readonly string $csvPath,
    ) {}

    public function handle(): void
    {
        $exam = Exam::findOrFail($this->examId);

        $fullPath = Storage::disk('local')->path($this->csvPath);
        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            return;
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return;
        }

        // Normalise header keys
        $header = array_map(fn (string $col) => strtolower(trim($col)), $header);

        $nextOrder = $exam->questions()->max('order') + 1;
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $headerCount = count($header);

            if (count($row) < $headerCount) {
                $skipped++;

                continue;
            }

            // When extra columns exist (unquoted commas in the last field), rejoin them
            if (count($row) > $headerCount) {
                $extra = array_splice($row, $headerCount - 1);
                $row[] = implode(',', $extra);
            }

            $data = array_combine($header, $row);
            $question = trim($data['question'] ?? '');
            $typeRaw = trim($data['type'] ?? '');
            $answer = trim($data['correct_answer'] ?? '');

            if ($question === '' || $answer === '') {
                $skipped++;

                continue;
            }

            $type = QuestionType::tryFrom($typeRaw) ?? QuestionType::ShortAnswer;

            $options = null;
            if ($type->hasOptions() && ! empty($data['options'])) {
                $options = array_values(array_filter(array_map('trim', explode('|', $data['options']))));
            }

            $exam->questions()->create([
                'question' => $question,
                'type' => $type->value,
                'correct_answer' => $answer,
                'options' => $options,
                'order' => $nextOrder++,
            ]);

            $imported++;
        }

        fclose($handle);

        @unlink($fullPath);
    }
}
