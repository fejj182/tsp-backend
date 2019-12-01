<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $station_id
 * @property string $arrival_time
 * @property string $departure_time
 * @property int $stop_sequence
 * @property string $journey_id
 */
class Stop extends Model
{
    public $timestamps = false;
    protected $table = 'stops';

    public function station()
    {
        return $this->belongsTo('App\Models\Station', 'station_id', 'station_id');
    }
}
