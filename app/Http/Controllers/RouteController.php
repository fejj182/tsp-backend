<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Station;
use Auth;
use DB;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('route-builder', ['stations' => Station::all()]);
    }

    public function create(Request $request)
    {
        $input = $request->input('route');

        DB::transaction(function () use ($input) {
            $route = Route::create();
            foreach ($input as $index => $stop) {
                RouteStop::create([
                    'route_id' => $route->id,
                    'station_id' => $stop['id'],
                    'position' => $index,
                ]);
            }
        });
    }
}
