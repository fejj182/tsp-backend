<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Station;
use App\Repositories\StationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StationController extends Controller
{
    protected $stations;

    public function __construct(StationRepository $stations)
    {
        $this->stations = $stations;
    }

    public function enabled(): Collection
    {
        return $this->stations->enabled();
    }

    public function nearest(Request $request): Station
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        return $this->stations->getNearestStation($lat, $lng);
    }

    //TODO: Move to repository and unit test to remove disabled stations etc.
    public function connections(Request $request)
    {
        $stationId = $request->input('stationId');
        $result = collect([]);

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
                $endingStation->duration = $connection->duration;
                $result->push($endingStation);
            }
        });

        return $result;
    }
}
