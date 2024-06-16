<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function list()
    {
        $stations = Station::all();
        return response()->json($stations);
    }
}
