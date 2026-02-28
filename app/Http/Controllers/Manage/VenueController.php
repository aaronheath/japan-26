<?php

namespace App\Http\Controllers\Manage;

use App\Enums\VenueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreVenueRequest;
use App\Http\Requests\Manage\UpdateVenueRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\DayAccommodation;
use App\Models\DayActivity;
use App\Models\State;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('manage/venues', [
            'venues' => Venue::query()
                ->with(['city', 'address'])
                ->orderBy('name')
                ->get()
                ->map(fn (Venue $venue) => [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'type' => $venue->type->value,
                    'description' => $venue->description,
                    'city_id' => $venue->city_id,
                    'city_name' => $venue->city->name,
                    'address' => $venue->address ? [
                        'id' => $venue->address->id,
                        'country_id' => $venue->address->country_id,
                        'state_id' => $venue->address->state_id,
                        'city_id' => $venue->address->city_id,
                        'postcode' => $venue->address->postcode,
                        'line_1' => $venue->address->line_1,
                        'line_2' => $venue->address->line_2,
                        'line_3' => $venue->address->line_3,
                        'latitude' => $venue->address->latitude,
                        'longitude' => $venue->address->longitude,
                    ] : null,
                ]),
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'states' => State::query()->orderBy('name')->get(['id', 'name', 'country_id']),
            'venueTypes' => collect(VenueType::cases())->map(fn (VenueType $type) => [
                'value' => $type->value,
                'label' => $type->name,
            ]),
            'unattachedAddresses' => Address::query()
                ->whereNull('addressable_id')
                ->with(['city', 'country'])
                ->orderBy('line_1')
                ->get()
                ->map(fn (Address $address) => [
                    'id' => $address->id,
                    'label' => $address->line_1.', '.$address->city->name.', '.$address->country->name,
                ]),
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $venue = Venue::create($request->safe()->only(['city_id', 'type', 'name', 'description']));

            if ($request->validated('address_mode') === 'select') {
                $this->attachExistingAddress($venue, $request->validated('address_id'));
            } else {
                $this->createAddress($venue, $request->validated('address'));
            }
        });

        return back();
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        DB::transaction(function () use ($request, $venue) {
            $venue->update($request->safe()->only(['city_id', 'type', 'name', 'description']));

            $this->updateOrCreateAddress($venue, $request->validated('address'));
        });

        return back();
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if (DayActivity::where('venue_id', $venue->id)->exists() || DayAccommodation::where('venue_id', $venue->id)->exists()) {
            return back()->withErrors([
                'venue' => 'Cannot delete a venue that is used in activities or accommodations.',
            ]);
        }

        $venue->address?->update([
            'addressable_type' => null,
            'addressable_id' => null,
        ]);

        $venue->delete();

        return back();
    }

    private function attachExistingAddress(Venue $venue, int $addressId): void
    {
        Address::where('id', $addressId)->update([
            'addressable_type' => Venue::class,
            'addressable_id' => $venue->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    private function createAddress(Venue $venue, array $addressData): void
    {
        $venue->address()->create($addressData);
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    private function updateOrCreateAddress(Venue $venue, array $addressData): void
    {
        if ($venue->address) {
            $venue->address->update($addressData);
        } else {
            $venue->address()->create($addressData);
        }
    }
}
