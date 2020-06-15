<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Destination;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testDestinationsEnabled()
    {
        $destination = factory(Destination::class)->create();
        $station = factory(Station::class)->create(['destination_id' => $destination->id]);
        factory(Connection::class)->create(['starting_station' => $station->station_id, 'duration' => 100]);
        
        $destination2 = factory(Destination::class)->create();
        $station2 = factory(Station::class)->create(['destination_id' => $destination2->id]);
        factory(Connection::class)->create(['starting_station' => $station2->station_id, 'duration' => 100]);

        $destination3 = factory(Destination::class)->create();
        factory(Station::class)->create(['destination_id' => $destination3->id]);
        factory(Connection::class)->create(['duration' => 100]);
        
        $destination4 = factory(Destination::class)->create();
        $station4 = factory(Station::class)->create(['destination_id' => $destination4->id]);
        factory(Connection::class)->create(['starting_station' => $station4->station_id, 'duration' => 0]);

        $response = $this->get('/api/destinations');

        $response->assertExactJson([$destination->toArray(), $destination2->toArray()]);
    }

    public function testDestinationConnections()
    {
        $startingDestination = factory(Destination::class)->create();
        $startingStation = factory(Station::class)->create(['destination_id' => $startingDestination->id]);

        $endingDestination = factory(Destination::class)->create();
        $endingStation = factory(Station::class)->create(['destination_id' => $endingDestination->id]);

        $connection = factory(Connection::class)->create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => 123
        ]);

        $response = $this->post('/api/destinations/connections',  ['destinationId' => $startingDestination->id]);

        $result = $endingDestination->toArray();
        $result["duration"] = $connection->duration;

        $response->assertExactJson([$result]);
    }

    public function testDestinationWithMultipleStations()
    {
        $startingDestination = factory(Destination::class)->create();
        $startingStation = factory(Station::class)->create(['destination_id' => $startingDestination->id]);
        $startingStationSameDestination = factory(Station::class)->create(['destination_id' => $startingDestination->id]);

        $endingDestination = factory(Destination::class)->create();
        $endingStation = factory(Station::class)->create(['destination_id' => $endingDestination->id]);
        $endingDifferentDestination = factory(Destination::class)->create();
        $endindDifferentStation = factory(Station::class)->create(['destination_id' => $endingDifferentDestination->id]);

        $connection = factory(Connection::class)->create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => 123
        ]);
        $connectionDifferent = factory(Connection::class)->create([
            'starting_station' => $startingStationSameDestination->station_id,
            'ending_station' => $endindDifferentStation->station_id,
            'duration' => 123
        ]);
        

        $response = $this->post('/api/destinations/connections',  ['destinationId' => $startingDestination->id]);

        $result = collect([]);
        
        $endingDestination->duration = $connection->duration;
        $result->push($endingDestination);

        $endingDifferentDestination->duration = $connectionDifferent->duration;
        $result->push($endingDifferentDestination);

        $response->assertExactJson($result->toArray());
    }

    public function testDestinationWithMultipleStationsThatHaveTheSameConnection()
    {
        $startingDestination = factory(Destination::class)->create();
        $startingStation = factory(Station::class)->create(['destination_id' => $startingDestination->id]);
        $startingStationSameDestination = factory(Station::class)->create(['destination_id' => $startingDestination->id]);

        $endingDestination = factory(Destination::class)->create();
        $endingStation = factory(Station::class)->create(['destination_id' => $endingDestination->id]);

        $connection = factory(Connection::class)->create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => 123
        ]);
        factory(Connection::class)->create([
            'starting_station' => $startingStationSameDestination->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => 123
        ]);
        

        $response = $this->post('/api/destinations/connections',  ['destinationId' => $startingDestination->id]);

        $result = collect([]);
        
        $endingDestination->duration = $connection->duration;
        $result->push($endingDestination);

        $response->assertExactJson($result->toArray());
    }

    public function testDestinationWithMultipleStationsThatHaveTheSameConnectionReturnsSmallerDuration()
    {
        $startingDestination = factory(Destination::class)->create();
        $startingStation = factory(Station::class)->create(['destination_id' => $startingDestination->id]);
        $startingStationSameDestination = factory(Station::class)->create(['destination_id' => $startingDestination->id]);

        $endingDestination = factory(Destination::class)->create();
        $endingStation = factory(Station::class)->create(['destination_id' => $endingDestination->id]);

        $connection = factory(Connection::class)->create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => 123
        ]);
        factory(Connection::class)->create([
            'starting_station' => $startingStationSameDestination->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => 124
        ]);
        

        $response = $this->post('/api/destinations/connections',  ['destinationId' => $startingDestination->id]);

        $result = collect([]);
        
        $endingDestination->duration = $connection->duration;
        $result->push($endingDestination);

        $response->assertExactJson($result->toArray());
    }
}
