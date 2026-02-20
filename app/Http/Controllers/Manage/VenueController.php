<?php

namespace App\Http\Controllers\Manage;

use App\Enums\VenueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreVenueRequest;
use App\Http\Requests\Manage\UpdateVenueRequest;
use App\Models\City;
use App\Models\DayAccommodation;
use App\Models\DayActivity;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('manage/venues', [
            'venues' => Venue::query()
                ->with('city')
                ->orderBy('name')
                ->get()
                ->map(fn (Venue $venue) => [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'type' => $venue->type->value,
                    'description' => $venue->description,
                    'city_id' => $venue->city_id,
                    'city_name' => $venue->city->name,
                ]),
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'venueTypes' => collect(VenueType::cases())->map(fn (VenueType $type) => [
                'value' => $type->value,
                'label' => $type->name,
            ]),
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        Venue::create($request->validated());

        return back();
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $venue->update($request->validated());

        return back();
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if (DayActivity::where('venue_id', $venue->id)->exists() || DayAccommodation::where('venue_id', $venue->id)->exists()) {
            return back()->withErrors([
                'venue' => 'Cannot delete a venue that is used in activities or accommodations.',
            ]);
        }

        $venue->delete();

        return back();
    }
}
