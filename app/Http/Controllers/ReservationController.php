<?php

namespace App\Http\Controllers;

use App\Models\StationTrip;
use App\Models\Trip;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function getAvailableSeats(Trip $trip, int $from_station_id): Array {
        $fromStation = StationTrip::where('trip_id', $trip->id)
            ->where('station_id', $from_station_id)
            ->first();
        $tripPrevStations = StationTrip::where('trip_id', $trip->id)
            ->where('order', '<=', $fromStation->order)
            ->get();

        $totalDeparted = [];
        $totalArrived = [];
        foreach ($tripPrevStations as $station) {
            $totalArrived = array_merge($totalArrived, $station->arrived_seats);
            $totalDeparted = array_merge($totalDeparted, $station->departed_seats);
        }

        $reservedSeats = $trip->reservations()->where('is_cancelled', false)->get()->pluck('seat_number')->toArray();
        foreach ($totalDeparted as $seat) {
            $index = array_search($seat, $reservedSeats);
            if ($index !== false) {
                unset($reservedSeats[$index]);
            }
        }

        $availableSeats = array_diff(range(1, $trip->bus->seats), $reservedSeats);

        if (count($totalDeparted) > count($totalArrived)) {
            foreach ($totalDeparted as $seat) {
                $index = array_search($seat, $totalArrived);
                if ($index !== false) {
                    unset($totalArrived[$index]);
                }
            }
            $availableSeats = array_diff($availableSeats, $totalArrived);

            $tripNextStations = StationTrip::where('trip_id', $trip->id)
                ->where('order', '>', $fromStation->order)
                ->get();

            $totalArrived = [];
            foreach ($tripNextStations as $station) {
                $totalArrived = array_merge($totalArrived, $station->arrived_seats);
            }

            $availableSeats = array_diff($availableSeats, $totalArrived);
        }

        return array_values($availableSeats);
    }

    public function availableTrips(Request $request)
    {
        $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id|different:from_station_id',
        ]);

        $trips = Trip::whereHas('stations', function ($query) use ($request) {
            $query->where('station_id', $request->from_station_id);
        })->whereHas('stations', function ($query) use ($request) {
            $query->where('station_id', $request->to_station_id);
        })->with('stations')->get();

        if ($trips->isEmpty()) {
            return response()->json([
                'message' => 'No trips available'
            ], 400);
        }

        // check if trip stations are in the correct order
        $availableTrips = $trips->filter(function ($trip) use ($request) {
            $from_station = $trip->stations->search(function ($station) use ($request) {
                return $station->id == $request->from_station_id;
            });

            $to_station = $trip->stations->search(function ($station) use ($request) {
                return $station->id == $request->to_station_id;
            });

            return $trip->stations[$from_station]->pivot->order < $trip->stations[$to_station]->pivot->order;
        });

        if ($availableTrips->isEmpty()) {
            return response()->json([
                'message' => 'No trips available'
            ], 400);
        }

        $data = $availableTrips->map(function ($trip) use ($request) {
            $availableSeats = $this->getAvailableSeats($trip->load('bus'), $request->from_station_id);

            return [
                'trip' => $trip,
                'available_seats' => $availableSeats,
            ];
        });

        return response()->json([
            "trips" => $data,
        ]);
    }

    public function reserve(Request $request)
    {
        $request->validate([
            'from_station_id' => 'required|exists:stations,id',
            'to_station_id' => 'required|exists:stations,id|different:from_station_id',
            'trip_id' => 'required|exists:trips,id',
            'seat_number' => 'required|integer',
        ]);

        $trip = Trip::find($request->trip_id)->load('stations');

        $from_station = $trip->stations->search(function ($station) use ($request) {
            return $station->id == $request->from_station_id;
        });
        $to_station = $trip->stations->search(function ($station) use ($request) {
            return $station->id == $request->to_station_id;
        });
        if ($from_station === false || $to_station === false) {
            return response()->json([
                'message' => 'Invalid stations, Please try again. exists'
            ], 400);
        }

        if ($trip->stations[$from_station]->pivot->order > $trip->stations[$to_station]->pivot->order) {
            return response()->json([
                'message' => 'Invalid stations, Please try again.'
            ], 400);
        }

        $availableSeats = $this->getAvailableSeats($trip, $request->from_station_id);

        if (in_array($request->seat_number, $availableSeats)) {
            $trip->reservations()->create([
                'user_id' => $request->user()->id,
                'seat_number' => $request->seat_number,
                'from_station_id' => $request->from_station_id,
                'to_station_id' => $request->to_station_id,
            ]);

            $fromStation = StationTrip::where('trip_id', $request->trip_id)
                ->where('station_id', $request->from_station_id)
                ->first();
            $fromStation->arrived_seats = array_merge($fromStation->arrived_seats, [$request->seat_number]);
            $fromStation->save();

            $toStation = StationTrip::where('trip_id', $request->trip_id)
                ->where('station_id', $request->to_station_id)
                ->first();
            $toStation->departed_seats = array_merge($toStation->departed_seats, [$request->seat_number]);
            $toStation->save();

            return response()->json([
                'message' => 'Seat reserved successfully'
            ]);
        }

        return response()->json([
            'message' => 'Unable to reserve this seat, Please try again.'
        ], 400);
    }

    public function cancelReservation(Request $request, $id)
    {
        $reservation = $request->user()->reservations()->find($id);

        if (!$reservation) {
            return response()->json([
                'message' => 'Reservation not found'
            ], 400);
        }

        $trip = $reservation->trip;
        $fromStation = StationTrip::where('trip_id', $trip->id)
            ->where('station_id', $reservation->from_station_id)
            ->first();

        $index = array_search($reservation->seat_number, $fromStation->arrived_seats);
        if ($index !== false) {
            $seats = $fromStation->arrived_seats;
            $fromStation->arrived_seats = array_splice($seats, $index, 1);
            $fromStation->save();
        }

        $toStation = StationTrip::where('trip_id', $trip->id)
            ->where('station_id', $reservation->to_station_id)
            ->first();
        $index = array_search($reservation->seat_number, $toStation->departed_seats);
        if ($index !== false) {
            $seats = $toStation->departed_seats;
            $toStation->departed_seats = array_splice($seats, $index, 1);
            $toStation->save();
        }

        $reservation->is_cancelled = true;
        $reservation->save();

        return response()->json([
            'message' => 'Reservation canceled successfully'
        ]);
    }

    public function list(Request $request)
    {
        $reservations = $request->user()->reservations()->with('trip', 'fromStation', 'toStation')->get();

        return response()->json($reservations);
    }
}
