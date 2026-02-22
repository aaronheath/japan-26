<?php

use Illuminate\Support\Facades\Artisan;

it('generates wayfinder actions successfully', function () {
    Artisan::call('wayfinder:generate');

    expect(Artisan::output())->toContain('Generated actions');
});

it('generates action files for all manage controllers', function (string $controller, array $methods) {
    $path = resource_path("js/actions/App/Http/Controllers/Manage/{$controller}.ts");

    expect($path)->toBeFile();

    $content = file_get_contents($path);

    expect($content)->toContain("export default {$controller}");

    foreach ($methods as $method) {
        expect($content)->toContain("export const {$method}");
    }
})->with([
    'CountryController' => ['CountryController', ['index', 'store', 'update', 'destroy']],
    'StateController' => ['StateController', ['index', 'store', 'update', 'destroy']],
    'CityController' => ['CityController', ['index', 'store', 'update', 'destroy']],
    'VenueController' => ['VenueController', ['index', 'store', 'update', 'destroy']],
    'AddressController' => ['AddressController', ['index', 'store', 'update', 'destroy']],
    'ProjectManagementController' => ['ProjectManagementController', ['index', 'store', 'update', 'destroy']],
    'SetProjectController' => ['SetProjectController', []],
    'DayTravelManagementController' => ['DayTravelManagementController', ['index', 'store', 'update', 'destroy']],
    'DayAccommodationManagementController' => ['DayAccommodationManagementController', ['index', 'store', 'update', 'destroy']],
    'DayActivityManagementController' => ['DayActivityManagementController', ['index', 'store', 'update', 'destroy']],
]);
