<?php

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Station::truncate();

        $stations = collect(json_decode(
            File::get(
                base_path("database/seeders/data/stations.json")
            )
        ));

        DB::transaction(function () use ($stations) {
            $data = $stations->map(function ($station) {
                return [
                    'id' => intval($station->id),
                    'name' => $station->name,
                ];
            });
            Station::insert($data->toArray());
        });
    }
}
