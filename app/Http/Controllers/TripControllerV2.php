<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Trip;
use App\Models\TripDestination;
use Illuminate\Http\Request;

class TripControllerV2 extends Controller
{
    public function create(Request $request): array
    {
        $tripInput = $request->input('trip');

        foreach($tripInput as $index => $destination) {
            Destination::where('id', $destination["id"])->firstOrFail();
        }

        $trip = Trip::create();
        foreach($tripInput as $index => $destination) {
            TripDestination::create(['trip_id' => $trip->id, 'destination_id' => $destination["id"], 'position' => $index]);
        }
        return ["alias" => $trip->alias];
    }

    public function get(String $alias): array
    {
        $trip = Trip::where('alias', $alias)->first();
        
        if ($trip == null) {
            abort(404);
        }

        $destinationIds = $trip->tripDestinations()->pluck('destination_id');
        $response = [];

        foreach($destinationIds as $id) {
            $nextDestination = Destination::where('id', $id)->first();
            $response[] = $nextDestination;
        }

        return $response;
    }

    public function update(Request $request, String $alias)
    {
        $tripInput = $request->input('trip');
        $trip = Trip::where('alias', $alias)->first();
        TripDestination::where('trip_id', $trip->id)->delete();
        foreach($tripInput as $index => $destination) {
            TripDestination::create(['trip_id' => $trip->id, 'destination_id' => $destination["id"], 'position' => $index]);
        }
        return 'success';
    }
}
