<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Destination;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function enabled(): Collection
    {
        return Destination::all();
    }

    public function connections(Request $request)
    {
        $destinationId = $request->input('destinationId');
        $startingDestination = Destination::query()->where('id', $destinationId)->first();

        $startingStations = $startingDestination->stations;

        $result = collect([]);
        foreach ($startingStations as $station) {
            $stationId = $station->id;

            $startingStation = Station::query()->where('id', $stationId)->first();
            $connections = Connection::query()
                ->where('starting_station', '=', $startingStation->station_id)
                ->where('duration', '>', 0)
                ->get();

            $connections->each(function ($connection) use ($result) {
                $endingStation = Station::query()
                    ->where([['station_id', '=', $connection->ending_station], ['enabled', true]])
                    ->first();
                if ($endingStation != null) {
                    $endingDestination = Destination::query()->where('id', $endingStation->destination_id)->first();
                    $endingDestination->duration = $connection->duration;
                    $result->push($endingDestination);
                }
            });
        }

        return $result;
    }
}
