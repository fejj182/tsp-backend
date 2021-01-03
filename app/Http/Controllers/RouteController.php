<?php

namespace App\Http\Controllers;

use App\Models\Station;
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('route-builder', ['stations' => Station::all()]);
    }
}
