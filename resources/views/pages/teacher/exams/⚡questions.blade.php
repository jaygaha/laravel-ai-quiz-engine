<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Enums\QuestionType;
use App\Jobs\ProcessGeneratedQuestionsJob;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Enums\Lab;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Manage Questions')] class extends Component {
    public Exam $exam;

    // --- Manual form ---
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $question = '';
    public string $type = QuestionType::MultipleChoice->value;
    public string $correct_answer = '';

    /** @var array<int, string> */
    public array $options = ['', '', '', ''];

    // --- AI generation panel ---
    public bool $showAiPanel = false;

    #[Validate('required|string|max:200')]
    public string $aiTopic = '';

    public string $aiType = QuestionType::MultipleChoice->value;

    #[Validate('required|integer|min:1|max:10')]
    public int $aiCount = 5;

    #[Validate('required|integer|min:1|max:5')]
    public int $aiDifficulty = 3;

    public bool $aiGenerating = false;
    public string $aiError = '';

    /** @var array<int, array<string, mixed>> */
    public array $pendingAiQuestions = [];

    public function mount(): void
    {
        abort_unless(auth()->id() === $this->exam->user_id, 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showAiPanel = false;
        $this->showForm = true;
    }

    public function toggleAiPanel(): void
    {
        $this->showAiPanel = ! $this->showAiPanel;
        $this->showForm = false;
        $this->aiError = '';
    }

    public function editQuestion(Question $question): void
    {
        $this->editingId = $question->id;
        $this->question = $question->question;
        $this->type = $question->type->value;
        $this->correct_answer = $question->correct_answer;
        $this->options = $question->options ?? ['', '', '', ''];
        $this->showForm = true;
        $this->showAiPanel = false;
    }

    public function saveQuestion(): void
    {
        $rules = [
            'question'      => 'required|string|max:1000',
            'type'          => 'required|string|in:'.implode(',', array_column(QuestionType::cases(), 'value')),
            'correct_answer' => 'required|string|max:500',
        ];

        if (QuestionType::from($this->type)->hasOptions()) {
            $rules['options']   = 'required|array|min:2';
            $rules['options.*'] = 'required|string|max:500';
        }

        $this->validate($rules);

        $data = [
            'question'       => $this->question,
            'type'           => $this->type,
            'correct_answer' => $this->correct_answer,
            'options'        => QuestionType::from($this->type)->hasOptions() ? array_values(array_filter($this->options)) : null,
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

    public function generateWithAi(): void
    {
        $this->validateOnly('aiTopic');
        $this->validateOnly('aiCount');
        $this->validateOnly('aiDifficulty');

        $this->aiError = '';
        $this->aiGenerating = true;
        $this->pendingAiQuestions = [];

        try {
            $agent = new QuestionGeneratorAgent(
                topic: $this->aiTopic,
                type: $this->aiType,
                count: $this->aiCount,
                difficulty: $this->aiDifficulty,
            );

            $providers = $this->resolvedProviders();

            if ($this->aiCount > 5) {
                $agent->queue(
                    "Generate {$this->aiCount} questions about {$this->aiTopic}.",
                    provider: $providers,
                )->then(function ($response) {
                    $data = json_decode($response->text, true);
                    ProcessGeneratedQuestionsJob::dispatch(
                        $this->exam->id,
                        $data['questions'] ?? [],
                    );
                });

                session()->flash('status', "Generating {$this->aiCount} questions in the background. Refresh in a moment.");
            } else {
                $response = $agent->prompt(
                    "Generate {$this->aiCount} questions about {$this->aiTopic}.",
                    provider: $providers,
                );

                $this->pendingAiQuestions = $response['questions'] ?? [];
            }
        } catch (\Throwable $e) {
            $this->aiError = 'AI generation failed. Please try again or add questions manually.';
        } finally {
            $this->aiGenerating = false;
        }
    }

    public function confirmAiQuestion(int $index): void
    {
        $data = $this->pendingAiQuestions[$index] ?? null;

        if (! $data) {
            return;
        }

        $type = QuestionType::tryFrom($data['type'] ?? '') ?? QuestionType::ShortAnswer;
        $order = $this->exam->questions()->max('order') + 1;

        $this->exam->questions()->create([
            'question'       => $data['question'],
            'type'           => $type->value,
            'options'        => $type->hasOptions() && ! empty($data['options']) ? $data['options'] : null,
            'correct_answer' => $data['correct_answer'],
            'order'          => $order,
        ]);

        unset($this->pendingAiQuestions[$index]);
        $this->pendingAiQuestions = array_values($this->pendingAiQuestions);
    }

    public function confirmAllAiQuestions(): void
    {
        $nextOrder = $this->exam->questions()->max('order') + 1;

        foreach ($this->pendingAiQuestions as $data) {
            $type = QuestionType::tryFrom($data['type'] ?? '') ?? QuestionType::ShortAnswer;

            $this->exam->questions()->create([
                'question'       => $data['question'],
                'type'           => $type->value,
                'options'        => $type->hasOptions() && ! empty($data['options']) ? $data['options'] : null,
                'correct_answer' => $data['correct_answer'],
                'order'          => $nextOrder++,
            ]);
        }

        $this->pendingAiQuestions = [];
    }

    public function discardAiQuestion(int $index): void
    {
        unset($this->pendingAiQuestions[$index]);
        $this->pendingAiQuestions = array_values($this->pendingAiQuestions);
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

    /**
     * Return the ordered provider list: Anthropic → Gemini → Ollama (local dev).
     *
     * @return array<int, Lab>
     */
    private function resolvedProviders(): array
    {
        $providers = [Lab::Anthropic, Lab::Gemini, Lab::OpenAI];

        if (config('ai.providers.ollama.url')) {
            $providers[] = Lab::Ollama;
        }

        return $providers;
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
        @if (!$showForm && !$showAiPanel)
            <div class="flex items-center gap-2">
                <flux:button variant="filled" icon="sparkles" wire:click="toggleAiPanel">
                    Generate with AI
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreate">Add Question</flux:button>
            </div>
        @elseif ($showAiPanel)
            <flux:button variant="ghost" wire:click="toggleAiPanel">Cancel</flux:button>
        @endif
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
    @endif

    {{-- AI Generation Panel --}}
    @if ($showAiPanel)
        <div class="bento-flat space-y-4">
            <div class="flex items-center gap-2">
                <flux:icon.sparkles class="text-teal-600 size-5" />
                <flux:heading size="lg">Generate Questions with AI</flux:heading>
            </div>

            @if ($aiError)
                <flux:callout variant="danger" icon="x-circle" heading="{{ $aiError }}" />
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Topic</flux:label>
                    <flux:input
                        wire:model="aiTopic"
                        placeholder="e.g. PHP arrays, World War II, Photosynthesis…"
                        autofocus
                    />
                    <flux:error name="aiTopic" />
                </flux:field>

                <flux:field>
                    <flux:label>Question Type</flux:label>
                    <flux:select wire:model="aiType">
                        @foreach ($this->questionTypes as $qType)
                            <flux:select.option :value="$qType->value">{{ $qType->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Number of Questions</flux:label>
                    <flux:input wire:model="aiCount" type="number" min="1" max="10" />
                    <flux:error name="aiCount" />
                </flux:field>

                <flux:field>
                    <flux:label>Difficulty (1 = Easy · 5 = Expert)</flux:label>
                    <flux:select wire:model="aiDifficulty">
                        <flux:select.option value="1">1 — Very Easy</flux:select.option>
                        <flux:select.option value="2">2 — Easy</flux:select.option>
                        <flux:select.option value="3">3 — Medium</flux:select.option>
                        <flux:select.option value="4">4 — Hard</flux:select.option>
                        <flux:select.option value="5">5 — Expert</flux:select.option>
                    </flux:select>
                    <flux:error name="aiDifficulty" />
                </flux:field>
            </div>

            <div class="flex justify-end">
                <flux:button
                    variant="primary"
                    icon="sparkles"
                    wire:click="generateWithAi"
                    wire:loading.attr="disabled"
                    wire:target="generateWithAi"
                >
                    <span wire:loading.remove wire:target="generateWithAi">Generate</span>
                    <span wire:loading wire:target="generateWithAi">Generating…</span>
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Pending AI Questions --}}
    @if (count($pendingAiQuestions) > 0)
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Review Generated Questions</flux:heading>
                <flux:button variant="primary" size="sm" wire:click="confirmAllAiQuestions">
                    Add All ({{ count($pendingAiQuestions) }})
                </flux:button>
            </div>

            @foreach ($pendingAiQuestions as $i => $pq)
                <div class="bento-flat space-y-2 border-teal-200 bg-teal-50" wire:key="pending-{{ $i }}">
                    <div class="flex items-start justify-between gap-3">
                        <flux:text class="font-medium">{{ $pq['question'] }}</flux:text>
                        <flux:badge size="sm" color="teal">AI</flux:badge>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <flux:badge size="sm" color="blue">
                            {{ QuestionType::tryFrom($pq['type'] ?? '')?->label() ?? $pq['type'] }}
                        </flux:badge>
                        <flux:badge size="sm" color="zinc">Difficulty: {{ $pq['difficulty'] ?? '?' }}</flux:badge>
                        <flux:text size="sm" class="text-zinc-500">Correct: {{ $pq['correct_answer'] }}</flux:text>
                    </div>

                    @if (!empty($pq['options']))
                        <div class="flex flex-wrap gap-1">
                            @foreach ($pq['options'] as $opt)
                                <flux:badge
                                    size="sm"
                                    :color="$opt === $pq['correct_answer'] ? 'green' : 'zinc'"
                                >{{ $opt }}</flux:badge>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($pq['explanation']))
                        <flux:text size="sm" class="text-zinc-500 italic">{{ $pq['explanation'] }}</flux:text>
                    @endif

                    <div class="flex items-center gap-2 pt-1">
                        <flux:button size="sm" variant="primary" wire:click="confirmAiQuestion({{ $i }})">
                            Add to Exam
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="discardAiQuestion({{ $i }})">
                            Discard
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Manual Question Form --}}
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

    {{-- Question List --}}
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
                                >{{ $opt }}</flux:badge>
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
