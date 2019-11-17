<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Repositories\StationRepository;
use App\Repositories\StopRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StationController extends Controller
{
    public function __construct() {
        $this->stationRepository = new StationRepository();
        $this->stopRepository = new StopRepository();
    }

    public function index(): Collection
    {
        return Station::all();
    }

    public function nearest(Request $request)
    {
        $lat = $request->input('lat');
        $lon = $request->input('lon');

        $nearestStation = $this->stationRepository->getNearestStation($lat, $lon);
        $journeyIds = $this->getJourneyIdsContainingStation($nearestStation);
        $journeyStops = $this->stopRepository->getStopsForJourneys($journeyIds);
        $connectingStations = $this->getConnectionsForStation($nearestStation, $journeyStops);

        $nearestStation['connectingStations'] = $connectingStations->values();

        return $nearestStation;
    }

    private function getJourneyIdsContainingStation(Station $station): array
    {
      return $station
              ->stops
              ->map(function($stop) {
                  return $stop->journey_id;
              })
              ->toArray();
    }

    private function getConnectionsForStation(Station $station, Collection $stops): Collection
    {
        return $stops
            ->map(function($stop) {
                return $stop->station;
            })
            ->filter(function($connection) use ($station) {
                return $connection->enabled && $connection->station_id != $station->station_id;
            });
    }
}