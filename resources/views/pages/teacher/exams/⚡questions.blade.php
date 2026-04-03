<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Ai\ResolvedProviders;
use App\Enums\QuestionType;
use App\Jobs\ImportQuestionsFromCsvJob;
use App\Jobs\ProcessGeneratedQuestionsJob;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Manage Questions')] class extends Component {
    use WithFileUploads;

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

    // --- CSV Import ---
    #[Validate('file|mimes:csv,txt|max:512')]
    public mixed $csvFile = null;

    // --- Bulk selection ---
    /** @var array<int, int> */
    public array $selectedQuestions = [];

    public bool $selectAll = false;

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
            'question'       => 'required|string|max:1000',
            'type'           => 'required|string|in:'.implode(',', array_column(QuestionType::cases(), 'value')),
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

    public function updatedSelectAll(): void
    {
        $this->selectedQuestions = $this->selectAll
            ? $this->questions->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedQuestions)) {
            return;
        }

        $count = Question::query()
            ->whereIn('id', $this->selectedQuestions)
            ->where('exam_id', $this->exam->id)
            ->delete();

        $this->selectedQuestions = [];
        $this->selectAll = false;
        unset($this->questions);

        $this->modal('confirm-bulk-delete')->close();
        $this->dispatch('toast', variant: 'success', heading: 'Deleted', text: "{$count} question(s) deleted.");
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function importCsv(): void
    {
        $this->validateOnly('csvFile');

        $path = $this->csvFile->storeAs(
            'imports',
            'csv-'.$this->exam->id.'-'.time().'.csv',
            'local',
        );

        ImportQuestionsFromCsvJob::dispatchSync($this->exam->id, $path);

        $this->csvFile = null;

        unset($this->questions);

        $this->modal('csv-import')->close();

        $this->dispatch('toast', variant: 'success', heading: 'Import complete', text: 'CSV questions have been imported.');
    }

    /**
     * Queued generation path — used for batches > 5.
     * Also kept for backward-compat with existing tests.
     */
    public function generateWithAi(): void
    {
        if (Cache::has('ai_budget_exceeded:'.auth()->id())) {
            $this->aiError = 'Daily AI budget exceeded. Please try again tomorrow.';

            return;
        }

        if (! RateLimiter::attempt('ai:'.auth()->id(), config('ai.rate_limit.per_minute', 30), fn () => true)) {
            $this->aiError = 'Too many AI requests. Please wait a moment.';

            return;
        }

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

            $examId = $this->exam->id;

            $agent->queue(
                "Generate {$this->aiCount} questions about {$this->aiTopic}.",
                provider: ResolvedProviders::list(),
            )->then(function ($response) use ($examId) {
                $data = json_decode($response->text, true);
                ProcessGeneratedQuestionsJob::dispatch(
                    $examId,
                    $data['questions'] ?? [],
                );
            });

            $this->dispatch('toast', heading: 'Queued', text: "Generating {$this->aiCount} questions in the background. Refresh in a moment.");
        } catch (\Throwable $e) {
            logger()->error('AI generation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->aiError = 'AI generation failed. Please try again or add questions manually.';
        } finally {
            $this->aiGenerating = false;
        }
    }

    /**
     * Synchronous generation path — uses prompt() instead of stream() because
     * QuestionGeneratorAgent uses HasStructuredOutput which does not support
     * streaming on all providers (e.g. Gemini).
     */
    public function streamGenerateWithAi(): void
    {
        if (Cache::has('ai_budget_exceeded:'.auth()->id())) {
            $this->aiError = 'Daily AI budget exceeded. Please try again tomorrow.';

            return;
        }

        if (! RateLimiter::attempt('ai:'.auth()->id(), config('ai.rate_limit.per_minute', 30), fn () => true)) {
            $this->aiError = 'Too many AI requests. Please wait a moment.';

            return;
        }

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

            $response = $agent->prompt(
                "Generate {$this->aiCount} questions about {$this->aiTopic}.",
                provider: ResolvedProviders::list(),
            );

            $data = json_decode($response->text, true);
            $this->pendingAiQuestions = $data['questions'] ?? [];

            if (count($this->pendingAiQuestions) > 0) {
                $this->saveToHistory();
            }
        } catch (\Throwable $e) {
            logger()->error('AI generation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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

        $type  = QuestionType::tryFrom($data['type'] ?? '') ?? QuestionType::ShortAnswer;
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

    /** Re-populate pendingAiQuestions from a previous generation batch stored in session. */
    public function loadFromHistory(int $index): void
    {
        $history = session("ai_gen_history_{$this->exam->id}", []);

        if (! isset($history[$index])) {
            return;
        }

        $this->pendingAiQuestions = $history[$index]['questions'];
        $this->aiTopic            = $history[$index]['topic'];
        $this->aiType             = $history[$index]['type'];
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
     * Last 5 generation batches for this exam, keyed in session.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function aiHistory(): array
    {
        return session("ai_gen_history_{$this->exam->id}", []);
    }

    /** Persist the latest batch to session history (max 5 entries per exam). */
    private function saveToHistory(): void
    {
        $key     = "ai_gen_history_{$this->exam->id}";
        $history = session($key, []);

        array_unshift($history, [
            'topic'        => $this->aiTopic,
            'type'         => $this->aiType,
            'count'        => count($this->pendingAiQuestions),
            'questions'    => $this->pendingAiQuestions,
            'generated_at' => now()->format('H:i'),
        ]);

        session([$key => array_slice($history, 0, 5)]);
    }

    private function resetForm(): void
    {
        $this->showForm     = false;
        $this->editingId    = null;
        $this->question     = '';
        $this->type         = QuestionType::MultipleChoice->value;
        $this->correct_answer = '';
        $this->options      = ['', '', '', ''];
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
                <flux:button
                    variant="ghost"
                    icon="eye"
                    :href="route('student.exams.take', $exam) . '?preview=1'"
                    wire:navigate
                >
                    Preview
                </flux:button>
                <flux:modal.trigger name="csv-import">
                    <flux:button variant="ghost" icon="arrow-up-tray">Import CSV</flux:button>
                </flux:modal.trigger>
                <flux:button variant="filled" icon="sparkles" wire:click="toggleAiPanel">
                    Generate with AI
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreate">Add Question</flux:button>
            </div>
        @elseif ($showAiPanel)
            <flux:button variant="ghost" wire:click="toggleAiPanel">Cancel</flux:button>
        @endif
    </div>

    {{-- ── AI Generation Panel ── --}}
    @if ($showAiPanel)
        <div
            class="bento-flat space-y-4"
            x-data="{ generating: $wire.$entangle('aiGenerating') }"
        >
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
                    <flux:description>Up to 5 stream live · 6–10 run in background</flux:description>
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

            <div class="flex items-center justify-between">
                {{-- Generate button --}}
                <flux:button
                    variant="primary"
                    x-on:click="generating = true; $wire.streamGenerateWithAi()"
                    x-bind:disabled="generating"
                    :loading="false"
                >
                    <span x-show="!generating" class="inline-flex items-center gap-1">
                        <flux:icon.sparkles class="size-4" />
                        Generate
                    </span>
                    <span x-show="generating" x-cloak class="inline-flex items-center gap-1">
                        <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        Generating…
                    </span>
                </flux:button>

                <flux:text x-show="generating" x-cloak size="sm" class="text-charcoal-400 animate-pulse">
                    Generating questions… please wait.
                </flux:text>
            </div>

            {{-- Skeleton cards shown while generating --}}
            <div x-show="generating" x-cloak class="space-y-3">
                @for ($s = 0; $s < max(min($aiCount, 3), 1); $s++)
                    <div class="bento-flat animate-pulse space-y-3 border-teal-100 bg-teal-50/40">
                        <div class="h-4 bg-teal-100 rounded w-3/4"></div>
                        <div class="flex gap-2">
                            <div class="h-3 bg-teal-100 rounded w-16"></div>
                            <div class="h-3 bg-teal-100 rounded w-20"></div>
                        </div>
                        <div class="h-3 bg-teal-100 rounded w-1/2"></div>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    {{-- ── Generation History ── --}}
    @if ($showAiPanel && count($this->aiHistory) > 0 && ! $aiGenerating && count($pendingAiQuestions) === 0)
        <div class="bento-flat space-y-3">
            <flux:heading size="sm">Recent Generations</flux:heading>
            <div class="space-y-2">
                @foreach ($this->aiHistory as $hi => $batch)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <flux:badge size="sm" color="zinc">{{ $batch['generated_at'] }}</flux:badge>
                            <flux:text size="sm">
                                <strong>{{ $batch['topic'] }}</strong>
                                · {{ $batch['count'] }} question(s)
                                · {{ QuestionType::from($batch['type'])->label() }}
                            </flux:text>
                        </div>
                        <flux:button size="sm" variant="ghost" wire:click="loadFromHistory({{ $hi }})">
                            Re-use
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Pending AI Questions ── --}}
    @if (count($pendingAiQuestions) > 0)
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">Review Generated Questions</flux:heading>
                </div>
                <flux:button variant="primary" size="sm" wire:click="confirmAllAiQuestions">
                    <span wire:loading.remove wire:target="confirmAllAiQuestions">Add All ({{ count($pendingAiQuestions) }})</span>
                    <span wire:loading wire:target="confirmAllAiQuestions" class="inline-flex items-center gap-1">
                        <svg class="animate-spin size-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        Adding…
                    </span>
                </flux:button>
            </div>

            @foreach ($pendingAiQuestions as $i => $pq)
                <div
                    class="bento-flat space-y-2 border-teal-200 bg-teal-50"
                    wire:key="pending-{{ $i }}"
                    x-data x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
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
                                <flux:badge size="sm" :color="$opt === $pq['correct_answer'] ? 'green' : 'zinc'">
                                    {{ $opt }}
                                </flux:badge>
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

    {{-- ── Manual Question Form ── --}}
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

    {{-- ── CSV Import Modal ── --}}
    <flux:modal name="csv-import">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Import Questions from CSV</flux:heading>
                <flux:text>
                    Upload a CSV with columns:
                    <code class="text-xs bg-zinc-100 px-1 rounded">question, type, options, correct_answer</code>.
                    Options for multiple-choice should be pipe-separated (e.g. <code class="text-xs bg-zinc-100 px-1 rounded">A|B|C|D</code>).
                    Malformed rows are skipped.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>CSV File</flux:label>
                <flux:input type="file" wire:model="csvFile" accept=".csv,.txt" />
                <flux:error name="csvFile" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="importCsv" icon="arrow-up-tray">
                    <span wire:loading.remove wire:target="importCsv">Import</span>
                    <span wire:loading wire:target="importCsv" class="inline-flex items-center gap-1">
                        <svg class="animate-spin size-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        Importing…
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Question List ── --}}
    <div class="space-y-3">
        @if ($this->questions->isNotEmpty())
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        wire:model.live="selectAll"
                        class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                    />
                    <flux:text size="sm">Select All</flux:text>
                </label>
                @if (count($selectedQuestions) > 0)
                    <flux:modal.trigger name="confirm-bulk-delete">
                        <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500">
                            Delete Selected ({{ count($selectedQuestions) }})
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        @endif

        @forelse ($this->questions as $q)
            <div class="bento-flat flex items-start gap-4" wire:key="question-{{ $q->id }}">
                <div class="flex items-center pt-0.5 shrink-0">
                    <input
                        type="checkbox"
                        wire:model.live="selectedQuestions"
                        value="{{ $q->id }}"
                        class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                    />
                </div>
                <div class="flex-1 space-y-1">
                    <flux:text class="font-medium">{{ $q->question }}</flux:text>
                    <div class="flex items-center gap-2">
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

    {{-- ── Confirm Bulk Delete Modal ── --}}
    <flux:modal name="confirm-bulk-delete" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Delete Selected Questions</flux:heading>
                <flux:text class="mt-1">
                    Are you sure you want to delete {{ count($selectedQuestions) }} question(s)? This cannot be undone.
                </flux:text>
            </div>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" class="!bg-red-600 hover:!bg-red-700" wire:click="deleteSelected">
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
