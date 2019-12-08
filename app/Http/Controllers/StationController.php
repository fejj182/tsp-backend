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

    public function __construct(StationRepository $stations) {
        $this->stations = $stations;
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
        return $this->stations->getConnectingStations($station)->values();
    }
}