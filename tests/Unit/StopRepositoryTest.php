<?php

namespace Tests\Unit;

use App\Models\Station;
use App\Models\Stop;
use App\Repositories\StopRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StopRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function getStopsToDisplayBetweenStations_shouldReturnStopsInJourney()
    {
        $stops = new StopRepository();

        $start = $this->createStationWithStop('1', '1-2');
        $end = $this->createStationWithStop('2', '1-2');
        
        $this->assertEquals(Stop::all(), $stops->getStopsToDisplayBetweenStations($start, $end));
    }

    private function createStationWithStop(String $stopSequence, String $journeyId) {
        $station = factory(Station::class)->create();
        $this->createStop($station, $stopSequence, $journeyId);
        return $station;
    }

    private function createStop(Station $station, String $stopSequence, String $journeyId) {
        $stop = factory(Stop::class)->create([
            'stop_sequence' => $stopSequence,
            'journey_id' => $journeyId
        ]);
        $station->stops()->save($stop);
        return $stop;
    }

    /**
     * @test
     */
    public function getStopsToDisplayBetweenStations_shouldReturnStopsInLongerJourney()
    {
        $stops = new StopRepository();

        $start = $this->createStationWithStop('1', '1-2');
        $end = $this->createStationWithStop('2', '1-2');

        $a = $this->createStop($start, '1', '1-2-3');

        $middle = factory(Station::class)->create();
        $b = $this->createStop($middle, '2', '1-2-3');

        $c = $this->createStop($end, '3', '1-2-3');

        $stopsToDisplay = $stops->getStopsToDisplayBetweenStations($start, $end);

        $this->assertEquals(collect([$a, $b, $c])->toArray(), $stopsToDisplay->toArray());
    }

    /**
     * @test
     */
    public function getStopsToDisplayBetweenStations_shouldReturnStopsInJourneyWithLowerLastStopNumberIfStopsNotOnSameJourney()
    {
        $stops = new StopRepository();

        $start = $this->createStationWithStop('3', '2');
        $a = $this->createStop($start, '1', '1-2');

        $end = $this->createStationWithStop('4', '3');
        $b = $this->createStop($end, '2', '1-2');

        $stopsToDisplay = $stops->getStopsToDisplayBetweenStations($start, $end);

        $this->assertEquals(collect([$a, $b])->toArray(), $stopsToDisplay->toArray());
    }
}
