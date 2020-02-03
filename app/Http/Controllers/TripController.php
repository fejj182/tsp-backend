<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function create(Request $request)
    {
        $tripInput = $request->input('trip');
        $trip = Trip::create();
        foreach($tripInput as $index => $station) {
            TripStop::create(['trip_id' => $trip->id, 'station_id' => $station["id"], 'position' => $index]);
        }
        return ["alias" => $trip->alias];
    }
}
