<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Station;
use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function create(Request $request): array
    {
        $tripInput = $request->input('trip');

        foreach($tripInput as $index => $station) {
            Station::where('id', $station["id"])->firstOrFail();
        }

        $trip = Trip::create();
        foreach($tripInput as $index => $station) {
            TripStop::create(['trip_id' => $trip->id, 'station_id' => $station["id"], 'position' => $index]);
        }
        return ["alias" => $trip->alias];
    }

    public function get(String $alias): array
    {
        $trip = Trip::where('alias', $alias)->first();
        
        if ($trip == null) {
            abort(404);
        }

        $stationIds = $trip->tripStops()->pluck('station_id');
        $response = [];

        foreach($stationIds as $id) {
            $nextStation = Station::where('id', $id)->first();

            if (sizeof($response) > 0) {
                $lastStation = $response[sizeof($response) - 1];
                $nextStation->duration = Connection::query()->where([
                    ['starting_station', '=', $lastStation->station_id],
                    ['ending_station', '=', $nextStation->station_id]
                ])->first()->duration;
            }

            $response[] = $nextStation;
        }

        return $response;
    }

    public function update(Request $request, String $alias)
    {
        $tripInput = $request->input('trip');
        $trip = Trip::where('alias', $alias)->first();
        TripStop::where('trip_id', $trip->id)->delete();
        foreach($tripInput as $index => $station) {
            TripStop::create(['trip_id' => $trip->id, 'station_id' => $station["id"], 'position' => $index]);
        }
        return 'success';
    }
}
