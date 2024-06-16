<?php

namespace Database\Seeders;

use App\Models\Trip;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('station_trip')->truncate();
        Trip::truncate();

        $buses = \App\Models\Bus::all();
        $stationTrips = [
            [1, 2, 7, 11, 16],
            [1, 2, 16, 24, 15],
            [1, 6, 3, 23],
        ];
        foreach ($buses as $key => $bus) {
            $trip = Trip::create([
                'bus_id' => $bus->id,
            ]);

            foreach ($stationTrips[$key] as $index => $station) {
                $trip->stations()->attach($station, ['order' => $index + 1]);
            }
        }
    }
}
