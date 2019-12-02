<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\Stop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationsTest extends TestCase
{
    use RefreshDatabase;
    
    protected $barcelona = [
        'name' => 'Barcelona-Sants',
        'lat' => '41.379520',
        'lng' => '2.140624'
    ];
    protected $valencia = [
        'name' => 'Valencia-Estacio del Nord',
        'lat' => '39.465064',
        'lng' => '-0.377433'
    ];
    protected $disabled = [
        'name' => 'Glasgow',
        'lat' => '0',
        'lng' => '0',
        'enabled' => false
    ];

    public function testEnabled()
    {
        $this->createStation($this->barcelona);
        $this->createStation($this->valencia);
        $this->createStation($this->disabled);

        $response = $this->get('/api/stations');

        $response->assertExactJson([$this->barcelona, $this->valencia]);
    }

    public function testNearest()
    {
        $this->createStation($this->barcelona);
        $this->createStation($this->valencia);
        $this->createStation($this->disabled);

        $response = $this->post('/api/stations/nearest', ["lat" => "39.465", "lng" => "-0.377"]);

        $nearest = $this->valencia;
        $nearest["connectingStations"] = array($this->barcelona);
        
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
