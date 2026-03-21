<?php

namespace App\Listeners;

use App\Models\AiUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\EmbeddingsGenerated;

class LogAiUsageListener
{
    public function handle(AgentPrompted|EmbeddingsGenerated $event): void
    {
        $userId = Auth::id();

        if (! $userId) {
            return;
        }

        if ($event instanceof AgentPrompted) {
            $model = $event->response->meta->model ?? 'unknown';
            $inputTokens = $event->response->usage->promptTokens;
            $outputTokens = $event->response->usage->completionTokens;
            $agent = class_basename($event->prompt->agent);
        } else {
            $model = $event->model;
            $inputTokens = $event->response->tokens;
            $outputTokens = 0;
            $agent = 'Embeddings';
        }

        $costs = config('ai.costs', []);
        $modelCosts = $costs[$model] ?? ['input' => 0, 'output' => 0];
        $estimatedCost = ($inputTokens / 1000) * $modelCosts['input']
            + ($outputTokens / 1000) * $modelCosts['output'];

        AiUsage::create([
            'user_id' => $userId,
            'agent' => $agent,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'estimated_cost' => $estimatedCost,
        ]);

        // Check daily budget
        $dailyBudget = config('ai.rate_limit.daily_budget', 5.00);
        $todaySpend = AiUsage::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->sum('estimated_cost');

        if ($todaySpend >= $dailyBudget) {
            Cache::put("ai_budget_exceeded:{$userId}", true, now()->endOfDay());
        }
    }
}
