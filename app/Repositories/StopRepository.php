<?php

namespace App\Repositories;

use App\Models\Stop;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

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

  public function getStopsToDisplayBetweenStations(Station $start, Station $end): Collection
  {
    $journeyIdsContainingStartStation = $this->getJourneyIdsContainingStation($start);
    $journeyIdsContainingEndStation = $this->getJourneyIdsContainingStation($end);
    $sharedJourneyIds = $journeyIdsContainingStartStation->intersect($journeyIdsContainingEndStation);

    $stopFromLongestJourney = $this->getStopFromLongestJourney($sharedJourneyIds);
    return $this->getConnectedStops($stopFromLongestJourney);
  }

  private function getJourneyIdsContainingStation(Station $station): SupportCollection
  {
    return Stop::query()
    ->where('station_id', $station->station_id)
    ->get()
    ->map(function($journey) {
      return $journey->journey_id;
    });
  }

  private function getStopFromLongestJourney(SupportCollection $journeys): Stop
  {
    return Stop::query()
    ->whereIn('journey_id', $journeys)
    ->orderByDesc('stop_sequence')
    ->first();
  }

  private function getConnectedStops(Stop $stop): Collection 
  {
    return Stop::query()
    ->where('journey_id', $stop->journey_id)
    ->get();
  }
}