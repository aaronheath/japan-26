<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreCityRequest;
use App\Http\Requests\Manage\UpdateCityRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('manage/cities', [
            'cities' => City::query()
                ->with(['country', 'state'])
                ->withCount('venues')
                ->orderBy('name')
                ->get()
                ->map(fn (City $city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                    'country_id' => $city->country_id,
                    'state_id' => $city->state_id,
                    'country_name' => $city->country->name,
                    'state_name' => $city->state?->name,
                    'population' => $city->population,
                    'timezone' => $city->timezone,
                    'venues_count' => $city->venues_count,
                ]),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'states' => State::query()->orderBy('name')->get(['id', 'name', 'country_id']),
        ]);
    }

    public function store(StoreCityRequest $request): RedirectResponse
    {
        City::create($request->validated());

        return back();
    }

    public function update(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $city->update($request->validated());

        return back();
    }

    public function destroy(City $city): RedirectResponse
    {
        if ($city->venues()->exists()) {
            return back()->withErrors([
                'city' => 'Cannot delete a city that has venues.',
            ]);
        }

        $city->delete();

        return back();
    }
}
