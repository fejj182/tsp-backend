<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $trip_id
 * @property string $destination_slug
 * @property int $position
 */
class TripDestination extends Model
{
    public $timestamps = false;
    protected $table = 'trip_destinations';
    protected $fillable = ['trip_id', 'destination_slug', 'position'];
}
