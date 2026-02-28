<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Http;

class AddressLookupService
{
    /**
     * @return array<int, array{place_id: string, description: string}>
     */
    public function autocomplete(string $query): array
    {
        $response = Http::withHeaders([
            'X-Goog-Api-Key' => config('services.google_places.api_key'),
        ])->post('https://places.googleapis.com/v1/places:autocomplete', [
            'input' => $query,
            'includedPrimaryTypes' => ['street_address', 'subpremise', 'premise', 'point_of_interest', 'establishment'],
        ]);

        $data = $response->json();

        return collect($data['suggestions'] ?? [])
            ->filter(fn (array $suggestion) => isset($suggestion['placePrediction']))
            ->map(fn (array $suggestion) => [
                'place_id' => $suggestion['placePrediction']['placeId'],
                'description' => $suggestion['placePrediction']['text']['text'] ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     line_1: string,
     *     line_2: string|null,
     *     line_3: string|null,
     *     postcode: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     country_id: int|null,
     *     state_id: int|null,
     *     city_id: int|null,
     *     country: array{id: int, name: string}|null,
     *     state: array{id: int, name: string, country_id: int}|null,
     *     city: array{id: int, name: string}|null,
     * }
     */
    public function placeDetails(string $placeId): array
    {
        $response = Http::withHeaders([
            'X-Goog-Api-Key' => config('services.google_places.api_key'),
            'X-Goog-FieldMask' => 'addressComponents,formattedAddress,location',
        ])->get("https://places.googleapis.com/v1/places/{$placeId}");

        $data = $response->json();
        $components = $this->parseAddressComponents($data['addressComponents'] ?? []);
        $location = $data['location'] ?? [];

        $latitude = $location['latitude'] ?? null;
        $longitude = $location['longitude'] ?? null;

        $geoRecords = $this->findOrCreateGeoRecords($components, $latitude, $longitude);

        $formattedAddress = $data['formattedAddress'] ?? '';

        return [
            'line_1' => $this->buildStreetAddress($components, $formattedAddress),
            'line_2' => $components['subpremise'] ?? null,
            'line_3' => null,
            'postcode' => $components['postal_code'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country_id' => $geoRecords['country']?->id,
            'state_id' => $geoRecords['state']?->id,
            'city_id' => $geoRecords['city']?->id,
            'country' => $geoRecords['country'] ? [
                'id' => $geoRecords['country']->id,
                'name' => $geoRecords['country']->name,
            ] : null,
            'state' => $geoRecords['state'] ? [
                'id' => $geoRecords['state']->id,
                'name' => $geoRecords['state']->name,
                'country_id' => $geoRecords['state']->country_id,
            ] : null,
            'city' => $geoRecords['city'] ? [
                'id' => $geoRecords['city']->id,
                'name' => $geoRecords['city']->name,
            ] : null,
        ];
    }

    /**
     * @param  array<int, array{longText: string, shortText: string, types: array<string>}>  $components
     * @return array<string, string>
     */
    private function parseAddressComponents(array $components): array
    {
        $parsed = [];

        foreach ($components as $component) {
            foreach ($component['types'] as $type) {
                $parsed[$type] = $component['longText'];
                $parsed[$type.'_short'] = $component['shortText'];
            }
        }

        return $parsed;
    }

    /**
     * @param  array<string, string>  $components
     */
    private function buildStreetAddress(array $components, string $formattedAddress = ''): string
    {
        $parts = [];

        if (isset($components['street_number'])) {
            $parts[] = $components['street_number'];
        }

        if (isset($components['route'])) {
            $parts[] = $components['route'];
        }

        if ($parts) {
            return implode(' ', $parts);
        }

        if (isset($components['premise'])) {
            return $components['premise'];
        }

        // Japanese-style addresses use sublocality components
        $subParts = [];

        if (isset($components['sublocality_level_4'])) {
            $subParts[] = $components['sublocality_level_4'];
        }

        if (isset($components['sublocality_level_3'])) {
            $subParts[] = $components['sublocality_level_3'];
        }

        if (isset($components['sublocality_level_2'])) {
            $subParts[] = $components['sublocality_level_2'];
        }

        if ($subParts) {
            return implode(' ', $subParts);
        }

        // Fall back to the portion of formatted address before the city/state/country
        if ($formattedAddress) {
            $cityName = $components['locality'] ?? $components['administrative_area_level_2'] ?? '';

            if ($cityName && str_contains($formattedAddress, $cityName)) {
                $streetPart = trim(explode($cityName, $formattedAddress)[0], ', ');

                if ($streetPart) {
                    return $streetPart;
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string, string>  $components
     * @return array{country: Country|null, state: State|null, city: City|null}
     */
    private function findOrCreateGeoRecords(array $components, ?float $latitude, ?float $longitude): array
    {
        $country = null;
        $state = null;
        $city = null;

        if (isset($components['country'])) {
            $country = Country::query()->firstOrCreate(
                ['name' => $components['country']],
            );
        }

        if ($country && isset($components['administrative_area_level_1'])) {
            $state = State::query()->firstOrCreate(
                [
                    'name' => $components['administrative_area_level_1'],
                    'country_id' => $country->id,
                ],
            );
        }

        $cityName = $components['locality']
            ?? $components['postal_town']
            ?? $components['administrative_area_level_2']
            ?? null;

        if ($country && $cityName) {
            $city = City::query()
                ->where('name', $cityName)
                ->where('country_id', $country->id)
                ->first();

            if (! $city) {
                $timezone = $this->lookupTimezone($latitude, $longitude);

                $city = City::query()->create([
                    'name' => $cityName,
                    'country_id' => $country->id,
                    'state_id' => $state?->id,
                    'timezone' => $timezone ?? 'UTC',
                    'population' => 0,
                ]);
            }
        }

        return [
            'country' => $country,
            'state' => $state,
            'city' => $city,
        ];
    }

    private function lookupTimezone(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/timezone/json', [
            'location' => "{$latitude},{$longitude}",
            'timestamp' => time(),
            'key' => config('services.google_places.api_key'),
        ]);

        $data = $response->json();

        if (($data['status'] ?? '') !== 'OK') {
            return null;
        }

        return $data['timeZoneId'] ?? null;
    }
}
