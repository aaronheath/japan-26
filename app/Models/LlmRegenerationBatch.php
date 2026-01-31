<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string|null $batch_id
 * @property string $scope
 * @property string|null $generator_type
 * @property int $total_jobs
 * @property int $completed_jobs
 * @property int $failed_jobs
 * @property string $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Project $project
 */
class LlmRegenerationBatch extends Model
{
    /** @use HasFactory<\Database\Factories\LlmRegenerationBatchFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function progressPercentage(): int
    {
        if ($this->total_jobs === 0) {
            return 0;
        }

        return (int) round(($this->completed_jobs / $this->total_jobs) * 100);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    public function incrementCompleted(): void
    {
        $this->increment('completed_jobs');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_jobs');
    }
}
