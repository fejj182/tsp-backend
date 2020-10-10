<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Trip;
use App\Models\TripDestination;
use DB;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function create(Request $request): array
    {
        $userTrip = $request->input('trip');

        $trip = null;
        DB::transaction(function () use ($userTrip, &$trip) {
            $trip = Trip::create();
            foreach ($userTrip as $index => $stop) {
                $this->saveTripDestination($stop, $trip->id, $index);
            }
        });
        return ["alias" => $trip->alias];
    }

    public function get(String $alias): array
    {
        $trip = Trip::where('alias', $alias)->first();

        if ($trip == null) {
            abort(404);
        }

        $response = [];

        foreach ($trip->tripDestinations()->get() as $tripDestination) {
            $destination = Destination::where('slug', $tripDestination->destination_slug)->first();
            if ($tripDestination->duration != null) {
                $destination["duration"] = $tripDestination->duration; 
            };
            $response[] = $destination;
        }

        return $response;
    }

    public function update(Request $request, String $alias)
    {
        $userTrip = $request->input('trip');
        $trip = Trip::where('alias', $alias)->first();
        TripDestination::where('trip_id', $trip->id)->delete();
        foreach ($userTrip as $index => $stop) {
            $this->saveTripDestination($stop, $trip->id, $index);
        }
        return 'success';
    }

    private function saveTripDestination(array $stop, int $tripId, int $position)
    {
        $destination = Destination::where('id', $stop["id"])->firstOrFail();
        $duration = isset($stop["duration"]) ? $stop["duration"] : null;
        TripDestination::create([
            'trip_id' => $tripId, 
            'destination_slug' => $destination["slug"], 
            'position' => $position,
            'duration' => $duration
        ]);
    }
}
