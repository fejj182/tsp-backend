<?php

namespace Tests\Unit;

use App\Models\Station;
use App\Models\Stop;
use App\Repositories\StopRepository;
use Tests\TestCase;

class StopRepositoryTest extends TestCase
{
    public function testGetStopsConnectedToStation_shouldReturnJourneyId()
    {
        $stops = new StopRepository();

        $start = $this->createStop('1', '1-2');
        $end = $this->createStop('2', '1-2');
        $this->assertEquals('1-2', $stops->getJourneyToDisplayBetweenStations($start, $end));
    }

    private function createStop(String $stopSequence, String $journeyId) {
        $stop = factory(Station::class)->create();
        $stop->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => $stopSequence,
            'journey_id' => $journeyId
        ]));
        return $stop;
    }

    public function testGetStopsConnectedToStation_shouldReturnJourneyIdWithLongestJourney()
    {
        $stops = new StopRepository();

        $start = $this->createStop('1', '1-2');
        $middle = $this->createStop('2', '1-2');

        $start->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => '1',
            'journey_id' => '1-2-3'
        ]));
        $middle->stops()->save(factory(Stop::class)->create([
            'stop_sequence' => '2',
            'journey_id' => '1-2-3'
        ]));

        $end = $this->createStop('3', '1-2-3');
    
        $this->assertEquals('1-2-3', $stops->getJourneyToDisplayBetweenStations($start, $end));
    }
}
