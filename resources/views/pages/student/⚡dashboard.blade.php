<?php

use App\Models\Exam;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Available Exams')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function availableExams(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Exam::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->withCount('questions')
            ->when($this->search, fn ($q) => $q->where('title', 'ilike', '%'.$this->search.'%'))
            ->latest('published_at')
            ->paginate(12);
    }

    #[Computed]
    public function myAttempts(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()->attempts()
            ->with('exam')
            ->latest()
            ->limit(5)
            ->get();
    }
}; ?>

<div class="flex flex-col gap-8">
        <flux:heading size="xl">Available Exams</flux:heading>

        <flux:input wire:model.live.debounce="search" placeholder="Search exams…" icon="magnifying-glass" clearable />

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->availableExams as $exam)
                <div class="bento-card flex flex-col gap-3" wire:key="exam-{{ $exam->id }}">
                    <div>
                        <flux:heading size="lg">{{ $exam->title }}</flux:heading>
                        @if ($exam->description)
                            <flux:text class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ $exam->description }}</flux:text>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 text-sm text-zinc-500">
                        <span class="flex items-center gap-1">
                            <flux:icon.list-bullet class="size-4" />
                            {{ $exam->questions_count }} questions
                        </span>
                        @if ($exam->time_limit)
                            <span class="flex items-center gap-1">
                                <flux:icon.clock class="size-4" />
                                {{ $exam->time_limit }} min
                            </span>
                        @endif
                    </div>
                    <flux:button
                        variant="primary"
                        :href="route('student.exams.take', $exam)"
                        wire:navigate
                        class="mt-auto"
                    >
                        Start Exam
                    </flux:button>
                </div>
            @empty
                <div class="col-span-3 rounded-xl border border-dashed p-12 text-center" style="border-color:var(--color-border-hover)">
                    <flux:text>No exams available yet. Check back soon!</flux:text>
                </div>
            @endforelse
        </div>

        {{ $this->availableExams->links() }}

        @if ($this->myAttempts->isNotEmpty())
            <div class="space-y-4">
                <flux:heading size="lg">Recent Attempts</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Exam</flux:table.column>
                        <flux:table.column>Score</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->myAttempts as $attempt)
                            <flux:table.row :key="$attempt->id">
                                <flux:table.cell variant="strong">{{ $attempt->exam->title }}</flux:table.cell>
                                <flux:table.cell>
                                    {{ $attempt->score !== null ? $attempt->score.'%' : '—' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($attempt->isCompleted())
                                        <flux:badge color="green" size="sm">Completed</flux:badge>
                                    @else
                                        <flux:badge color="yellow" size="sm">In Progress</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $attempt->started_at?->diffForHumans() }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($attempt->isCompleted())
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :href="route('student.attempts.results', $attempt)"
                                            wire:navigate
                                        >
                                            View Results
                                        </flux:button>
                                    @else
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :href="route('student.exams.take', $attempt->exam)"
                                            wire:navigate
                                        >
                                            Continue
                                        </flux:button>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>
