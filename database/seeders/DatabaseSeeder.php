<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    const AUSTRALIA_CITY_STATES = [
        ['name' => 'Sydney', 'state' => 'New South Wales', 'timezone' => 'Australia/Sydney'],
        ['name' => 'Melbourne', 'state' => 'Victoria', 'timezone' => 'Australia/Sydney'],
        ['name' => 'Brisbane', 'state' => 'Queensland', 'timezone' => 'Australia/Brisbane'],
        ['name' => 'Perth', 'state' => 'Western Australia', 'timezone' => 'Australia/Perth'],
        ['name' => 'Adelaide', 'state' => 'South Australia', 'timezone' => 'Australia/Adelaide'],
        ['name' => 'Hobart', 'state' => 'Tasmania', 'timezone' => 'Australia/Sydney'],
        ['name' => 'Canberra', 'state' => 'Australian Capital Territory', 'timezone' => 'Australia/Sydney'],
        ['name' => 'Darwin', 'state' => 'Northern Territory', 'timezone' => 'Australia/Darwin'],
    ];

    const JAPAN_CITY_STATES = [
        ['name' => 'Sapporo', 'state' => 'Hokkaido'],
        ['name' => 'Sendai', 'state' => 'Miyagi'],
        ['name' => 'Nagaoka', 'state' => 'Niigata'],
        ['name' => 'Ota', 'state' => 'Tokyo'],
        ['name' => 'Nagoya', 'state' => 'Aichi'],
        ['name' => 'Suita', 'state' => 'Osaka'],
        ['name' => 'Takamatsu', 'state' => 'Kagawa'],
        ['name' => 'Nishi-ku', 'state' => 'Hiroshima'],
        ['name' => 'Hakata-ku', 'state' => 'Fukuoka'],
        ['name' => 'Suminoe-ku', 'state' => 'Osaka'],
        ['name' => 'Bunkyo', 'state' => 'Tokyo'],
        ['name' => 'Naka-ku', 'state' => 'Yokohama'],
        ['name' => 'Takasaki', 'state' => 'Gunma'],
        ['name' => 'Hamamatsu', 'state' => 'Shizuoka'],
        ['name' => 'Koto', 'state' => 'Tokyo'],
        //        ['name' => '', 'state' => ''],
    ];

    const JAPAN_WRESTLING_VENUES = [
        ['name' => 'Hokkai Kitayell', 'city' => 'Sapporo'],
        ['name' => 'Sendai Sun Plaza Hall', 'city' => 'Sendai'],
        ['name' => 'City Hall Plaza Aore Nagaoka', 'city' => 'Nagaoka'],
        ['name' => 'Ota City General Gymnasium', 'city' => 'Ota'],
        ['name' => 'Port Messe Nagoya', 'city' => 'Nagoya'],
        ['name' => 'Yamato Arena', 'city' => 'Suita'],
        ['name' => 'Sun Messe Kagawa', 'city' => 'Takamatsu'],
        ['name' => 'Hiroshima Sun Plaza', 'city' => 'Nishi-ku'],
        ['name' => 'Fukuoka Convention Center', 'city' => 'Hakata-ku'],
        ['name' => 'Intex Osaka', 'city' => 'Suminoe-ku'],
        ['name' => 'Korakuen Hall', 'city' => 'Bunkyo'],
        ['name' => 'Yokohama Budokan', 'city' => 'Naka-ku'],
        ['name' => 'G Messe Gunma', 'city' => 'Takasaki'],
        ['name' => 'Act City Hamamatsu', 'city' => 'Hamamatsu'],
        ['name' => 'Ariake Arena', 'city' => 'Koto'],
    ];

    public function run(): void
    {
        $this->users();
        $this->countries();
        $this->statesAndCities();
        $this->wrestlingVenues();
        $this->japan26Project();
    }

    protected function users()
    {
        User::updateOrCreate(
            [
                'email' => 'aaron@aaronheath.com',
            ],
            [
                'name' => 'Aaron Heath',
                'password' => bcrypt('password'),
            ],
        );
    }

    protected function countries()
    {
        Country::updateOrCreate(
            ['name' => 'Australia'],
        );

        Country::updateOrCreate(
            ['name' => 'Japan'],
        );
    }

    protected function statesAndCities()
    {
        $australia = Country::where('name', 'Australia')->first();

        collect(self::AUSTRALIA_CITY_STATES)->each(function ($city) use (&$australia) {
            $state = $australia->states()->updateOrCreate([
                'name' => $city['state'],
            ]);

            $australia->cities()->updateOrCreate(
                [
                    'name' => $city['name'],
                    'state_id' => $state->id,
                ],
                [
                    'timezone' => $city['timezone'],
                ],
            );
        });

        $japan = Country::where('name', 'Japan')->first();

        collect(self::JAPAN_CITY_STATES)->each(function ($city) use (&$japan) {
            $state = $japan->states()->updateOrCreate([
                'name' => $city['state'],
            ]);

            $japan->cities()->updateOrCreate(
                [
                    'name' => $city['name'],
                    'state_id' => $state->id,
                ],
                [
                    'timezone' => 'Asia/Tokyo',
                ],
            );
        });
    }

    protected function wrestlingVenues()
    {
        $japan = Country::where('name', 'Japan')->first();

        collect(self::JAPAN_WRESTLING_VENUES)->each(function ($venue) use (&$japan) {
            $city = $japan->cities()->where('name', $venue['city'])->first();

            $city->venues()->updateOrCreate([
                'type' => 'wrestling',
                'name' => $venue['name'],
            ]);
        });
    }

    protected function japan26Project()
    {
        $this->call([Japan26Seeder::class]);
    }
}
