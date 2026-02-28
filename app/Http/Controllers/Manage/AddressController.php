<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreAddressRequest;
use App\Http\Requests\Manage\UpdateAddressRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('manage/addresses', [
            'addresses' => Address::query()
                ->with(['country', 'state', 'city', 'addressable'])
                ->orderBy('line_1')
                ->get()
                ->map(fn (Address $address) => [
                    'id' => $address->id,
                    'line_1' => $address->line_1,
                    'line_2' => $address->line_2,
                    'line_3' => $address->line_3,
                    'postcode' => $address->postcode,
                    'country_id' => $address->country_id,
                    'state_id' => $address->state_id,
                    'city_id' => $address->city_id,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'country_name' => $address->country->name,
                    'state_name' => $address->state?->name,
                    'city_name' => $address->city->name,
                    'attached_to' => $address->addressable ? class_basename($address->addressable).': '.($address->addressable->name ?? $address->addressable->getKey()) : null,
                    'attached_to_url' => $address->addressable instanceof \App\Models\Venue ? route('manage.venues.index') : null,
                ]),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'states' => State::query()->orderBy('name')->get(['id', 'name', 'country_id']),
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        Address::create($request->validated());

        return back();
    }

    public function update(UpdateAddressRequest $request, Address $address): RedirectResponse
    {
        $address->update($request->validated());

        return back();
    }

    public function destroy(Address $address): RedirectResponse
    {
        if ($address->addressable) {
            return back()->withErrors([
                'address' => 'Cannot delete an address that is attached to an entity.',
            ]);
        }

        $address->delete();

        return back();
    }
}
