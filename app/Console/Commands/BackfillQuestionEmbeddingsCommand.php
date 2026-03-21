<?php

namespace App\Console\Commands;

use App\Jobs\GenerateQuestionEmbeddingJob;
use App\Models\Question;
use Illuminate\Console\Command;

class BackfillQuestionEmbeddingsCommand extends Command
{
    protected $signature = 'questions:backfill-embeddings';

    protected $description = 'Dispatch embedding generation jobs for questions without embeddings';

    public function handle(): int
    {
        $count = 0;

        Question::query()
            ->whereNull('embedding')
            ->chunkById(50, function ($questions) use (&$count) {
                foreach ($questions as $question) {
                    GenerateQuestionEmbeddingJob::dispatch($question);
                    $count++;
                }
            });

        $this->info("Dispatched embedding jobs for {$count} questions.");

        return self::SUCCESS;
    }
}
