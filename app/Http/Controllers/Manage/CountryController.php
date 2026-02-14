<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreCountryRequest;
use App\Http\Requests\Manage\UpdateCountryRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CountryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('manage/countries', [
            'countries' => Country::query()
                ->withCount(['states', 'cities'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreCountryRequest $request): RedirectResponse
    {
        Country::create($request->validated());

        return back();
    }

    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        return back();
    }

    public function destroy(Country $country): RedirectResponse
    {
        if ($country->states()->exists() || $country->cities()->exists()) {
            return back()->withErrors([
                'country' => 'Cannot delete a country that has states or cities.',
            ]);
        }

        $country->delete();

        return back();
    }
}
