<?php

namespace App\Http\Controllers;

use App\Models\Trip;

class TripController extends Controller
{
    public function create()
    {
        $trip = Trip::create();
        return ["alias" => $trip->alias];
    }
}
