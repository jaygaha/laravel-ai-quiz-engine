<?php

use App\Models\Attempt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Attempts')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'completed_at';
    public string $sortDir = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }

        $this->resetPage();
    }

    #[Computed]
    public function attempts(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return auth()->user()->attempts()
            ->completed()
            ->with('exam:id,title')
            ->when($this->search, fn ($q) => $q->whereHas('exam', fn ($q) => $q->where('title', 'ilike', '%'.$this->search.'%')))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">My Attempts</flux:heading>
        <flux:button variant="ghost" icon="arrow-left" :href="route('student.dashboard')" wire:navigate>
            Dashboard
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce="search" placeholder="Search by exam name…" icon="magnifying-glass" clearable />

    <flux:table :paginate="$this->attempts">
        <flux:table.columns>
            <flux:table.column>Exam</flux:table.column>
            <flux:table.column>
                <button wire:click="sort('score')" class="flex items-center gap-1 font-medium">
                    Score
                    @if ($sortBy === 'score')
                        @if ($sortDir === 'asc')
                            <flux:icon.chevron-up class="size-3" />
                        @else
                            <flux:icon.chevron-down class="size-3" />
                        @endif
                    @endif
                </button>
            </flux:table.column>
            <flux:table.column>Result</flux:table.column>
            <flux:table.column>
                <button wire:click="sort('completed_at')" class="flex items-center gap-1 font-medium">
                    Date
                    @if ($sortBy === 'completed_at')
                        @if ($sortDir === 'asc')
                            <flux:icon.chevron-up class="size-3" />
                        @else
                            <flux:icon.chevron-down class="size-3" />
                        @endif
                    @endif
                </button>
            </flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->attempts as $attempt)
                <flux:table.row :key="$attempt->id">
                    <flux:table.cell variant="strong">{{ $attempt->exam->title }}</flux:table.cell>
                    <flux:table.cell>
                        <span class="{{ $attempt->score >= 70 ? 'score-pass' : ($attempt->score >= 50 ? 'score-warn' : 'score-fail') }} font-bold">
                            {{ $attempt->score }}%
                        </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge
                            size="sm"
                            :color="$attempt->score >= 70 ? 'green' : ($attempt->score >= 50 ? 'yellow' : 'red')"
                        >
                            {{ $attempt->score >= 70 ? 'Passed' : ($attempt->score >= 50 ? 'Needs Improvement' : 'Failed') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $attempt->completed_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            :href="route('student.attempts.results', $attempt)"
                            wire:navigate
                        >
                            View Results
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="py-10 text-center">
                            <flux:text>
                                No completed attempts yet.
                                <flux:link :href="route('student.dashboard')" wire:navigate>
                                    Browse available exams.
                                </flux:link>
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
