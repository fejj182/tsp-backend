<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripDestination extends Model
{
    public $timestamps = false;
    protected $table = 'trip_destinations';
    protected $fillable = ['trip_id', 'destination_id', 'position'];
}
