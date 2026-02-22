<?php

namespace App\Http\Controllers\Manage;

use App\Enums\PromptType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\RevertPromptRequest;
use App\Http\Requests\Manage\StorePromptRequest;
use App\Http\Requests\Manage\UpdatePromptRequest;
use App\Models\Prompt;
use App\Models\PromptVersion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PromptController extends Controller
{
    public function index(): Response
    {
        $prompts = Prompt::query()
            ->with(['activeVersion', 'systemPrompt', 'versions' => fn ($q) => $q->orderByDesc('version')])
            ->withCount('versions')
            ->orderByRaw('CASE WHEN type = ? THEN 0 ELSE 1 END ASC', [PromptType::System->value])
            ->orderBy('name')
            ->get();

        $systemPrompts = Prompt::query()
            ->where('type', PromptType::System)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('manage/prompts', [
            'prompts' => $prompts,
            'systemPrompts' => $systemPrompts,
        ]);
    }

    public function store(StorePromptRequest $request): RedirectResponse
    {
        $prompt = Prompt::create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'type' => $request->validated('type'),
            'system_prompt_id' => $request->validated('system_prompt_id'),
        ]);

        $version = PromptVersion::create([
            'prompt_id' => $prompt->id,
            'version' => 1,
            'content' => $request->validated('content'),
        ]);

        $prompt->update(['active_version_id' => $version->id]);

        return back();
    }

    public function update(UpdatePromptRequest $request, Prompt $prompt): RedirectResponse
    {
        $latestVersion = $prompt->versions()->max('version') ?? 0;

        $version = PromptVersion::create([
            'prompt_id' => $prompt->id,
            'version' => $latestVersion + 1,
            'content' => $request->validated('content'),
            'change_notes' => $request->validated('change_notes'),
        ]);

        $prompt->update(['active_version_id' => $version->id]);

        return back();
    }

    public function revert(RevertPromptRequest $request, Prompt $prompt): RedirectResponse
    {
        $prompt->update(['active_version_id' => $request->validated('version_id')]);

        return back();
    }
}
