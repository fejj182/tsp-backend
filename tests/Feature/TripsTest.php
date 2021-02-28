<?php

use App\Models\Destination;
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

        $this->startingDestination = factory(Destination::class)->create();
        $this->endingDestination = factory(Destination::class)->create();

        $this->firstStop = $this->startingDestination->toArray();
        $this->secondStop = $this->endingDestination->toArray();
        $this->secondStop['duration'] = $this->faker->randomNumber();
    }


    public function testTripCreated()
    {
        $response = $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop)]);
        $trip = Trip::query()->first();
        $response->assertStatus(200);
        $response->assertExactJson(["alias" => $trip->alias]);
    }

    public function testTripNotCreatedIfDestinationDoesNotExist()
    {
        $notExistingDestination = $this->getDestination();
        $response = $this->post('/api/trip', ["trip" => array($notExistingDestination, $this->secondStop)]);
        $response->assertStatus(404);
    }

    public function testTripStationsCreated()
    {
        $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop)]);
        $this->assertDatabaseHas('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->firstStop["slug"],
            'position' => 0,
            'duration' => null
        ]);
        $this->assertDatabaseHas('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->secondStop["slug"],
            'position' => 1,
            'duration' => $this->secondStop["duration"]
        ]);
    }

    public function testGetTrip()
    {
        $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop, $this->firstStop)]);
        $trip = Trip::query()->first();
        $response = $this->get('/api/trip/' . $trip->alias);

        $response->assertStatus(200);
        $response->assertExactJson([
            $this->firstStop,
            $this->secondStop,
            $this->firstStop
        ]);
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

        $this->assertDatabaseHas('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->secondStop["slug"],
            'position' => 0,
            'duration' => $this->secondStop["duration"]
        ]);
        $this->assertDatabaseHas('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->firstStop["slug"],
            'position' => 1,
            'duration' => null
        ]);

        $this->assertDatabaseMissing('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->firstStop["slug"],
            'position' => 0,
            'duration' => null
        ]);
        $this->assertDatabaseMissing('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->secondStop["slug"],
            'position' => 1,
            'duration' => $this->secondStop["duration"]
        ]);
    }

    protected function getDestination(): array
    {
        return [
            'id' => (string) Uuid::uuid4(),
            'name' => $this->faker->city,
            'slug' => $this->faker->slug,
            'lat' => $this->faker->latitude,
            'lng' => $this->faker->longitude
        ];
    }
}
