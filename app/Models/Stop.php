<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    public $timestamps = false;
    protected $table = 'stops';

    public function station()
    {
        return $this->belongsTo('App\Models\Station', 'station_id', 'station_id');
    }
}
