<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\City;
use Illuminate\Database\Seeder;

class StateCitySeeder extends Seeder
{
    public function run(): void
    {
        // Major Indian States and Cities for MVP
        $statesData = [
            [
                'name' => 'Maharashtra',
                'code' => 'MH',
                'cities' => ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Thane']
            ],
            [
                'name' => 'Delhi',
                'code' => 'DL',
                'cities' => ['New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi']
            ],
            [
                'name' => 'Karnataka',
                'code' => 'KA',
                'cities' => ['Bangalore', 'Mysore', 'Mangalore', 'Hubli', 'Belgaum']
            ],
            [
                'name' => 'Tamil Nadu',
                'code' => 'TN',
                'cities' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem']
            ],
            [
                'name' => 'Gujarat',
                'code' => 'GJ',
                'cities' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar']
            ],
            [
                'name' => 'West Bengal',
                'code' => 'WB',
                'cities' => ['Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri']
            ],
            [
                'name' => 'Telangana',
                'code' => 'TS',
                'cities' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Khammam', 'Karimnagar']
            ],
            [
                'name' => 'Uttar Pradesh',
                'code' => 'UP',
                'cities' => ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Varanasi']
            ],
        ];

        foreach ($statesData as $stateData) {
            $state = State::create([
                'name' => $stateData['name'],
                'code' => $stateData['code'],
                'status' => true
            ]);

            foreach ($stateData['cities'] as $cityName) {
                City::create([
                    'state_id' => $state->id,
                    'name' => $cityName,
                    'status' => true
                ]);
            }
        }
    }
}
