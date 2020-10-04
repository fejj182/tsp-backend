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

        $this->firstStop = $this->getDestinationJson();
        $this->secondStop = $this->getDestinationJson();

        // note: creating this way to ensure property order in assertExactJson
        $this->startingDestination = factory(Destination::class)->create($this->firstStop);
        $this->endingDestination = factory(Destination::class)->create($this->secondStop);
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
        $notExistingDestination = $this->getDestinationJson();
        $response = $this->post('/api/trip', ["trip" => array($notExistingDestination, $this->secondStop)]);
        $response->assertStatus(404);
    }

    public function testTripStationsCreated()
    {
        $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop)]);
        $this->assertDatabaseHas('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->startingDestination->slug,
            'position' => 0
        ]);
        $this->assertDatabaseHas('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->endingDestination->slug,
            'position' => 1
        ]);
    }

    public function testGetTrip()
    {
        $this->post('/api/trip', ["trip" => array($this->firstStop, $this->secondStop, $this->firstStop)]);
        $trip = Trip::query()->first();
        $response = $this->get('/api/trip/' . $trip->alias);

        $response->assertStatus(200);
        $response->assertExactJson([
            $this->startingDestination->toArray(),
            $this->endingDestination->toArray(),
            $this->startingDestination->toArray()
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
            'destination_slug' => $this->endingDestination->slug,
            'position' => 0
        ]);
        $this->assertDatabaseHas('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->startingDestination->slug,
            'position' => 1
        ]);

        $this->assertDatabaseMissing('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->startingDestination->slug,
            'position' => 0
        ]);
        $this->assertDatabaseMissing('trip_destinations', [
            'trip_id' => '1',
            'destination_slug' => $this->endingDestination->slug,
            'position' => 1
        ]);
    }

    protected function getDestinationJson(): array
    {
        return [
            'id' => (string) Uuid::uuid4(),
            'name' => $this->faker->city,
            'lat' => $this->faker->latitude,
            'lng' => $this->faker->longitude,
        ];
    }
}
