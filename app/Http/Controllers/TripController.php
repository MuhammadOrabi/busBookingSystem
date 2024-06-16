<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function list(Request $request)
    {
        $trips = Trip::with('bus', 'stations')->get();
        return response()->json($trips);
    }
}
