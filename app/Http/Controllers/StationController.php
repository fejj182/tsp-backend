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
    protected $stations;
    protected $stops;

    public function __construct(StationRepository $stations, StopRepository $stops) {
        $this->stations = $stations;
        $this->stops = $stops;
    }

    public function enabled(): Collection
    {
        return $this->stations->enabled();
    }

    public function nearest(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        return $this->stations->getNearestStation($lat, $lng);
    }

    public function connections(Request $request)
    {
        $stationId = $request->input('stationId');

        $station = Station::find($stationId);
        $connections = $this->stations->getConnectingStations($station);
        
        $connections->each(function($connection) use($station) {
            $stops = $this->stops->getStopsToDisplayBetweenStations($station, $connection);
            $connection->coords = $stops->map(function($stop) {
                return [$stop->station->lng, $stop->station->lat];
            });
        });
        
        return $connections->values();
    }
}