<?php

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Question Bank')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $examFilter = '';
    public string $typeFilter = '';

    // "Add to exam" modal
    public bool $showAddModal = false;
    public ?int $selectedQuestionId = null;
    public string $targetExamId = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingExamFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function openAddToExam(int $questionId): void
    {
        $this->selectedQuestionId = $questionId;
        $this->targetExamId       = '';
        $this->showAddModal       = true;
    }

    public function copyToExam(): void
    {
        $this->validate(['targetExamId' => 'required|integer']);

        $source = Question::findOrFail($this->selectedQuestionId);
        $target = Exam::where('user_id', auth()->id())->findOrFail((int) $this->targetExamId);

        abort_unless($source->exam->user_id === auth()->id(), 403);

        $order = $target->questions()->max('order') + 1;

        $target->questions()->create([
            'question'       => $source->question,
            'type'           => $source->type->value,
            'options'        => $source->options,
            'correct_answer' => $source->correct_answer,
            'order'          => $order,
        ]);

        $this->showAddModal       = false;
        $this->selectedQuestionId = null;

        $this->dispatch('toast', variant: 'success', text: 'Question copied to "'.$target->title.'".');
    }

    #[Computed]
    public function questions(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Question::query()
            ->whereHas('exam', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('exam')
            ->when($this->search, fn ($q) => $q->where('question', 'ilike', '%'.$this->search.'%'))
            ->when($this->examFilter, fn ($q) => $q->where('exam_id', $this->examFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->orderBy('exam_id')
            ->orderBy('order')
            ->paginate(15);
    }

    #[Computed]
    public function teacherExams(): \Illuminate\Database\Eloquent\Collection
    {
        return Exam::where('user_id', auth()->id())
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    #[Computed]
    public function questionTypes(): array
    {
        return QuestionType::cases();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Question Bank</flux:heading>
            <flux:text>All questions across your exams</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('teacher.exams.index')" wire:navigate>
            My Exams
        </flux:button>
    </div>

    {{-- ── Filters ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <flux:field class="flex-1">
            <flux:label>Search</flux:label>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search questions…" icon="magnifying-glass" />
        </flux:field>

        <flux:field>
            <flux:label>Exam</flux:label>
            <flux:select wire:model.live="examFilter">
                <flux:select.option value="">All exams</flux:select.option>
                @foreach ($this->teacherExams as $exam)
                    <flux:select.option :value="$exam->id">{{ $exam->title }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>Type</flux:label>
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">All types</flux:select.option>
                @foreach ($this->questionTypes as $qType)
                    <flux:select.option :value="$qType->value">{{ $qType->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    {{-- ── Question List ── --}}
    <div class="space-y-3">
        @forelse ($this->questions as $q)
            <div class="bento-flat flex items-start justify-between gap-4" wire:key="qb-{{ $q->id }}">
                <div class="flex-1 space-y-1 min-w-0">
                    <flux:text class="font-medium">{{ $q->question }}</flux:text>
                    <div class="flex items-center gap-2 flex-wrap">
                        <flux:badge size="sm" color="zinc">{{ $q->exam->title }}</flux:badge>
                        <flux:badge size="sm" color="blue">{{ $q->type->label() }}</flux:badge>
                        <flux:text size="sm" class="text-zinc-500">Correct: {{ $q->correct_answer }}</flux:text>
                    </div>
                    @if ($q->options)
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach ($q->options as $opt)
                                <flux:badge size="sm" :color="$opt === $q->correct_answer ? 'green' : 'zinc'">
                                    {{ $opt }}
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="shrink-0">
                    <flux:button size="sm" variant="ghost" icon="document-duplicate"
                        wire:click="openAddToExam({{ $q->id }})">
                        Add to Exam
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed p-10 text-center" style="border-color:var(--color-border-hover)">
                <flux:text>No questions found.</flux:text>
            </div>
        @endforelse
    </div>

    {{ $this->questions->links() }}

    {{-- ── Add to Exam Modal ── --}}
    <flux:modal wire:model="showAddModal" name="add-to-exam">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Add to Exam</flux:heading>
                <flux:text>Choose which exam to copy this question into.</flux:text>
            </div>

            <flux:field>
                <flux:label>Target Exam</flux:label>
                <flux:select wire:model="targetExamId">
                    <flux:select.option value="">Select an exam…</flux:select.option>
                    @foreach ($this->teacherExams as $exam)
                        <flux:select.option :value="$exam->id">{{ $exam->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="targetExamId" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="copyToExam">Copy Question</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
