<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreStateRequest;
use App\Http\Requests\Manage\UpdateStateRequest;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('manage/states', [
            'states' => State::query()
                ->with('country')
                ->withCount('cities')
                ->orderBy('name')
                ->get()
                ->map(fn (State $state) => [
                    'id' => $state->id,
                    'name' => $state->name,
                    'country_id' => $state->country_id,
                    'country_name' => $state->country->name,
                    'cities_count' => $state->cities_count,
                ]),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreStateRequest $request): RedirectResponse
    {
        State::create($request->validated());

        return back();
    }

    public function update(UpdateStateRequest $request, State $state): RedirectResponse
    {
        $state->update($request->validated());

        return back();
    }

    public function destroy(State $state): RedirectResponse
    {
        if ($state->cities()->exists()) {
            return back()->withErrors([
                'state' => 'Cannot delete a state that has cities.',
            ]);
        }

        $state->delete();

        return back();
    }
}
