<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Exam Result — {{ $attempt->exam->title }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; padding: 32px; }

        /* ── Header ── */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #0d9488; }
        .header h1 { font-size: 20px; font-weight: 700; color: #0d9488; }
        .header .meta { font-size: 11px; color: #6b7280; text-align: right; }

        /* ── Score card ── */
        .score-card { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 24px; }
        .score-number { font-size: 52px; font-weight: 800; line-height: 1; }
        .score-pass   { color: #059669; }
        .score-warn   { color: #d97706; }
        .score-fail   { color: #dc2626; }
        .score-label  { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; margin-top: 8px; }
        .badge-pass { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef9c3; color: #854d0e; }
        .badge-fail { background: #fee2e2; color: #991b1b; }

        /* ── Question review ── */
        h2 { font-size: 14px; font-weight: 700; margin-bottom: 12px; color: #374151; }
        .question { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 10px; }
        .question.correct { border-color: #86efac; background: #f0fdf4; }
        .question.incorrect { border-color: #fca5a5; background: #fff5f5; }
        .question-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .question-text { font-weight: 600; font-size: 11.5px; flex: 1; padding-right: 8px; }
        .question-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; white-space: nowrap; }
        .q-correct  { background: #dcfce7; color: #166534; }
        .q-incorrect{ background: #fee2e2; color: #991b1b; }
        .q-ai       { background: #f3f4f6; color: #374151; }
        .answer-row { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .answer-row strong { color: #374151; }
        .correct-answer-row { font-size: 11px; color: #059669; margin-top: 2px; }
        .ai-explanation { font-size: 10.5px; color: #374151; margin-top: 6px; padding: 6px; background: #f9fafb; border-left: 3px solid #9ca3af; border-radius: 3px; }
        .ai-tip { font-size: 10.5px; color: #92400e; margin-top: 6px; padding: 6px; background: #fffbeb; border-left: 3px solid #fcd34d; border-radius: 3px; }

        /* ── Footer ── */
        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>Exam Result</h1>
            <div style="font-size:13px; color:#374151; margin-top:4px;">{{ $attempt->exam->title }}</div>
        </div>
        <div class="meta">
            <div><strong>Student:</strong> {{ $attempt->student->name }}</div>
            <div><strong>Submitted:</strong> {{ $attempt->completed_at->format('d M Y, H:i') }}</div>
            @if ($attempt->exam->time_limit)
                <div><strong>Time Limit:</strong> {{ $attempt->exam->time_limit }} min</div>
            @endif
        </div>
    </div>

    {{-- Score card --}}
    <div class="score-card">
        @php
            $scoreClass = $attempt->score >= 70 ? 'score-pass' : ($attempt->score >= 50 ? 'score-warn' : 'score-fail');
            $badgeClass = $attempt->score >= 70 ? 'badge-pass' : ($attempt->score >= 50 ? 'badge-warn' : 'badge-fail');
            $badgeLabel = $attempt->score >= 70 ? 'Passed' : ($attempt->score >= 50 ? 'Needs Improvement' : 'Failed');
        @endphp
        <div class="score-number {{ $scoreClass }}">{{ $attempt->score }}%</div>
        <div class="score-label">{{ $correctCount }} / {{ $totalCount }} questions correct</div>
        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </div>

    {{-- Question breakdown --}}
    <h2>Question Review</h2>

    @foreach ($attempt->exam->questions as $index => $question)
        @php
            $given      = $attempt->answers[$question->id] ?? null;
            $isAiGraded = is_array($given) && ($given['ai_graded'] ?? false);
            $isFlagged  = is_array($given) && ($given['flagged'] ?? false);

            if ($isAiGraded) {
                $rawAnswer = $given['raw_answer'] ?? $given['value'] ?? null;
                $isCorrect = ($given['ai_score'] ?? 0) >= 50;
            } elseif (is_array($given)) {
                $rawAnswer = $given['value'] ?? null;
                $isCorrect = $rawAnswer && strtolower(trim($rawAnswer)) === strtolower(trim($question->correct_answer));
            } else {
                $rawAnswer = $given;
                $isCorrect = $rawAnswer && strtolower(trim($rawAnswer)) === strtolower(trim($question->correct_answer));
            }
        @endphp
        <div class="question {{ $isCorrect ? 'correct' : 'incorrect' }}">
            <div class="question-header">
                <div class="question-text">{{ $index + 1 }}. {{ $question->question }}</div>
                <div style="display:flex; gap:4px; align-items:center;">
                    @if ($isFlagged)
                        <span style="font-size:10px; color:#0d9488;">⚑ Flagged</span>
                    @endif
                    @if ($isAiGraded)
                        <span class="question-badge q-ai">AI {{ $given['ai_score'] }}%</span>
                    @else
                        <span class="question-badge {{ $isCorrect ? 'q-correct' : 'q-incorrect' }}">
                            {{ $isCorrect ? 'Correct' : 'Incorrect' }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="answer-row">Your answer: <strong>{{ $rawAnswer ?? 'Not answered' }}</strong></div>

            @if (! $isCorrect && ! $isAiGraded)
                <div class="correct-answer-row">Correct answer: <strong>{{ $question->correct_answer }}</strong></div>
            @endif

            @if ($isAiGraded)
                @if ($given['ai_explanation'] ?? null)
                    <div class="ai-explanation">{{ $given['ai_explanation'] }}</div>
                @endif
                @if (! $isCorrect && ($given['ai_suggestion'] ?? null))
                    <div class="ai-tip"><strong>Tip:</strong> {{ $given['ai_suggestion'] }}</div>
                @endif
            @endif
        </div>
    @endforeach

    <div class="footer">
        <span>{{ config('app.name') }}</span>
        <span>Generated {{ now()->format('d M Y, H:i') }}</span>
    </div>

</body>
</html>
