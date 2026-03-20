<?php

use App\Models\Exam;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Leaderboard')] class extends Component {
    public Exam $exam;

    public function mount(): void
    {
        abort_unless($this->exam->isPublished(), 404);
        abort_unless($this->exam->leaderboard_enabled, 404);
    }

    /** Triggered by Alpine Echo listener when a new submission arrives. */
    #[On('exam-attempt-submitted')]
    public function refresh(): void
    {
        unset($this->topAttempts);
    }

    #[Computed]
    public function topAttempts(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->exam->attempts()
            ->completed()
            ->with('student:id,name')
            ->orderByDesc('score')
            ->orderBy('completed_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function myRank(): ?int
    {
        $myAttempt = $this->exam->attempts()
            ->completed()
            ->where('user_id', auth()->id())
            ->orderByDesc('score')
            ->first();

        if (! $myAttempt) {
            return null;
        }

        return $this->exam->attempts()
            ->completed()
            ->where(function ($q) use ($myAttempt) {
                $q->where('score', '>', $myAttempt->score)
                    ->orWhere(function ($q2) use ($myAttempt) {
                        $q2->where('score', $myAttempt->score)
                            ->where('completed_at', '<', $myAttempt->completed_at);
                    });
            })
            ->count() + 1;
    }
}; ?>

<div
    class="mx-auto max-w-2xl flex flex-col gap-6"
    x-data="{
        initEcho() {
            if (typeof Echo === 'undefined') return;
            Echo.channel('exam.{{ $exam->id }}')
                .listen('AttemptSubmittedEvent', () => {
                    $wire.dispatch('exam-attempt-submitted');
                });
        }
    }"
    x-init="initEcho()"
>
    <div class="flex items-center gap-4">
        <flux:button variant="ghost" icon="arrow-left" :href="route('student.dashboard')" wire:navigate />
        <div>
            <flux:heading size="xl">Leaderboard</flux:heading>
            <flux:text>{{ $exam->title }}</flux:text>
        </div>
        <span class="ml-auto inline-flex items-center gap-1.5 text-sm text-teal-700 font-medium">
            <span class="size-2 rounded-full bg-teal-500 inline-block animate-pulse"></span>
            Live
        </span>
    </div>

    {{-- My rank card (if already completed) --}}
    @if ($this->myRank !== null)
        <div class="bento-flat p-6 flex items-center justify-between gap-4" style="border-color:#86EFAC; background:#F0FDF4;">
            <div>
                <flux:text class="text-sm text-zinc-500">Your current rank</flux:text>
                <div class="text-3xl font-bold text-teal-700">#{{ $this->myRank }}</div>
            </div>
            <flux:badge :color="$this->myRank === 1 ? 'yellow' : 'teal'" size="lg">
                {{ $this->myRank === 1 ? 'Top of the class!' : 'Keep it up!' }}
            </flux:badge>
        </div>
    @endif

    {{-- Top 10 --}}
    <div class="bento-flat divide-y divide-gray-100">
        @forelse ($this->topAttempts as $index => $attempt)
            @php
                $rank = $index + 1;
                $isMe = $attempt->user_id === auth()->id();
            @endphp
            <div
                class="flex items-center gap-4 px-4 py-3 {{ $isMe ? 'bg-teal-50' : '' }}"
                wire:key="rank-{{ $attempt->id }}"
            >
                <div class="w-8 text-center font-bold shrink-0">
                    @if ($rank === 1)
                        <span class="text-lg">🥇</span>
                    @elseif ($rank === 2)
                        <span class="text-lg">🥈</span>
                    @elseif ($rank === 3)
                        <span class="text-lg">🥉</span>
                    @else
                        <span class="text-sm text-zinc-500">#{{ $rank }}</span>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <flux:text class="font-medium truncate">
                        {{ $isMe ? 'You' : $attempt->student->name }}
                    </flux:text>
                    <flux:text class="text-xs text-zinc-400">
                        Submitted {{ $attempt->completed_at->diffForHumans() }}
                    </flux:text>
                </div>

                <div class="text-right shrink-0">
                    <div class="text-lg font-bold {{ $attempt->score >= 70 ? 'score-pass' : ($attempt->score >= 50 ? 'score-warn' : 'score-fail') }}">
                        {{ $attempt->score }}%
                    </div>
                </div>
            </div>
        @empty
            <div class="py-12 text-center">
                <flux:text>No submissions yet. Be the first to complete this exam!</flux:text>
            </div>
        @endforelse
    </div>

    <div class="flex justify-center">
        <flux:button variant="ghost" :href="route('student.dashboard')" wire:navigate>
            Back to Dashboard
        </flux:button>
    </div>
</div>
