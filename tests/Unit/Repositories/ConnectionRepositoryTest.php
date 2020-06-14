<?php

use App\Models\Connection;
use App\Repositories\ConnectionRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConnectionRepositoryTest extends TestCase {
    use DatabaseMigrations;

    /** @test */
    public function findByStartingStationId()
    {
        factory(Connection::class)->create([
            'starting_station' => 123,
            'ending_station' => 456,
            'duration' => 100
        ]);
        factory(Connection::class)->create([
            'starting_station' => 123,
            'ending_station' => 789,
            'duration' => 0
        ]);
        factory(Connection::class)->create([
            'starting_station' => 456,
            'ending_station' => 123,
            'duration' => 100
        ]);
        $connectionRepository = new ConnectionRepository();
        $connections = $connectionRepository->findByStartingStationId(123);

        $this->assertEquals(1, $connections->count()); 
    }
    

}