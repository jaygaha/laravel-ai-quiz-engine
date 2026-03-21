<?php

use App\Models\AiUsage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('AI Usage')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()->isTeacher(), 403);
    }

    /**
     * Daily token spend for the last 7 days.
     *
     * @return \Illuminate\Support\Collection<int, array{date: string, input_tokens: int, output_tokens: int, cost: string}>
     */
    #[Computed]
    public function dailyUsage(): \Illuminate\Support\Collection
    {
        return AiUsage::query()
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->selectRaw("DATE(created_at) as date")
            ->selectRaw('SUM(input_tokens) as input_tokens')
            ->selectRaw('SUM(output_tokens) as output_tokens')
            ->selectRaw('SUM(estimated_cost) as cost')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();
    }

    /**
     * Per-model breakdown.
     *
     * @return \Illuminate\Support\Collection<int, array{model: string, input_tokens: int, output_tokens: int, cost: string}>
     */
    #[Computed]
    public function modelBreakdown(): \Illuminate\Support\Collection
    {
        return AiUsage::query()
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->selectRaw('model')
            ->selectRaw('SUM(input_tokens) as input_tokens')
            ->selectRaw('SUM(output_tokens) as output_tokens')
            ->selectRaw('SUM(estimated_cost) as cost')
            ->groupBy('model')
            ->orderByRaw('SUM(estimated_cost) DESC')
            ->get();
    }

    #[Computed]
    public function totalCost(): float
    {
        return (float) AiUsage::query()
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->sum('estimated_cost');
    }

    #[Computed]
    public function budgetExceeded(): bool
    {
        return \Illuminate\Support\Facades\Cache::has('ai_budget_exceeded:'.auth()->id());
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <flux:button variant="ghost" icon="arrow-left" :href="route('teacher.exams.index')" wire:navigate />
        <flux:heading size="xl">AI Usage</flux:heading>
    </div>

    @if ($this->budgetExceeded)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>Daily budget exceeded</flux:callout.heading>
            <flux:callout.text>AI features are paused until tomorrow. Your daily limit is ${{ number_format(config('ai.rate_limit.daily_budget', 5.00), 2) }}.</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Summary stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bento-flat p-5 text-center">
            <div class="text-3xl font-bold text-teal-700">${{ number_format($this->totalCost, 4) }}</div>
            <flux:text class="text-sm mt-1">Total Cost (7 days)</flux:text>
        </div>
        <div class="bento-flat p-5 text-center">
            <div class="text-3xl font-bold text-teal-700">{{ number_format($this->dailyUsage->sum('input_tokens') + $this->dailyUsage->sum('output_tokens')) }}</div>
            <flux:text class="text-sm mt-1">Total Tokens (7 days)</flux:text>
        </div>
        <div class="bento-flat p-5 text-center">
            <div class="text-3xl font-bold text-teal-700">{{ $this->modelBreakdown->count() }}</div>
            <flux:text class="text-sm mt-1">Models Used</flux:text>
        </div>
    </div>

    {{-- Daily breakdown --}}
    <div class="bento-flat space-y-3">
        <flux:heading size="lg">Daily Breakdown</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Input Tokens</flux:table.column>
                <flux:table.column>Output Tokens</flux:table.column>
                <flux:table.column>Cost</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->dailyUsage as $day)
                    <flux:table.row>
                        <flux:table.cell variant="strong">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($day->input_tokens) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($day->output_tokens) }}</flux:table.cell>
                        <flux:table.cell>${{ number_format($day->cost, 4) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <div class="py-10 text-center">
                                <flux:text>No AI usage recorded in the last 7 days.</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Model breakdown --}}
    @if ($this->modelBreakdown->isNotEmpty())
        <div class="bento-flat space-y-3">
            <flux:heading size="lg">Per-Model Breakdown</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Model</flux:table.column>
                    <flux:table.column>Input Tokens</flux:table.column>
                    <flux:table.column>Output Tokens</flux:table.column>
                    <flux:table.column>Cost</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->modelBreakdown as $row)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $row->model }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($row->input_tokens) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($row->output_tokens) }}</flux:table.cell>
                            <flux:table.cell>${{ number_format($row->cost, 4) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</div>
