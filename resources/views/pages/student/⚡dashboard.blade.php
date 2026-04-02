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

    #[Computed]
    public function totalAttempts(): int
    {
        return auth()->user()->attempts()->completed()->count();
    }

    #[Computed]
    public function averageScore(): int
    {
        return (int) round(
            auth()->user()->attempts()->completed()->avg('score') ?? 0
        );
    }

    #[Computed]
    public function bestScore(): int
    {
        return (int) (auth()->user()->attempts()->completed()->max('score') ?? 0);
    }

    /**
     * @return array<int, array{label: string, score: int, date: string}>
     */
    #[Computed]
    public function progressChartData(): array
    {
        return auth()->user()->attempts()
            ->completed()
            ->with('exam:id,title')
            ->orderBy('completed_at')
            ->get()
            ->map(fn ($attempt) => [
                'label' => $attempt->exam->title.' ('.$attempt->completed_at->format('M j').')',
                'score' => $attempt->score,
                'date' => $attempt->completed_at->format('M j, Y'),
            ])
            ->toArray();
    }
}; ?>

<div class="flex flex-col gap-8">
    @if ($this->totalAttempts > 0)
        <div class="grid grid-cols-3 gap-4">
            <div class="bento-flat p-5 text-center">
                <div class="text-3xl font-bold text-teal-700">{{ $this->totalAttempts }}</div>
                <flux:text class="text-sm mt-1">Exams Taken</flux:text>
            </div>
            <div class="bento-flat p-5 text-center">
                <div class="text-3xl font-bold {{ $this->averageScore >= 70 ? 'score-pass' : ($this->averageScore >= 50 ? 'score-warn' : 'score-fail') }}">
                    {{ $this->averageScore }}%
                </div>
                <flux:text class="text-sm mt-1">Average Score</flux:text>
            </div>
            <div class="bento-flat p-5 text-center">
                <div class="text-3xl font-bold {{ $this->bestScore >= 70 ? 'score-pass' : ($this->bestScore >= 50 ? 'score-warn' : 'score-fail') }}">
                    {{ $this->bestScore }}%
                </div>
                <flux:text class="text-sm mt-1">Best Score</flux:text>
            </div>
        </div>
    @endif

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

        @if (count($this->progressChartData) >= 2)
            <div class="bento-flat p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Score Progress</flux:heading>
                    <flux:button variant="ghost" size="sm" :href="route('student.attempts')" wire:navigate>
                        View all
                    </flux:button>
                </div>
                <div
                    x-data="progressChart(@js($this->progressChartData))"
                    x-init="init()"
                    style="height:220px; position:relative;"
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        @endif

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

@once
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    <script>
        function progressChart(data) {
            return {
                init() {
                    const scores = data.map(d => d.score);
                    const labels = data.map(d => d.date);

                    new Chart(this.$refs.canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                data: scores,
                                borderColor: '#0d9488',
                                backgroundColor: 'rgba(13,148,136,0.08)',
                                borderWidth: 2,
                                pointBackgroundColor: scores.map(s =>
                                    s >= 70 ? '#16a34a' : s >= 50 ? '#d97706' : '#dc2626'
                                ),
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                fill: true,
                                tension: 0.3,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => ` ${ctx.parsed.y}%`,
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    min: 0,
                                    max: 100,
                                    ticks: {
                                        callback: v => v + '%',
                                        stepSize: 25,
                                    },
                                    grid: { color: 'rgba(0,0,0,0.05)' },
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { maxTicksLimit: 8 },
                                },
                            },
                        },
                    });
                },
            };
        }
    </script>
@endonce
