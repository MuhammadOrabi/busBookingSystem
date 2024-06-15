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
        $stationCounts = [4, 6, 8];
        foreach ($buses as $key => $bus) {
            $trip = Trip::create([
                'bus_id' => $bus->id,
            ]);

            $stations = \App\Models\Station::get()->random($stationCounts[array_rand($stationCounts)]);

            foreach ($stations as $index => $station) {
                $trip->stations()->attach($station->id, ['order' => $index + 1]);
            }
        }
    }
}
