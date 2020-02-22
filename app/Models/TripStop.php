<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $trip_id
 * @property string $station_id
 * @property int $position
 */
class TripStop extends Model
{
    public $timestamps = false;
    protected $table = 'trip_stops';
    protected $fillable = ['trip_id', 'station_id', 'position'];
}
