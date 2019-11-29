<?php

namespace App\Repositories;

use App\Models\Station;

class StationRepository
{
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
}