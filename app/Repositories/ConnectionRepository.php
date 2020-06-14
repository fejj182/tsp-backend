<?php

namespace App\Repositories;

use App\Models\Connection;
use Illuminate\Database\Eloquent\Collection;

class ConnectionRepository
{
  public function findByStartingStationId(int $stationId): Collection
  {
    return Connection::query()
    ->where('starting_station', '=', $stationId)
    ->where('duration', '>', 0)
    ->get();
  }
}