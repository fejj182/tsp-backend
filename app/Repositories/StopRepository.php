<?php

namespace App\Repositories;

use App\Models\Stop;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;

class StopRepository
{
  public function getStopsConnectedToStation(Station $station): Collection
  {
    return Stop::query()
    ->select('station_id')
    ->whereIn('journey_id', $station->stops->map(function($stop) {
      return $stop->journey_id;
    }))
    ->groupBy('station_id')
    ->get();
  }
}