<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $starting_station
 * @property int $ending_station
 * @property int $duration
 */
class Connection extends Model
{
    protected $fillable = ['starting_station', 'ending_station', 'duration'];
}
