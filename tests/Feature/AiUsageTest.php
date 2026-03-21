<?php

use App\Ai\Agents\AutoGraderAgent;
use App\Listeners\LogAiUsageListener;
use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Livewire\Livewire;

function createAgentPromptedEvent(string $invocationId = 'inv-1'): AgentPrompted
{
    $usage = new Usage(promptTokens: 100, completionTokens: 50);
    $meta = new Meta(provider: 'anthropic', model: 'claude-haiku-4-5-20251001');
    $response = new AgentResponse($invocationId, 'response text', $usage, $meta);

    $agent = new AutoGraderAgent('What is PHP?', 'A programming language');
    $provider = mock(TextProvider::class);
    $prompt = new AgentPrompt($agent, 'test prompt', [], $provider, 'claude-haiku-4-5-20251001');

    return new AgentPrompted($invocationId, $prompt, $response);
}

it('LogAiUsageListener creates AiUsage record on agent event', function () {
    $teacher = User::factory()->teacher()->create();
    $this->actingAs($teacher);

    $event = createAgentPromptedEvent();

    (new LogAiUsageListener)->handle($event);

    expect(AiUsage::count())->toBe(1);

    $record = AiUsage::first();
    expect($record->user_id)->toBe($teacher->id)
        ->and($record->agent)->toBe('AutoGraderAgent')
        ->and($record->model)->toBe('claude-haiku-4-5-20251001')
        ->and($record->input_tokens)->toBe(100)
        ->and($record->output_tokens)->toBe(50)
        ->and((float) $record->estimated_cost)->toBeGreaterThan(0);
});

it('budget exceeded flag prevents further AI calls', function () {
    $teacher = User::factory()->teacher()->create();

    Cache::put("ai_budget_exceeded:{$teacher->id}", true, now()->addHour());

    expect(Cache::has("ai_budget_exceeded:{$teacher->id}"))->toBeTrue();
});

it('AI usage page shows data for authenticated teacher', function () {
    $teacher = User::factory()->teacher()->create();

    AiUsage::create([
        'user_id' => $teacher->id,
        'agent' => 'AutoGraderAgent',
        'model' => 'claude-haiku-4-5-20251001',
        'input_tokens' => 500,
        'output_tokens' => 200,
        'estimated_cost' => 0.001500,
    ]);

    $component = Livewire::actingAs($teacher)
        ->test('pages::teacher.ai-usage');

    $component->assertStatus(200)
        ->assertSee('AI Usage')
        ->assertSee('claude-haiku');
});

it('AI usage page excludes other users data', function () {
    $teacher1 = User::factory()->teacher()->create();
    $teacher2 = User::factory()->teacher()->create();

    AiUsage::create([
        'user_id' => $teacher2->id,
        'agent' => 'AutoGraderAgent',
        'model' => 'claude-haiku-4-5-20251001',
        'input_tokens' => 500,
        'output_tokens' => 200,
        'estimated_cost' => 0.001500,
    ]);

    $component = Livewire::actingAs($teacher1)
        ->test('pages::teacher.ai-usage');

    expect($component->instance()->totalCost)->toBe(0.0);
});

it('daily budget exceeded sets cache flag', function () {
    $teacher = User::factory()->teacher()->create();
    $this->actingAs($teacher);

    config(['ai.rate_limit.daily_budget' => 0.001]);

    AiUsage::create([
        'user_id' => $teacher->id,
        'agent' => 'AutoGraderAgent',
        'model' => 'claude-haiku-4-5-20251001',
        'input_tokens' => 1000,
        'output_tokens' => 500,
        'estimated_cost' => 0.01,
    ]);

    $event = createAgentPromptedEvent('inv-2');

    (new LogAiUsageListener)->handle($event);

    expect(Cache::has("ai_budget_exceeded:{$teacher->id}"))->toBeTrue();
});
