<?php

use App\Jobs\ExportExamResultsJob;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Exam Results')] class extends Component {
    public Exam $exam;

    public function mount(): void
    {
        abort_unless(auth()->id() === $this->exam->user_id, 403);
    }

    public function exportResults(): void
    {
        ExportExamResultsJob::dispatch($this->exam->id, auth()->id());

        $this->dispatch('toast', variant: 'success', heading: 'Export queued', text: 'Your PDF is being generated — you will receive an email shortly.');
    }

    #[Computed]
    public function attempts(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->exam->attempts()
            ->completed()
            ->with('student:id,name,email')
            ->orderByDesc('score')
            ->get();
    }

    #[Computed]
    public function averageScore(): int
    {
        return $this->attempts->count() > 0
            ? (int) round($this->attempts->avg('score'))
            : 0;
    }

    #[Computed]
    public function passRate(): int
    {
        if ($this->attempts->count() === 0) {
            return 0;
        }

        return (int) round(($this->attempts->where('score', '>=', 70)->count() / $this->attempts->count()) * 100);
    }

    /**
     * Questions with the lowest correct-answer rates.
     *
     * @return \Illuminate\Support\Collection<int, array{question: Question, correct_rate: int}>
     */
    #[Computed]
    public function struggledTopics(): \Illuminate\Support\Collection
    {
        $attempts = $this->attempts;

        if ($attempts->isEmpty()) {
            return collect();
        }

        return $this->exam->questions->map(function (Question $question) use ($attempts) {
            $correctCount = 0;
            $totalAttempts = $attempts->count();

            foreach ($attempts as $attempt) {
                $given = $attempt->answers[$question->id] ?? null;

                if ($given === null) {
                    continue;
                }

                if (is_array($given)) {
                    if ($given['ai_graded'] ?? false) {
                        if (($given['ai_score'] ?? 0) >= 50) {
                            $correctCount++;
                        }
                    } else {
                        $value = $given['value'] ?? null;
                        if ($value !== null && $value !== '' &&
                            strtolower(trim($value)) === strtolower(trim($question->correct_answer))) {
                            $correctCount++;
                        }
                    }
                } elseif (strtolower(trim($given)) === strtolower(trim($question->correct_answer))) {
                    $correctCount++;
                }
            }

            $correctRate = $totalAttempts > 0 ? (int) round(($correctCount / $totalAttempts) * 100) : 0;

            return ['question' => $question, 'correct_rate' => $correctRate];
        })
        ->sortBy('correct_rate')
        ->take(5)
        ->values();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('teacher.exams.index')" wire:navigate />
            <div>
                <flux:heading size="xl">{{ $exam->title }}</flux:heading>
                <flux:text>{{ $this->attempts->count() }} submission(s)</flux:text>
            </div>
        </div>
        <flux:button variant="outline" wire:click="exportResults" wire:loading.attr="disabled"
            wire:target="exportResults">
            <span wire:loading.remove wire:target="exportResults" class="inline-flex items-center gap-1">
                <flux:icon.arrow-down-tray class="size-4" />
                Export Results (PDF)
            </span>
            <span wire:loading wire:target="exportResults" class="inline-flex items-center gap-1">
                <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                Queuing…
            </span>
        </flux:button>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bento-flat p-5 text-center">
            <div class="text-3xl font-bold text-teal-700">{{ $this->attempts->count() }}</div>
            <flux:text class="text-sm mt-1">Submissions</flux:text>
        </div>
        <div class="bento-flat p-5 text-center">
            <div
                class="text-3xl font-bold {{ $this->averageScore >= 70 ? 'score-pass' : ($this->averageScore >= 50 ? 'score-warn' : 'score-fail') }}">
                {{ $this->averageScore }}%
            </div>
            <flux:text class="text-sm mt-1">Average Score</flux:text>
        </div>
        <div class="bento-flat p-5 text-center">
            <div class="text-3xl font-bold text-teal-700">{{ $this->passRate }}%</div>
            <flux:text class="text-sm mt-1">Pass Rate (≥70%)</flux:text>
        </div>
    </div>

    {{-- Struggled Topics --}}
    @if ($this->struggledTopics->isNotEmpty() && $this->attempts->count() > 0)
        <div class="bento-flat space-y-3">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <flux:heading size="lg">Most Struggled Questions</flux:heading>
            </div>
            <div class="space-y-2">
                @foreach ($this->struggledTopics as $topic)
                    <div class="flex items-center justify-between gap-4 p-3 rounded-lg bg-gray-50 border border-gray-100">
                        <flux:text class="font-medium flex-1 min-w-0">{{ $topic['question']->question }}</flux:text>
                        <div class="shrink-0">
                            <flux:badge size="sm" :color="$topic['correct_rate'] >= 50 ? 'yellow' : 'red'">
                                {{ $topic['correct_rate'] }}% correct
                            </flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Results table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Student</flux:table.column>
            <flux:table.column>Score</flux:table.column>
            <flux:table.column>Result</flux:table.column>
            <flux:table.column>Submitted</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->attempts as $attempt)
                <flux:table.row :key="$attempt->id">
                    <flux:table.cell variant="strong">{{ $attempt->student->name }}</flux:table.cell>
                    <flux:table.cell>
                        <span
                            class="{{ $attempt->score >= 70 ? 'score-pass' : ($attempt->score >= 50 ? 'score-warn' : 'score-fail') }} font-bold">
                            {{ $attempt->score }}%
                        </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$attempt->score >= 70 ? 'green' : ($attempt->score >= 50 ? 'yellow' : 'red')"
                            size="sm">
                            {{ $attempt->score >= 70 ? 'Passed' : ($attempt->score >= 50 ? 'Needs Improvement' : 'Failed') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $attempt->completed_at->diffForHumans() }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <div class="py-10 text-center">
                            <flux:text>No submissions yet.</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>