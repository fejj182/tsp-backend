<?php

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

        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);
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
        $this->post('/api/trip', ["trip" => array($this->barcelona, $this->valencia, $this->barcelona)]);
        $trip = Trip::query()->first();
        $response = $this->get('/api/trip/' . $trip->alias);
        $response->assertStatus(200);
        $response->assertExactJson([$this->barcelona, $this->valencia, $this->barcelona]);
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