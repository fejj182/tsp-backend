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

    public function index(): Collection
    {
        return Station::all();
    }

    public function nearest(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        $nearestStation = $this->stations->getNearestStation($lat, $lng);
        $connectingStations = $this->stations->getConnectingStations($nearestStation);

        $nearestStation['connectingStations'] = $connectingStations->values();

        return $nearestStation;
    }
}