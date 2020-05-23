<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Trip;
use App\Models\TripDestination;
use DB;
use Illuminate\Http\Request;

class TripControllerV2 extends Controller
{
    public function create(Request $request): array
    {
        $userTrip = $request->input('trip');

        $trip = null;
        DB::transaction(function () use ($userTrip, &$trip) {
            $trip = Trip::create();
            foreach ($userTrip as $index => $stop) {
                $destination = Destination::where('id', $stop["id"])->firstOrFail();
                TripDestination::create(['trip_id' => $trip->id, 'destination_slug' => $destination["slug"], 'position' => $index]);
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

        $destinationSlugs = $trip->tripDestinations()->pluck('destination_slug');
        $response = [];

        foreach ($destinationSlugs as $slug) {
            $nextDestination = Destination::where('slug', $slug)->first();
            $response[] = $nextDestination;
        }

        return $response;
    }

    public function update(Request $request, String $alias)
    {
        $userTrip = $request->input('trip');
        $trip = Trip::where('alias', $alias)->first();
        TripDestination::where('trip_id', $trip->id)->delete();
        foreach ($userTrip as $index => $stop) {
            $destination = Destination::where('id', $stop["id"])->first();
            TripDestination::create(['trip_id' => $trip->id, 'destination_slug' => $destination->slug, 'position' => $index]);
        }
        return 'success';
    }
}
