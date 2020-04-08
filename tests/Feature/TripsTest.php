<?php

use App\Models\Connection;
use App\Models\Station;
use App\Models\Trip;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class TripsTest extends TestCase
{
    use DatabaseMigrations;
    //TODO: why does use RefreshDatabase not work here?

    protected function setUp(): void
    {
        parent::setUp();

        $this->barcelona = [
            'id' => (string) Uuid::uuid4(),
            'name' => 'Barcelona-Sants',
            'lat' => 41.379520,
            'lng' => 2.140624
        ];
        $this->valencia = [
            'id' => (string) Uuid::uuid4(),
            'name' => 'Valencia-Estacio del Nord',
            'lat' => 39.465064,
            'lng' => -0.377433
        ];

        // note: creating this way to ensure property order in assertExactJson
        $this->startingStation = factory(Station::class)->create($this->barcelona);
        $this->endingStation = factory(Station::class)->create($this->valencia);
    }

    public function testTripCreated()
    {
        $response = $this->post('/api/trip', ["trip" => array($this->barcelona, $this->valencia)]);
        $trip = Trip::query()->first();
        $response->assertStatus(200);
        $response->assertExactJson(["alias" => $trip->alias]);
    }

    public function testTripStationsCreated()
    {
        $this->post('/api/trip', ["trip" => array($this->barcelona, $this->valencia)]);
        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->barcelona['id'],
            'position' => 0
        ]);
        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->valencia['id'],
            'position' => 1
        ]);
    }

    public function testGetTrip()
    {
        factory(Connection::class)->create([
            'starting_station' => $this->startingStation->station_id,
            'ending_station' => $this->endingStation->station_id,
            'duration' => 123
        ]);

        factory(Connection::class)->create([
            'starting_station' => $this->endingStation->station_id,
            'ending_station' => $this->startingStation->station_id,
            'duration' => 321
        ]);

        $this->post('/api/trip', ["trip" => array($this->barcelona, $this->valencia, $this->barcelona)]);
        $trip = Trip::query()->first();
        $response = $this->get('/api/trip/' . $trip->alias);

        $firstConnection = $this->valencia;
        $firstConnection['duration'] = 123;

        $secondConnection = $this->barcelona;
        $secondConnection['duration'] = 321;

        $response->assertStatus(200);
        $response->assertExactJson([$this->barcelona, $firstConnection, $secondConnection]);
    }

    public function testUpdateTrip()
    {
        $this->post('/api/trip', ["trip" => array($this->barcelona, $this->valencia)]);
        $trip = Trip::query()->first();
        $this->post('/api/trip/' . $trip->alias, ["trip" => array($this->valencia, $this->barcelona)]);

        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->valencia['id'],
            'position' => 0
        ]);
        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->barcelona['id'],
            'position' => 1
        ]);

        $this->assertDatabaseMissing('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->barcelona['id'],
            'position' => 0
        ]);
        $this->assertDatabaseMissing('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->valencia['id'],
            'position' => 1
        ]);
    }
}