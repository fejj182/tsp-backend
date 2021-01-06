<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    public function stops()
    {
        return $this->hasMany('App\Models\RouteStops');
    }
}
