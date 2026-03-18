<?php

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manage Questions')] class extends Component {
    public Exam $exam;

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $question = '';
    public string $type = QuestionType::MultipleChoice->value;
    public string $correct_answer = '';

    /** @var array<int, string> */
    public array $options = ['', '', '', ''];

    public function mount(): void
    {
        abort_unless(auth()->id() === $this->exam->user_id, 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editQuestion(Question $question): void
    {
        $this->editingId = $question->id;
        $this->question = $question->question;
        $this->type = $question->type->value;
        $this->correct_answer = $question->correct_answer;
        $this->options = $question->options ?? ['', '', '', ''];
        $this->showForm = true;
    }

    public function saveQuestion(): void
    {
        $rules = [
            'question' => 'required|string|max:1000',
            'type' => 'required|string|in:'.implode(',', array_column(QuestionType::cases(), 'value')),
            'correct_answer' => 'required|string|max:500',
        ];

        if (QuestionType::from($this->type)->hasOptions()) {
            $rules['options'] = 'required|array|min:2';
            $rules['options.*'] = 'required|string|max:500';
        }

        $this->validate($rules);

        $data = [
            'question' => $this->question,
            'type' => $this->type,
            'correct_answer' => $this->correct_answer,
            'options' => QuestionType::from($this->type)->hasOptions() ? array_values(array_filter($this->options)) : null,
        ];

        if ($this->editingId) {
            Question::findOrFail($this->editingId)->update($data);
        } else {
            $order = $this->exam->questions()->max('order') + 1;
            $this->exam->questions()->create([...$data, 'order' => $order]);
        }

        $this->resetForm();
    }

    public function deleteQuestion(Question $question): void
    {
        abort_unless($question->exam_id === $this->exam->id, 403);
        $question->delete();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->question = '';
        $this->type = QuestionType::MultipleChoice->value;
        $this->correct_answer = '';
        $this->options = ['', '', '', ''];
        $this->resetValidation();
    }

    #[Computed]
    public function questions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->exam->questions()->get();
    }

    #[Computed]
    public function questionTypes(): array
    {
        return QuestionType::cases();
    }

    #[Computed]
    public function currentTypeHasOptions(): bool
    {
        return QuestionType::from($this->type)->hasOptions();
    }
}; ?>

<div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <flux:button variant="ghost" icon="arrow-left" :href="route('teacher.exams.index')" wire:navigate />
                <div>
                    <flux:heading size="xl">{{ $exam->title }}</flux:heading>
                    <flux:text>{{ $this->questions->count() }} question(s)</flux:text>
                </div>
            </div>
            @if (!$showForm)
                <flux:button variant="primary" icon="plus" wire:click="openCreate">Add Question</flux:button>
            @endif
        </div>

        @if ($showForm)
            <div class="bento-flat space-y-4">
                <flux:heading size="lg">{{ $editingId ? 'Edit Question' : 'New Question' }}</flux:heading>

                <flux:field>
                    <flux:label>Question</flux:label>
                    <flux:textarea wire:model="question" placeholder="Enter your question…" rows="3" autofocus />
                    <flux:error name="question" />
                </flux:field>

                <flux:field>
                    <flux:label>Type</flux:label>
                    <flux:select wire:model.live="type">
                        @foreach ($this->questionTypes as $qType)
                            <flux:select.option :value="$qType->value">{{ $qType->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>

                @if ($this->currentTypeHasOptions)
                    <div class="space-y-2">
                        <flux:label>Answer Options</flux:label>
                        @foreach ($options as $i => $option)
                            <flux:input
                                wire:model="options.{{ $i }}"
                                placeholder="Option {{ $i + 1 }}"
                                :key="'opt-'.$i"
                            />
                        @endforeach
                        <flux:error name="options" />
                    </div>
                @endif

                <flux:field>
                    <flux:label>Correct Answer</flux:label>
                    <flux:input wire:model="correct_answer" placeholder="Enter the correct answer exactly as written" />
                    <flux:error name="correct_answer" />
                </flux:field>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="cancel">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="saveQuestion">
                        {{ $editingId ? 'Update Question' : 'Add Question' }}
                    </flux:button>
                </div>
            </div>
        @endif

        <div class="space-y-3">
            @forelse ($this->questions as $q)
                <div class="bento-flat flex items-start justify-between gap-4" wire:key="question-{{ $q->id }}">
                    <div class="flex-1 space-y-1">
                        <flux:text class="font-medium">{{ $q->question }}</flux:text>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="blue">{{ $q->type->label() }}</flux:badge>
                            <flux:text size="sm" class="text-zinc-500">Correct: {{ $q->correct_answer }}</flux:text>
                        </div>
                        @if ($q->options)
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach ($q->options as $opt)
                                    <flux:badge
                                        size="sm"
                                        :color="$opt === $q->correct_answer ? 'green' : 'zinc'"
                                    >
                                        {{ $opt }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editQuestion({{ $q->id }})" />
                        <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:click="deleteQuestion({{ $q->id }})" />
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed p-10 text-center" style="border-color:var(--color-border-hover)">
                    <flux:text>No questions yet. Add your first question above.</flux:text>
                </div>
            @endforelse
        </div>
    </div>
