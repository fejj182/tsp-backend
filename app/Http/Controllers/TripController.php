<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TripController extends Controller
{
    public function create(Request $request): array
    {
        $tripInput = $request->input('trip');
        $trip = Trip::create();
        foreach($tripInput as $index => $station) {
            TripStop::create(['trip_id' => $trip->id, 'station_id' => $station["id"], 'position' => $index]);
        }
        return ["alias" => $trip->alias];
    }

    public function get(String $alias): array
    {
        $trip = Trip::where('alias', $alias)->first();
        $tripStops = $trip->tripStops()->pluck('station_id');
        $stations = [];
        foreach($tripStops as $stationId) {
            $stations[] = Station::where('id', $stationId)->first();
        }
        return $stations;
    }
}
