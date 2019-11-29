<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\Stop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationsTest extends TestCase
{
    use RefreshDatabase;

    public function testAll()
    {
        factory(Station::class)->create([
            'name' => 'Barcelona-Sants',
        ]);
        $response = $this->get('/api/stations');
        $response->assertJsonFragment(["name" => "Barcelona-Sants"]);
        $response->assertStatus(200);
    }

    public function testNearest()
    {
        $barcelona = [
            'name' => 'Barcelona-Sants',
            'lat' => '41.379520',
            'lng' => '2.140624'
        ];
        $valencia = [
            'name' => 'Valencia-Estacio del Nord',
            'lat' => '39.465064',
            'lng' => '-0.377433'
        ];

        $this->createStation($barcelona);
        $this->createStation($valencia);

        $response = $this->post('/api/stations/nearest', ["lat" => "39.465", "lng" => "-0.377"]);

        $nearest = $valencia;
        $nearest["connectingStations"] = array($barcelona);
        
        $response->assertExactJson($nearest);
        $response->assertStatus(200);
    }

    private function createStation($data) 
    {
        factory(Station::class)->create($data)->each(function ($station) {
            $station->stops()->save(factory(Stop::class)->make([
                'station_id' => $station->station_id,
                'journey_id' => '123ABC'
            ]));
        });
    }
}
