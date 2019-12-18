<?php

namespace App\Repositories;

use App\Models\Stop;
use App\Models\Station;
use DB;
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

  public function getJourneyToDisplayBetweenStations(Station $start, Station $end): string 
  {
    return Stop::query()
    ->where('station_id', $start->station_id)
    ->orWhere('station_id', $end->station_id)
    ->orderBy('stop_sequence', 'DESC')
    ->value('journey_id');
  }

  public function getStopsFromJourneyId(String $journeyId): Collection 
  {
    return Stop::query()
    ->where('journey_id', $journeyId)
    ->get();
  }
}