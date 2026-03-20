<?php

namespace App\Jobs;

use App\Mail\ExportReadyMail;
use App\Models\Attempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ExportStudentResultJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $attemptId) {}

    public function handle(): void
    {
        $attempt = Attempt::with(['exam.questions', 'student'])->findOrFail($this->attemptId);

        $correctCount = $this->computeCorrectCount($attempt);
        $totalCount = $attempt->exam->questions->count();

        $pdf = Pdf::loadView('pdf.student-result', compact('attempt', 'correctCount', 'totalCount'));
        $path = 'exports/'.Str::uuid().'-result.pdf';

        Storage::disk('local')->put($path, $pdf->output());

        $downloadUrl = URL::temporarySignedRoute(
            'exports.download',
            now()->addHours(24),
            ['path' => $path],
        );

        Mail::to($attempt->student->email)->send(
            new ExportReadyMail(
                mailSubject: 'Your result for "'.$attempt->exam->title.'" is ready',
                downloadUrl: $downloadUrl,
                expiresAt: now()->addHours(24),
            )
        );
    }

    private function computeCorrectCount(Attempt $attempt): int
    {
        $correct = 0;

        foreach ($attempt->exam->questions as $question) {
            $given = $attempt->answers[$question->id] ?? null;

            if ($given === null) {
                continue;
            }

            if (is_array($given)) {
                if ($given['ai_graded'] ?? false) {
                    if (($given['ai_score'] ?? 0) >= 50) {
                        $correct++;
                    }
                } else {
                    $value = $given['value'] ?? null;
                    if ($value && strtolower(trim($value)) === strtolower(trim($question->correct_answer))) {
                        $correct++;
                    }
                }
            } elseif (strtolower(trim($given)) === strtolower(trim($question->correct_answer))) {
                $correct++;
            }
        }

        return $correct;
    }
}
