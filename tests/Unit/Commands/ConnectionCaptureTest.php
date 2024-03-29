<?php

use App\Models\Connection;
use App\Models\Station;
use Carbon\Carbon;
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

    public function testCaptureCommandWithDurationZero()
    {
        $connection = factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 0
        ]);

        $this->addFakeJsonResponse($this->twoLegs($this->start, $this->end));

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $startToMiddle = Connection::query()->where('starting_station', '=', $this->start->station_id)->first();
        $middleToEnd = Connection::query()->where('starting_station', '=', $this->connectionId)->first();

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();

        $this->assertNotEmpty($captured);
        $this->assertEquals('Barcelona', $captured->name);
        $this->assertEquals('ES', $captured->country);
        $this->assertEquals($connection->id, $captured->captured_by);
        $this->assertEquals(false, $captured->important);

        $this->assertEquals(360, $startToMiddle->duration);
        $this->assertEquals(90, $middleToEnd->duration);

        $this->assertGuzzleCalledWithUrl("mockHost/journeys/{$connection->starting_station}/{$connection->ending_station}/capture");
        $this->assertGuzzleCalledTimes(1);
    }

    public function testCaptureCommandConnectionsAlreadyHaveDurations()
    {
        factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 90
        ]);

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();

        $this->assertEmpty($captured);
        $this->assertGuzzleNotCalled();
    }

    public function testCaptureCommandReturnsEmptyResponse()
    {
        $connection = factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 0,
            'updated_at' => Carbon::now()->subDays(2)
        ]);
        $secondConnection = factory(Connection::class)->create([
            'starting_station' => 123,
            'ending_station' => 456,
            'duration' => 0,
            'updated_at' => Carbon::now()->subDay()
        ]);

        $this->addFakeJsonResponse([]);
        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->connectionId)->first();

        $this->assertEmpty($captured);
        $this->assertGuzzleCalledTimes(1);
        $this->assertTrue(new Carbon($connection->update_at) > new Carbon($secondConnection->updated_at));
    }

    public function testCaptureCommandReturnsOnlyFirstLeg()
    {
        factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 0
        ]);

        $this->addFakeJsonResponse($this->oneLegOnly($this->start, $this->end));

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $this->assertGuzzleCalledTimes(1);
        $this->assertEquals(360, Connection::first()->duration);
    }

    public function testCaptureCommandConnectionAlreadyCaptured()
    {
        $connection = factory(Connection::class)->create([
            'starting_station' => $this->start->station_id,
            'ending_station' => $this->end->station_id,
            'duration' => 0,
            'updated_at' => Carbon::now()->subDay()
        ]);

        factory(Station::class)->create([
            'station_id' => $this->connectionId
        ]);

        $this->addFakeJsonResponse($this->twoLegs($this->start, $this->end));

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $this->assertGuzzleCalledTimes(1);
        $this->assertTrue(Connection::first()->updated_at > $connection->updated_at);
    }

    public function testCaptureCommandConnectionsFailedResponse()
    {
        $connection = factory(Connection::class)->create([
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
        $this->assertGuzzleCalledWithUrl("mockHost/journeys/{$connection->starting_station}/{$connection->ending_station}/capture");
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

    protected function twoLegs($start, $end)
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

    protected function oneLegOnly($start, $end)
    {
        $origin = [
            'id' => $start->station_id,
            'name' => 'VALENCIA (Spain)',
            'location' => [
                "longitude" => 1,
                "latitude" => 1
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
            "destination" => $destination,
            "departure" => "2020-03-11T10:00:00.000+01:00",
            "arrival" => "2020-03-11T16:00:00.000+01:00"
        ];

        return ["firstLeg" => $leg1];
    }
}
