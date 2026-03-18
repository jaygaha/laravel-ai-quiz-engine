<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Take Exam')] class extends Component {
    public Exam $exam;
    public ?Attempt $attempt = null;

    /** @var array<int, string> */
    public array $answers = [];

    public function mount(): void
    {
        abort_unless($this->exam->isPublished(), 404);

        // Resume or create an attempt
        $this->attempt = auth()->user()->attempts()
            ->where('exam_id', $this->exam->id)
            ->whereNull('completed_at')
            ->latest()
            ->first();

        if (! $this->attempt) {
            $this->attempt = auth()->user()->attempts()->create([
                'exam_id' => $this->exam->id,
                'started_at' => now(),
            ]);
        }

        $this->answers = $this->attempt->answers ?? [];
    }

    public function submitExam(): void
    {
        $this->validate([
            'answers' => 'array',
            'answers.*' => 'nullable|string|max:500',
        ]);

        $questions = $this->exam->questions;
        $correct = 0;

        foreach ($questions as $question) {
            $given = $this->answers[$question->id] ?? null;
            if ($given !== null && strtolower(trim($given)) === strtolower(trim($question->correct_answer))) {
                $correct++;
            }
        }

        $score = $questions->count() > 0
            ? (int) round(($correct / $questions->count()) * 100)
            : 0;

        $this->attempt->update([
            'answers' => $this->answers,
            'score' => $score,
            'completed_at' => now(),
        ]);

        $this->redirect(route('student.attempts.results', $this->attempt), navigate: true);
    }

    #[Computed]
    public function questions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->exam->questions;
    }
}; ?>

<div class="mx-auto max-w-3xl flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ $exam->title }}</flux:heading>
                <div class="flex items-center gap-3 mt-1">
                    <flux:text>{{ $this->questions->count() }} questions</flux:text>
                    @if ($exam->time_limit)
                        <flux:text>• {{ $exam->time_limit }} minute limit</flux:text>
                    @endif
                </div>
            </div>
            <flux:button variant="ghost" :href="route('student.dashboard')" wire:navigate>Exit</flux:button>
        </div>

        @if ($exam->description)
            <flux:callout icon="information-circle">
                {{ $exam->description }}
            </flux:callout>
        @endif

        <form wire:submit="submitExam" class="space-y-4">
            @foreach ($this->questions as $index => $question)
                <div class="bento-flat space-y-4" wire:key="q-{{ $question->id }}">
                    <div style="display:flex; align-items:flex-start; gap:.75rem;">
                        <span style="display:inline-flex; align-items:center; justify-content:center; min-width:1.75rem; height:1.75rem; border-radius:8px; background:#F0FDFA; color:#0F766E; font-size:.75rem; font-weight:700; flex-shrink:0; margin-top:1px;">
                            {{ $index + 1 }}
                        </span>
                        <flux:heading style="flex:1; padding-top:2px;">{{ $question->question }}</flux:heading>
                    </div>

                    @if ($question->options)
                        <div class="space-y-2 pt-1">
                            @foreach ($question->options as $option)
                                <label class="quiz-option" wire:key="opt-{{ $question->id }}-{{ $loop->index }}">
                                    <input
                                        type="radio"
                                        wire:model="answers.{{ $question->id }}"
                                        value="{{ $option }}"
                                    />
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <flux:input
                            wire:model="answers.{{ $question->id }}"
                            placeholder="Type your answer…"
                        />
                    @endif
                </div>
            @endforeach

            <div class="flex justify-end gap-3 pt-2 pb-4">
                <flux:button variant="primary" type="submit" icon="check">
                    Submit Exam
                </flux:button>
            </div>
        </form>
    </div>
