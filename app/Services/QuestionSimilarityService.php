<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;

class QuestionSimilarityService
{
    /**
     * @return Collection<int, Question>
     */
    public function findSimilar(string $query, int $limit = 5, ?int $excludeExamId = null): Collection
    {
        return Question::query()
            ->whereNotNull('embedding')
            ->when($excludeExamId, fn ($q) => $q->where('exam_id', '!=', $excludeExamId))
            ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.3)
            ->limit($limit)
            ->get();
    }
}
