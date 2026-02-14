<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SetProjectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
        ]);

        $request->session()->put('selected_project_id', (int) $request->input('project_id'));

        return back();
    }
}
