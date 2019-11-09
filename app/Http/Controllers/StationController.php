<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StationController extends Controller
{
    public function index(): Collection
    {
        return Station::all();
    }

    public function nearest(Request $request): Collection
    {
        $lat = $request->input('lat');
        $lon = $request->input('lon');
        return Station::query()
        ->selectRaw('*, (
            3959 *
            acos(cos(radians(?)) * 
            cos(radians(lat)) * 
            cos(radians(lon) - 
            radians(?)) + 
            sin(radians(?)) * 
            sin(radians(lat )))
         ) AS distance', [$lat, $lon, $lat])
        ->where('enabled', 1)
        ->orderBy('distance', 'asc')
        ->take(1)
        ->get();
    }
}