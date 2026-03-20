<?php

use App\Models\Exam;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Edit Exam')] class extends Component {
    public Exam $exam;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:5000')]
    public string $description = '';

    #[Validate('nullable|integer|min:1|max:600')]
    public ?int $time_limit = null;

    public bool $leaderboard_enabled = false;

    public function mount(): void
    {
        abort_unless(auth()->id() === $this->exam->user_id, 403);

        $this->title               = $this->exam->title;
        $this->description         = $this->exam->description ?? '';
        $this->time_limit          = $this->exam->time_limit;
        $this->leaderboard_enabled = $this->exam->leaderboard_enabled;
    }

    public function save(): void
    {
        $this->validate();

        $this->exam->update([
            'title'               => $this->title,
            'description'         => $this->description ?: null,
            'time_limit'          => $this->time_limit,
            'leaderboard_enabled' => $this->leaderboard_enabled,
        ]);

        $this->redirect(route('teacher.exams.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-2xl flex flex-col gap-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('teacher.exams.index')" wire:navigate />
            <flux:heading size="xl">Edit Exam</flux:heading>
        </div>

        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" autofocus />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Description <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                <flux:textarea wire:model="description" rows="4" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>Time Limit (minutes) <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                <flux:input wire:model="time_limit" type="number" min="1" max="600" />
                <flux:description>Leave blank for no time limit.</flux:description>
                <flux:error name="time_limit" />
            </flux:field>

            <flux:field variant="inline">
                <flux:switch wire:model="leaderboard_enabled" />
                <div>
                    <flux:label>Enable Live Leaderboard</flux:label>
                    <flux:description>Students can see the top-10 scores update in real time after submitting.</flux:description>
                </div>
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button
                    variant="ghost"
                    icon="eye"
                    :href="route('student.exams.take', $exam) . '?preview=1'"
                    wire:navigate
                >
                    Preview
                </flux:button>
                <flux:button
                    variant="ghost"
                    :href="route('teacher.exams.questions', $exam)"
                    wire:navigate
                    icon="list-bullet"
                >
                    Manage Questions
                </flux:button>
                <flux:button variant="primary" type="submit">Save Changes</flux:button>
            </div>
        </form>
    </div>
