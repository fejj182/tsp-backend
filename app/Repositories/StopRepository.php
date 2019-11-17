<?php

namespace App\Repositories;

use App\Models\Stop;
use Illuminate\Database\Eloquent\Collection;

class StopRepository
{
  public function getStopsForJourneys(array $journeyIds): Collection
  {
    return Stop::query()
    ->select('station_id')
    ->whereIn('journey_id', $journeyIds)
    ->groupBy('station_id')
    ->get();
  }
}