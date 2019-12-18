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

  public function getJourneyToDisplayBetweenStations(Station $start, Station $end)
  {
    $stopsWithStartStation = $this->getJourneyIdsContainingStation($start);
    $stopsWithEndStation = $this->getJourneyIdsContainingStation($end);

    $sharedJourneys = $stopsWithStartStation->intersect($stopsWithEndStation);

    return Stop::query()
    ->whereIn('journey_id', $sharedJourneys)
    ->orderByDesc('stop_sequence')
    ->first()
    ->journey_id;
  }

  public function getJourneyIdsContainingStation(Station $station)
  {
    return Stop::query()
    ->where('station_id', $station->station_id)
    ->get()
    ->map(function($journey) {
      return $journey->journey_id;
    });
  }

  public function getStopsFromJourneyId(String $journeyId): Collection 
  {
    return Stop::query()
    ->where('journey_id', $journeyId)
    ->get();
  }
}