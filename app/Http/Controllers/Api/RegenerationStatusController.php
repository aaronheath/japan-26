<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LlmRegenerationBatch;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class RegenerationStatusController extends Controller
{
    public function status(Project $project): JsonResponse
    {
        $activeBatches = LlmRegenerationBatch::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['pending', 'processing'])
            ->get()
            ->map(function (LlmRegenerationBatch $batch) {
                $laravelBatch = $batch->batch_id ? Bus::findBatch($batch->batch_id) : null;

                return [
                    'id' => $batch->id,
                    'scope' => $batch->scope,
                    'generator_type' => $batch->generator_type,
                    'progress' => $laravelBatch ? $laravelBatch->progress() : $batch->progressPercentage(),
                    'total_jobs' => $batch->total_jobs,
                    'completed_jobs' => $laravelBatch ? $laravelBatch->processedJobs() : $batch->completed_jobs,
                    'failed_jobs' => $laravelBatch ? $laravelBatch->failedJobs : $batch->failed_jobs,
                    'status' => $batch->status,
                ];
            });

        $recentlyCompleted = LlmRegenerationBatch::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['completed', 'failed'])
            ->where('completed_at', '>=', now()->subSeconds(20))
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn (LlmRegenerationBatch $batch) => [
                'id' => $batch->id,
                'scope' => $batch->scope,
                'generator_type' => $batch->generator_type,
                'status' => $batch->status,
                'completed_at' => $batch->completed_at?->toIso8601String(),
            ]);

        return response()->json([
            'is_regenerating' => $activeBatches->isNotEmpty(),
            'horizon_running' => $this->isHorizonRunning(),
            'active_batches' => $activeBatches,
            'recently_completed' => $recentlyCompleted,
        ]);
    }

    protected function isHorizonRunning(): bool
    {
        try {
            $supervisors = app(MasterSupervisorRepository::class)->all();

            return ! empty($supervisors);
        } catch (\Exception) {
            return false;
        }
    }
}
