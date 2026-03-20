<?php

use App\Models\Exam;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Exams')] class extends Component {
    use WithPagination;

    public string $search = '';

    /**
     * Tracks live submission count increments received via Reverb.
     * Keyed by exam ID so only the relevant row updates.
     *
     * @var array<int, array{student_count: int, latest_student_name: string}>
     */
    public array $liveSubmissions = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function togglePublish(Exam $exam): void
    {
        abort_unless(auth()->id() === $exam->user_id, 403);

        $exam->update([
            'published_at' => $exam->isPublished() ? null : now(),
        ]);
    }

    public function deleteExam(Exam $exam): void
    {
        abort_unless(auth()->id() === $exam->user_id, 403);

        $exam->delete();
    }

    /** Receive broadcast from AttemptSubmittedEvent via echo channel. */
    #[On('exam-attempt-submitted')]
    public function handleAttemptSubmitted(int $examId, int $studentCount, string $latestStudentName): void
    {
        $this->liveSubmissions[$examId] = [
            'student_count'       => $studentCount,
            'latest_student_name' => $latestStudentName,
        ];
    }

    #[Computed]
    public function exams(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return auth()->user()->exams()
            ->withCount('questions', 'attempts')
            ->when($this->search, fn ($q) => $q->where('title', 'ilike', '%'.$this->search.'%'))
            ->latest()
            ->paginate(10);
    }
}; ?>

<div
    class="flex flex-col gap-6"
    x-data="{
        initEcho() {
            if (typeof Echo === 'undefined') return;
            @foreach ($this->exams as $exam)
            Echo.channel('exam.{{ $exam->id }}')
                .listen('AttemptSubmittedEvent', (e) => {
                    $wire.dispatch('exam-attempt-submitted', {
                        examId: {{ $exam->id }},
                        studentCount: e.student_count,
                        latestStudentName: e.latest_student_name,
                    });
                });
            @endforeach
        }
    }"
    x-init="initEcho()"
>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">My Exams</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('teacher.exams.create')" wire:navigate>
            New Exam
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce="search" placeholder="Search exams…" icon="magnifying-glass" clearable />

    <flux:table :paginate="$this->exams">
        <flux:table.columns>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Questions</flux:table.column>
            <flux:table.column>Submitted</flux:table.column>
            <flux:table.column>Time Limit</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->exams as $exam)
                @php $live = $liveSubmissions[$exam->id] ?? null; @endphp
                <flux:table.row :key="$exam->id">
                    <flux:table.cell variant="strong">{{ $exam->title }}</flux:table.cell>
                    <flux:table.cell>{{ $exam->questions_count }}</flux:table.cell>

                    {{-- Live submission counter --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-1.5">
                            <span>{{ $live ? $live['student_count'] : $exam->attempts_count }}</span>
                            @if ($live)
                                <span class="inline-flex items-center gap-1 text-xs text-teal-700 font-medium animate-pulse">
                                    <span class="size-1.5 rounded-full bg-teal-500 inline-block"></span>
                                    live
                                </span>
                            @endif
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $exam->time_limit ? $exam->time_limit.' min' : '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($exam->isPublished())
                            <flux:badge color="green" size="sm">Published</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Draft</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="pencil-square"
                                :href="route('teacher.exams.edit', $exam)"
                                wire:navigate
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="list-bullet"
                                :href="route('teacher.exams.questions', $exam)"
                                wire:navigate
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="chart-bar"
                                :href="route('teacher.exams.results', $exam)"
                                wire:navigate
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                :icon="$exam->isPublished() ? 'eye-slash' : 'eye'"
                                wire:click="togglePublish({{ $exam->id }})"
                            />
                            <flux:modal.trigger :name="'delete-exam-'.$exam->id">
                                <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" />
                            </flux:modal.trigger>
                        </div>

                        <flux:modal :name="'delete-exam-'.$exam->id" class="md:w-80">
                            <div class="space-y-4">
                                <flux:heading size="lg">Delete Exam</flux:heading>
                                <flux:text>
                                    Are you sure you want to delete <strong>{{ $exam->title }}</strong>? This cannot be undone.
                                </flux:text>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button
                                        variant="danger"
                                        wire:click="deleteExam({{ $exam->id }})"
                                    >
                                        Delete
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="py-10 text-center">
                            <flux:text>
                                No exams yet.
                                <flux:link :href="route('teacher.exams.create')" wire:navigate>
                                    Create your first exam.
                                </flux:link>
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
