<?php

use App\Models\Connection;
use App\Models\Station;
use App\Models\Trip;
use Faker\Factory;
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

        $this->firstStop = $this->getStationJson();
        $this->secondStop = $this->getStationJson();

        // note: creating this way to ensure property order in assertExactJson
        $this->startingStation = factory(Station::class)->create($this->firstStop);
        $this->endingStation = factory(Station::class)->create($this->secondStop);
    }

    public function testTripCreated()
    {
        $response = $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop)]);
        $trip = Trip::query()->first();
        $response->assertStatus(200);
        $response->assertExactJson(["alias" => $trip->alias]);
    }

    public function testTripNotCreatedIfStationDoesNotExist()
    {
        $notExistingStation = $this->getStationJson();
        $response = $this->post('/api/trip', ["trip" => array($notExistingStation, $this->secondStop)]);
        $response->assertStatus(404);
    }

    public function testTripStationsCreated()
    {
        $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop)]);
        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->firstStop['id'],
            'position' => 0
        ]);
        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->secondStop['id'],
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

        $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop, $this->firstStop)]);
        $trip = Trip::query()->first();
        $response = $this->get('/api/trip/' . $trip->alias);

        $firstConnection = $this->secondStop;
        $firstConnection['duration'] = 123;

        $secondConnection = $this->firstStop;
        $secondConnection['duration'] = 321;

        $response->assertStatus(200);
        $response->assertExactJson([$this->firstStop, $firstConnection, $secondConnection]);
    }

    public function testGetTrip_whenTripDoesntExist_Return404()
    {
        $response = $this->get('/api/trip/notatrip');
        $response->assertStatus(404);
    }

    public function testUpdateTrip()
    {
        $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop)]);
        $trip = Trip::query()->first();
        $this->post('/api/trip/' . $trip->alias, ["trip" => array($this->secondStop, $this->firstStop)]);

        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->secondStop['id'],
            'position' => 0
        ]);
        $this->assertDatabaseHas('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->firstStop['id'],
            'position' => 1
        ]);

        $this->assertDatabaseMissing('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->firstStop['id'],
            'position' => 0
        ]);
        $this->assertDatabaseMissing('trip_stops', [
            'trip_id' => '1',
            'station_id' => $this->secondStop['id'],
            'position' => 1
        ]);
    }

    protected function getStationJson()
    {
        $faker = Factory::create();
        return [
            'id' => (string) Uuid::uuid4(),
            'name' => $faker->city,
            'slug' => $faker->slug,
            'lat' => $faker->latitude,
            'lng' => $faker->longitude,
        ];
    }
}
