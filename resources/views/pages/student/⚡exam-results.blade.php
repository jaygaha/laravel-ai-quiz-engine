<?php

use App\Enums\QuestionType;
use App\Jobs\ExportStudentResultJob;
use App\Models\Attempt;
use App\Services\QuestionSimilarityService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Exam Results')] class extends Component {
    public Attempt $attempt;

    public function mount(): void
    {
        abort_unless(auth()->id() === $this->attempt->user_id, 403);
        abort_unless($this->attempt->isCompleted(), 404);
    }

    #[Computed]
    public function correctCount(): int
    {
        $correct = 0;

        foreach ($this->attempt->exam->questions as $question) {
            $given = $this->attempt->answers[$question->id] ?? null;

            if ($given === null) {
                continue;
            }

            if (is_array($given)) {
                if ($given['ai_graded'] ?? false) {
                    // AI-graded short answer (both old and new shape)
                    if (($given['ai_score'] ?? 0) >= 50) {
                        $correct++;
                    }
                } else {
                    // New shape: { value, flagged }
                    $value = $given['value'] ?? null;

                    if (
                        $value !== null && $value !== '' &&
                        strtolower(trim($value)) === strtolower(trim($question->correct_answer))
                    ) {
                        $correct++;
                    }
                }
            } else {
                // Old shape: plain string
                if (strtolower(trim($given)) === strtolower(trim($question->correct_answer))) {
                    $correct++;
                }
            }
        }

        return $correct;
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->attempt->exam->questions->count();
    }

    public function requestExport(): void
    {
        ExportStudentResultJob::dispatch($this->attempt->id);

        session()->flash('export_status', 'Your PDF is being generated — you will receive an email shortly.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Question>
     */
    #[Computed]
    public function recommendations(): \Illuminate\Database\Eloquent\Collection
    {
        $incorrectTexts = [];

        foreach ($this->attempt->exam->questions as $question) {
            $given = $this->attempt->answers[$question->id] ?? null;

            if ($given === null) {
                continue;
            }

            $isCorrect = false;

            if (is_array($given)) {
                if ($given['ai_graded'] ?? false) {
                    $isCorrect = ($given['ai_score'] ?? 0) >= 50;
                } else {
                    $value = $given['value'] ?? null;
                    $isCorrect = $value !== null && $value !== '' &&
                        strtolower(trim($value)) === strtolower(trim($question->correct_answer));
                }
            } else {
                $isCorrect = strtolower(trim($given)) === strtolower(trim($question->correct_answer));
            }

            if (! $isCorrect) {
                $incorrectTexts[] = $question->question;
            }
        }

        if (empty($incorrectTexts)) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        $query = implode(' ', array_slice($incorrectTexts, 0, 3));

        try {
            return app(QuestionSimilarityService::class)
                ->findSimilar($query, limit: 5, excludeExamId: $this->attempt->exam_id);
        } catch (\Throwable $e) {
            logger()->warning('Recommendations unavailable', ['error' => $e->getMessage()]);

            return new \Illuminate\Database\Eloquent\Collection;
        }
    }
}; ?>

<div class="mx-auto max-w-3xl flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <flux:button variant="ghost" icon="arrow-left" :href="route('student.dashboard')" wire:navigate />
        <flux:heading size="xl">Results: {{ $attempt->exam->title }}</flux:heading>
    </div>

    @if (session('export_status'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('export_status') }}" />
    @endif

    {{-- Score Card --}}
    <div class="bento-flat p-8 text-center space-y-3">
        <div
            class="text-6xl font-bold {{ $attempt->score >= 70 ? 'score-pass' : ($attempt->score >= 50 ? 'score-warn' : 'score-fail') }}">
            {{ $attempt->score }}%
        </div>
        <flux:text>
            You answered {{ $this->correctCount }} out of {{ $this->totalCount }} questions correctly.
        </flux:text>
        <flux:badge :color="$attempt->score >= 70 ? 'green' : ($attempt->score >= 50 ? 'yellow' : 'red')">
            {{ $attempt->score >= 70 ? 'Passed' : ($attempt->score >= 50 ? 'Needs Improvement' : 'Failed') }}
        </flux:badge>
    </div>

    {{-- Recommended Practice --}}
    @if ($this->recommendations->isNotEmpty())
        <div class="bento-flat space-y-3">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>
                <flux:heading size="lg">Recommended Practice</flux:heading>
            </div>
            <flux:text size="sm">Based on the questions you missed, here are similar questions from other exams to practice:</flux:text>
            <div class="space-y-2">
                @foreach ($this->recommendations as $rec)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 border border-gray-100">
                        <span class="inline-flex items-center justify-center min-w-6 h-6 rounded-md bg-teal-50 text-teal-700 text-xs font-bold shrink-0 mt-0.5">
                            {{ $loop->iteration }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <flux:text class="font-medium">{{ $rec->question }}</flux:text>
                            <flux:text size="sm" class="mt-0.5">
                                From: <a href="{{ route('student.exams.take', $rec->exam) }}" wire:navigate class="text-teal-600 hover:underline">{{ $rec->exam->title }}</a>
                            </flux:text>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Question Review --}}
    <flux:heading size="lg">Question Review</flux:heading>
    <div class="space-y-3">
        @foreach ($attempt->exam->questions as $index => $question)
            @php
                $given = $attempt->answers[$question->id] ?? null;
                $isAiGraded = is_array($given) && ($given['ai_graded'] ?? false);
                $isFlagged = is_array($given) && ($given['flagged'] ?? false);

                // Extract the display answer, handling all shapes
                if ($isAiGraded) {
                    $rawAnswer = $given['raw_answer'] ?? $given['value'] ?? null;
                } elseif (is_array($given)) {
                    $rawAnswer = $given['value'] ?? null;    // New { value, flagged } shape
                } else {
                    $rawAnswer = $given;                     // Old plain-string shape
                }

                $isCorrect = $isAiGraded
                    ? ($given['ai_score'] ?? 0) >= 50
                    : ($rawAnswer !== null && $rawAnswer !== '' &&
                        strtolower(trim($rawAnswer)) === strtolower(trim($question->correct_answer)));
            @endphp
            <div class="bento-flat space-y-2" style="{{ $isCorrect
            ? 'border-color:#86EFAC; background:#F0FDF4;'
            : 'border-color:#FCA5A5; background:#FFF5F5;' }}" wire:key="result-{{ $question->id }}">
                <div class="flex items-start justify-between gap-3">
                    <flux:text class="font-medium">{{ $index + 1 }}. {{ $question->question }}</flux:text>
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($isFlagged)
                            <span title="Flagged for review">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-teal-600" fill="currentColor"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                                </svg>
                            </span>
                        @endif
                        @if ($isAiGraded)
                            <flux:badge size="sm" color="zinc">AI graded</flux:badge>
                            <flux:badge size="sm" :color="$isCorrect ? 'green' : 'red'">
                                {{ $given['ai_score'] }}%
                            </flux:badge>
                        @else
                            <flux:badge size="sm" :color="$isCorrect ? 'green' : 'red'">
                                {{ $isCorrect ? 'Correct' : 'Incorrect' }}
                            </flux:badge>
                        @endif
                    </div>
                </div>

                <flux:text size="sm">Your answer: <strong>{{ $rawAnswer ?? 'Not answered' }}</strong></flux:text>

                @if (!$isCorrect && !$isAiGraded)
                    <flux:text size="sm" style="color:#16A34A;">
                        Correct answer: <strong>{{ $question->correct_answer }}</strong>
                    </flux:text>
                @endif

                @if ($isAiGraded)
                    @if ($given['ai_explanation'] ?? null)
                        <flux:text size="sm" style="color:#374151;">
                            {{ $given['ai_explanation'] }}
                        </flux:text>
                    @endif
                    @if (!$isCorrect && ($given['ai_suggestion'] ?? null))
                        <div class="pt-1 pl-3 border-l-2 border-amber-300">
                            <flux:text size="sm" style="color:#92400E;">
                                <strong>Tip:</strong> {{ $given['ai_suggestion'] }}
                            </flux:text>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    <div class="flex justify-center gap-3 flex-wrap">
        <flux:button variant="outline" wire:click="requestExport" wire:loading.attr="disabled"
            wire:target="requestExport">
            <span wire:loading.remove wire:target="requestExport" class="inline-flex items-center gap-1">
                <flux:icon.arrow-down-tray class="size-4" />
                Download Result (PDF)
            </span>
            <span wire:loading wire:target="requestExport" class="inline-flex items-center gap-1">
                <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                Queuing…
            </span>
        </flux:button>
        @if ($attempt->exam->leaderboard_enabled)
            <flux:button variant="outline" icon="chart-bar" :href="route('student.exams.leaderboard', $attempt->exam)"
                wire:navigate>
                View Leaderboard
            </flux:button>
        @endif
        <flux:button variant="primary" :href="route('student.dashboard')" wire:navigate>
            Back to Dashboard
        </flux:button>
    </div>
</div>