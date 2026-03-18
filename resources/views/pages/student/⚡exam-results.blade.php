<?php

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
            if ($given !== null && strtolower(trim($given)) === strtolower(trim($question->correct_answer))) {
                $correct++;
            }
        }

        return $correct;
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->attempt->exam->questions->count();
    }
}; ?>

<div class="mx-auto max-w-3xl flex flex-col gap-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('student.dashboard')" wire:navigate />
            <flux:heading size="xl">Results: {{ $attempt->exam->title }}</flux:heading>
        </div>

        {{-- Score Card --}}
        <div class="bento-flat p-8 text-center space-y-3">
            <div class="text-6xl font-bold {{ $attempt->score >= 70 ? 'score-pass' : ($attempt->score >= 50 ? 'score-warn' : 'score-fail') }}">
                {{ $attempt->score }}%
            </div>
            <flux:text>
                You answered {{ $this->correctCount }} out of {{ $this->totalCount }} questions correctly.
            </flux:text>
            <flux:badge
                :color="$attempt->score >= 70 ? 'green' : ($attempt->score >= 50 ? 'yellow' : 'red')"
            >
                {{ $attempt->score >= 70 ? 'Passed' : ($attempt->score >= 50 ? 'Needs Improvement' : 'Failed') }}
            </flux:badge>
        </div>

        {{-- Question Review --}}
        <flux:heading size="lg">Question Review</flux:heading>
        <div class="space-y-3">
            @foreach ($attempt->exam->questions as $index => $question)
                @php
                    $given = $attempt->answers[$question->id] ?? null;
                    $isCorrect = $given !== null && strtolower(trim($given)) === strtolower(trim($question->correct_answer));
                @endphp
                <div
                    class="bento-flat space-y-2"
                    style="{{ $isCorrect
                        ? 'border-color:#86EFAC; background:#F0FDF4;'
                        : 'border-color:#FCA5A5; background:#FFF5F5;' }}"
                    wire:key="result-{{ $question->id }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <flux:text class="font-medium">{{ $index + 1 }}. {{ $question->question }}</flux:text>
                        <flux:badge size="sm" :color="$isCorrect ? 'green' : 'red'">
                            {{ $isCorrect ? 'Correct' : 'Incorrect' }}
                        </flux:badge>
                    </div>
                    <flux:text size="sm">Your answer: <strong>{{ $given ?? 'Not answered' }}</strong></flux:text>
                    @if (! $isCorrect)
                        <flux:text size="sm" style="color:#16A34A;">
                            Correct answer: <strong>{{ $question->correct_answer }}</strong>
                        </flux:text>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex justify-center">
            <flux:button variant="primary" :href="route('student.dashboard')" wire:navigate>
                Back to Dashboard
            </flux:button>
        </div>
    </div>
