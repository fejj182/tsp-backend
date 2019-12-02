<?php

namespace App\Repositories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;

class StationRepository
{
  protected $stops;

  public function __construct(StopRepository $stops) {
    $this->stops = $stops;
  }

  public function enabled() 
  {
    return Station::query()
    ->where('enabled', true)
    ->get();
  }

  public function getNearestStation(string $lat, string $lng): Station
  {
    return Station::query()
    ->selectRaw('*, (
        3959 *
        acos(cos(radians(?)) * 
        cos(radians(lat)) * 
        cos(radians(lng) - 
        radians(?)) + 
        sin(radians(?)) * 
        sin(radians(lat )))
     ) AS distance', [$lat, $lng, $lat])
    ->where('enabled', 1)
    ->orderBy('distance', 'asc')
    ->first();
  }

  public function getConnectingStations(Station $station): Collection
  {
    $stops = $this->stops->getStopsConnectedToStation($station);

    return $stops
    ->map(function($stop) {
      return $stop->station;
    })
    ->filter(function($connectingStation) use ($station) {
      return $connectingStation->enabled && $connectingStation->station_id != $station->station_id;
    });
  }
}