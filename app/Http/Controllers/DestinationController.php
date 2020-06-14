<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Station;
use App\Repositories\ConnectionRepository;
use App\Repositories\StationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    protected $connectionRepository;
    protected $stationRepository;

    public function __construct(
        ConnectionRepository $connectionRepository,
        StationRepository $stationRepository
    ) {
        $this->connectionRepository = $connectionRepository;
        $this->stationRepository = $stationRepository;
    }

    public function enabled(): Collection
    {
        return Destination::all();
    }

    public function connections(Request $request)
    {
        $destinationId = $request->input('destinationId');
        $startingDestination = Destination::query()->where('id', $destinationId)->first();

        $result = collect([]);
        foreach ($startingDestination->stations as $station) {
            
            $startingStation = Station::where('id', $station->id)->first();
            $connections = $this->connectionRepository->findByStartingStationId($startingStation->station_id);
            $connections->each(function ($connection) use ($result) {
                $endingStation = $this->stationRepository->findOneByStationId($connection->ending_station);
                if ($endingStation != null) {
                    $endingDestination = Destination::where('id', $endingStation->destination_id)->first();
                    $endingDestination->duration = $connection->duration;

                    $destinationIdsEqual = function ($value) use ($endingDestination) {
                        return $value->id == $endingDestination->id;
                    };
                    
                    $indexOrFalse = $result->search($destinationIdsEqual);
                    if ($indexOrFalse === false) {
                        $result->push($endingDestination);
                    } else {
                        $existingResult = $result->get($indexOrFalse);
                        if ($existingResult->duration > $endingDestination->duration) {
                            $existingResult->duration = $endingDestination->duration;
                        }
                    }
                }
            });
        }

        return $result;
    }
}
