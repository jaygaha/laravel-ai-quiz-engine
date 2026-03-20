<?php

use App\Jobs\ExportExamResultsJob;
use App\Models\Attempt;
use App\Models\Exam;
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

        session()->flash('status', 'Your PDF is being generated — you will receive an email shortly.');
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
        <flux:button variant="outline" icon="arrow-down-tray" wire:click="exportResults" wire:loading.attr="disabled"
            wire:target="exportResults">
            <span wire:loading.remove wire:target="exportResults">Export Results (PDF)</span>
            <span wire:loading wire:target="exportResults">Queuing…</span>
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
    @endif

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