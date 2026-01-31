<?php

use App\Models\LlmRegenerationBatch;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $staleBatches = LlmRegenerationBatch::query()
        ->whereIn('status', ['pending', 'processing'])
        ->where('started_at', '<=', now()->subMinutes(30))
        ->get();

    foreach ($staleBatches as $batch) {
        if ($batch->batch_id) {
            $laravelBatch = Bus::findBatch($batch->batch_id);

            if ($laravelBatch && ! $laravelBatch->cancelled()) {
                $laravelBatch->cancel();
            }
        }

        $batch->markAsTimedOut();

        Log::error('LLM regeneration batch timed out', [
            'batch_id' => $batch->id,
            'project_id' => $batch->project_id,
            'scope' => $batch->scope,
            'total_jobs' => $batch->total_jobs,
            'completed_jobs' => $batch->completed_jobs,
            'started_at' => $batch->started_at?->toIso8601String(),
        ]);

        report(new RuntimeException("LLM regeneration batch #{$batch->id} timed out after 30 minutes"));
    }
})->name('llm-regeneration:timeout-stale-batches')->everyMinute();
