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
    public function getStopsToDisplayBetweenStations_shouldReturnJourneyId()
    {
        $stops = new StopRepository();

        $start = $this->createStop('1', '1-2');
        $end = $this->createStop('2', '1-2');
        $this->assertEquals(Stop::all(), $stops->getStopsToDisplayBetweenStations($start, $end));
    }

    private function createStop(String $stopSequence, String $journeyId) {
        $stop = factory(Station::class)->create();
        $stop->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => $stopSequence,
            'journey_id' => $journeyId
        ]));
        return $stop;
    }

    /**
     * @test
     */
    public function getStopsToDisplayBetweenStations_shouldReturnJourneyIdWithLongestJourney()
    {
        $stops = new StopRepository();

        $start = $this->createStop('1', '1-2');
        $end = $this->createStop('2', '1-2');

        $start->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => '1',
            'journey_id' => '1-2-3'
        ]));
        
        $this->createStop('2', '1-2-3');
        $end->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => '3',
            'journey_id' => '1-2-3'
        ]));

        $this->assertEquals(Stop::query()->where('journey_id', '1-2-3')->get(), $stops->getStopsToDisplayBetweenStations($start, $end));
    }

    /**
     * @test
     */
    public function getStopsToDisplayBetweenStations_shouldReturnJourneyIdWithBothStations()
    {
        $stops = new StopRepository();

        $start = $this->createStop('1', '1-2');
        $start->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => '3',
            'journey_id' => '2'
        ]));
        $end = $this->createStop('2', '1-2');
        $end->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => '4',
            'journey_id' => '3'
        ]));
        
        $this->assertEquals(Stop::query()->where('journey_id', '1-2')->get(), $stops->getStopsToDisplayBetweenStations($start, $end));
    }
}
