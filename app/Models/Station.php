<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    public $timestamps = false;
    protected $table = 'stations';
    protected $hidden = ['id', 'station_id', 'enabled', 'distance', 'stops'];

    public function stops()
    {
        return $this->hasMany('App\Models\Stop', 'station_id', 'station_id');
    }
}
