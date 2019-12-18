<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\Stop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;

class StationsTest extends TestCase
{
    use RefreshDatabase;

    protected $barcelona;
    protected $valencia;
    protected $disabled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barcelona = [
            'id' => Uuid::uuid4(),
            'name' => 'Barcelona-Sants',
            'lat' => '41.379520',
            'lng' => '2.140624'
        ];
        $this->valencia = [
            'id' => Uuid::uuid4(),
            'name' => 'Valencia-Estacio del Nord',
            'lat' => '39.465064',
            'lng' => '-0.377433'
        ];
        $this->disabled = [
            'id' => Uuid::uuid4(),
            'name' => 'Glasgow',
            'lat' => '0',
            'lng' => '0',
            'enabled' => false
        ];

    }

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

        $response = $this->post('/api/stations/nearest', ["lat" => "41.379520", "lng" => "2.140624"]);
        
        $response->assertExactJson($this->barcelona);
        $response->assertStatus(200);
    }

    public function testConnections()
    {
        $barcelona = $this->createStation($this->barcelona);
        $this->createStation($this->valencia);

        $response = $this->post('/api/stations/connections', ["stationId" => $barcelona->id]);

        $response->assertExactJson([$this->valencia]);
        $response->assertStatus(200);
    }

    private function createStation($data): Station
    {
        $station = factory(Station::class)->create($data);
        $station->stops()->save(factory(Stop::class)->make([
            'station_id' => $station->station_id,
            'journey_id' => '123ABC'
        ]));
        return $station;
    }
}
