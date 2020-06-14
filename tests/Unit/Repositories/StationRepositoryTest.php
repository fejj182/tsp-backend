<?php

use App\Models\Station;
use App\Repositories\StationRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StationRepositoryTest extends TestCase {
    use DatabaseMigrations;

    /** @test */
    public function findByStartingStationId()
    {
        factory(Station::class)->create(['station_id' => 123]);
        $expectedStation = factory(Station::class)->create(['station_id' => 456]);
        $stationRepository = new StationRepository();

        $actualStation = $stationRepository->findOneByStationId(456);
        
        $this->assertEquals($expectedStation->id, $actualStation->id);
    }

    /** @test */
    public function findByStartingStationId_notEnabled()
    {
        factory(Station::class)->create(['station_id' => 123, 'enabled' => false]);
        $stationRepository = new StationRepository();

        $this->expectException(ModelNotFoundException::class);
        
        $stationRepository->findOneByStationId(123);
    }

}