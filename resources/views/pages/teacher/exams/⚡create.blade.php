<?php

use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Create Exam')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:5000')]
    public string $description = '';

    #[Validate('nullable|integer|min:1|max:600')]
    public ?int $time_limit = null;

    public function save(): void
    {
        $this->validate();

        $exam = auth()->user()->exams()->create([
            'title' => $this->title,
            'description' => $this->description ?: null,
            'time_limit' => $this->time_limit,
        ]);

        $this->redirect(route('teacher.exams.questions', $exam), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-2xl flex flex-col gap-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('teacher.exams.index')" wire:navigate />
            <flux:heading size="xl">Create Exam</flux:heading>
        </div>

        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" placeholder="e.g. Introduction to Algebra" autofocus />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Description <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                <flux:textarea wire:model="description" placeholder="Describe what this exam covers…" rows="4" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>Time Limit (minutes) <flux:badge size="sm" color="zinc">Optional</flux:badge></flux:label>
                <flux:input wire:model="time_limit" type="number" min="1" max="600" placeholder="e.g. 60" />
                <flux:description>Leave blank for no time limit.</flux:description>
                <flux:error name="time_limit" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('teacher.exams.index')" wire:navigate>Cancel</flux:button>
                <flux:button variant="primary" type="submit" icon="arrow-right">
                    Save & Add Questions
                </flux:button>
            </div>
        </form>
    </div>
