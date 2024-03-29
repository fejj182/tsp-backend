<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testEnabled()
    {
        $station = factory(Station::class)->create();
        $anotherStation = factory(Station::class)->create();
        factory(Station::class)->create(['enabled' => false]);

        $response = $this->get('/api/stations');

        $response->assertExactJson([$station->toArray(), $anotherStation->toArray()]);
    }

    public function testNearest()
    {
        $close = ["lat" => 1, "lng" => 1.5];
        $far = ["lat" => 10, "lng" => 10];
        $closeDisabled = ["lat" => 1, "lng" => 1, 'enabled' => false];

        $closeStation = factory(Station::class)->create($close);
        factory(Station::class)->create($far);
        factory(Station::class)->create($closeDisabled);

        $response = $this->post('/api/stations/nearest', ["lat" => 1, "lng" => 1]);

        $response->assertExactJson($closeStation->toArray());
        $response->assertStatus(200);
    }

    public function testConnections()
    {
        $startingStation = factory(Station::class)->create();
        $endingStation = factory(Station::class)->create();
        $connection = factory(Connection::class)->create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => 123
        ]);
        factory(Connection::class)->create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => factory(Station::class)->create()->station_id,
            'duration' => 0
        ]);
        factory(Connection::class)->create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => factory(Station::class)->create(['enabled' => false])->station_id,
            'duration' => 123
        ]);

        $response = $this->post('/api/stations/connections',  ['stationId' => $startingStation->id]);

        $result = $endingStation->toArray();
        $result["duration"] = $connection->duration;

        $response->assertExactJson([$result]);
    }
}
