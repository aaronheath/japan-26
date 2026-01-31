<?php

namespace App\Http\Controllers\Api;

use App\Enums\RegenerationColumnType;
use App\Enums\RegenerationItemType;
use App\Http\Controllers\Controller;
use App\Models\Day;
use App\Models\DayActivity;
use App\Models\DayTravel;
use App\Models\Project;
use App\Services\LLM\RegenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegenerationController extends Controller
{
    public function __construct(
        protected RegenerationService $regenerationService
    ) {}

    public function single(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::enum(RegenerationItemType::class)],
            'id' => ['required', 'integer'],
        ]);

        $type = RegenerationItemType::from($request->input('type'));
        $id = $request->input('id');

        if ($type === RegenerationItemType::Travel) {
            $travel = DayTravel::findOrFail($id);
            $batch = $this->regenerationService->regenerateSingleTravel($project, $travel);
        } else {
            $activity = DayActivity::findOrFail($id);
            $batch = $this->regenerationService->regenerateSingleActivity($project, $activity);
        }

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_jobs' => $batch->total_jobs,
        ]);
    }

    public function day(Project $project, Day $day): JsonResponse
    {
        $batch = $this->regenerationService->regenerateDay($project, $day);

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_jobs' => $batch->total_jobs,
        ]);
    }

    public function column(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::enum(RegenerationColumnType::class)],
        ]);

        $type = RegenerationColumnType::from($request->input('type'));
        $batch = $this->regenerationService->regenerateColumn($project, $type);

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_jobs' => $batch->total_jobs,
        ]);
    }

    public function project(Project $project): JsonResponse
    {
        $batch = $this->regenerationService->regenerateProject($project);

        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_jobs' => $batch->total_jobs,
        ]);
    }
}
