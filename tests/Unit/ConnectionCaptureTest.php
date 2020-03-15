<?php

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Concerns\FakeRequests;

class ConnectionCaptureTest extends TestCase
{
    use DatabaseMigrations;
    use FakeRequests;

    protected $start;
    protected $end;
    protected $connectionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClient();
        Log::spy();

        $this->start = factory(Station::class)->create([
            'country' => 'ES',
            'important' => true
        ]);
        $this->end = factory(Station::class)->create([
            'country' => 'FR',
            'important' => true
        ]);

        $this->connectionId = rand(0, 999);
    }

    public function testCaptureCommand()
    {
        factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 0
        ]);

        $this->addFakeJsonResponse($this->fakeLegs($this->start, $this->end));

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $startToMiddle = Connection::query()->where('starting_station', '=', $this->start->station_id)->first();
        $middleToEnd = Connection::query()->where('starting_station', '=', $this->connectionId)->first();

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();

        $this->assertNotEmpty($captured);
        $this->assertEquals('ES', $captured->country);
        $this->assertEquals(true, $captured->captured);
        $this->assertEquals(false, $captured->important);

        $this->assertEquals(360, $startToMiddle->duration);
        $this->assertEquals(90, $middleToEnd->duration);

        $this->assertGuzzleCalledTimes(1);
    }

    public function testCaptureCommandConnectionsAlreadyHaveDurations()
    {
        factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 90
        ]);

        $this->addFakeJsonResponse($this->fakeLegs($this->start, $this->end));

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();

        $this->assertEmpty($captured);
        $this->assertGuzzleNotCalled();
    }

    public function testCaptureCommandConnectionsFailedResponse()
    {
        factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 0
        ]);

        $this->addErrorResponse();

        $this->artisan('connections:capture ES')
            ->expectsOutput('Failed')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();

        $this->assertEmpty($captured);
        $this->assertGuzzleCalledTimes(1);
    }

    public function testCaptureCommandShouldNotTryAndCallApiForNotImportantStations()
    {
        $start = factory(Station::class)->create([
            'country' => 'ES',
            'important' => false
        ]);
        $end = factory(Station::class)->create([
            'country' => 'FR',
            'important' => false
        ]);

        factory(Connection::class)->create([
            'starting_station' => $start->station_id,
            'ending_station' => $end->station_id,
            'duration' => 0
        ]);

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();
        $this->assertEmpty($captured);

        $this->assertGuzzleNotCalled();
    }

    public function testCaptureCommandShouldNotCallApiIfDurationHasNotExpired()
    {
        factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 0
        ]);

        $this->artisan('connections:capture ES --days=1')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();

        $this->assertEmpty($captured);
        $this->assertGuzzleNotCalled();
    }

    //TODO: Test logs

    protected function fakeLegs($start, $end)
    {
        $origin = [
            'id' => $start->station_id,
            'name' => 'VALENCIA (Spain)',
            'location' => [
                "longitude" => 1,
                "latitude" => 1
            ],
        ];
        $connection = [
            'id' => $this->connectionId,
            'name' => 'BARCELONA (Spain)',
            'location' => [
                "longitude" => 2,
                "latitude" => 2
            ],
        ];
        $destination = [
            'id' => $end->station_id,
            'name' => 'PARIS NORD (France)',
            'location' => [
                "longitude" => 3,
                "latitude" => 3
            ],
        ];

        $leg1 = [
            "origin" => $origin,
            "destination" => $connection,
            "departure" => "2020-03-11T10:00:00.000+01:00",
            "arrival" => "2020-03-11T16:00:00.000+01:00"
        ];

        $leg2 = [
            "origin" => $connection,
            "destination" => $destination,
            "departure" => "2020-03-11T16:00:00.000+01:00",
            "arrival" => "2020-03-11T17:30:00.000+01:00"
        ];

        return ["firstLeg" => $leg1, "secondLeg" => $leg2];
    }
}
