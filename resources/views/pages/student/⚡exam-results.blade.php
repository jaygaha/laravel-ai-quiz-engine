<?php

use App\Enums\QuestionType;
use App\Jobs\ExportStudentResultJob;
use App\Models\Attempt;
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
        <flux:button variant="outline" icon="arrow-down-tray" wire:click="requestExport" wire:loading.attr="disabled"
            wire:target="requestExport">
            <span wire:loading.remove wire:target="requestExport">Download Result (PDF)</span>
            <span wire:loading wire:target="requestExport">Queuing…</span>
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