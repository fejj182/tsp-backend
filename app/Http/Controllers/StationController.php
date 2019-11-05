<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Station;
use Illuminate\Support\Collection;

class StationController extends Controller
{
    public function index(): Collection
    {
        return Station::all();
    }
}