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
    protected $middle;
    protected $end;
    protected $leg1;
    protected $leg2;
    protected $leg3;
    protected $leg4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClient();
        Log::spy();

        $this->start = [
            'id' => 123,
            'name' => 'BARCELONA-SANTS (Spain)',
            'location' => [
                "longitude" => 1,
                "latitude" => 1
            ],
        ];
        $this->middle = [
            'id' => 456,
            'name' => 'PARIS GARE DE LYON (France)',
            'location' => [
                "longitude" => 2,
                "latitude" => 2
            ],
        ];
        $this->end = [
            'id' => 789,
            'name' => 'PARIS NORD (France)',
            'location' => [
                "longitude" => 3,
                "latitude" => 3
            ],
        ];

        $this->leg1 = [
            "origin" => $this->start,
            "destination" => $this->middle,
            "departure" => "2020-03-11T10:00:00.000+01:00",
            "arrival" => "2020-03-11T16:00:00.000+01:00"
        ];

        $this->leg2 = [
            "origin" => $this->middle,
            "destination" => $this->end,
            "departure" => "2020-03-11T16:00:00.000+01:00",
            "arrival" => "2020-03-11T17:30:00.000+01:00"
        ];

        $this->leg3 = [
            "origin" => $this->end,
            "destination" => $this->middle,
            "departure" => "2020-03-11T10:00:00.000+01:00",
            "arrival" => "2020-03-11T11:00:00.000+01:00"
        ];

        $this->leg4 = [
            "origin" => $this->middle,
            "destination" => $this->start,
            "departure" => "2020-03-11T12:00:00.000+01:00",
            "arrival" => "2020-03-11T18:30:00.000+01:00"
        ];
    }

    protected function setUpStations()
    {
        factory(Station::class)->create([
            'station_id' => 123,
            'country' => 'ES',
            'important' => true
        ]);
        factory(Station::class)->create([
            'station_id' => 789,
            'country' => 'ES',
            'important' => true
        ]);
    }

    protected function setUEndnImportantStations()
    {
        factory(Station::class)->create([
            'station_id' => 123,
            'country' => 'ES',
            'important' => false
        ]);
        factory(Station::class)->create([
            'station_id' => 789,
            'country' => 'ES',
            'important' => false
        ]);
    }

    protected function setUpStationsInDifferentCountries()
    {
        factory(Station::class)->create([
            'station_id' => 123,
            'country' => 'ES',
            'important' => true
        ]);
        factory(Station::class)->create([
            'station_id' => 789,
            'country' => 'FR',
            'important' => true
        ]);
    }

    protected function setUpConnections()
    {
        factory(Connection::class)->create([
            'starting_station' => 123,
            'ending_station' => 789,
            'duration' => 0
        ]);
        factory(Connection::class)->create([
            'starting_station' => 789,
            'ending_station' => 123,
            'duration' => 0
        ]);
    }

    protected function setUpConnectionsWithDurations()
    {
        factory(Connection::class)->create([
            'starting_station' => 123,
            'ending_station' => 789,
            'duration' => 90
        ]);
        factory(Connection::class)->create([
            'starting_station' => 789,
            'ending_station' => 123,
            'duration' => 120
        ]);
    }

    protected function setUpConnectionsWithNoDurations()
    {
        factory(Connection::class)->create([
            'starting_station' => 123,
            'ending_station' => 789,
        ]);
        factory(Connection::class)->create([
            'starting_station' => 789,
            'ending_station' => 123,
        ]);
    }

    public function testCaptureCommandSameCountry()
    {
        $this->setUpStations();
        $this->setUpConnections();

        $this->addFakeJsonResponse([$this->leg1, $this->leg2]);
        $this->addFakeJsonResponse([$this->leg3, $this->leg4]);

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $startToMiddle = Connection::query()->where([
            ['starting_station', '=', $this->start['id']],
            ['ending_station', '=', $this->middle['id']]
        ])->first();
        $middleToEnd = Connection::query()->where([
            ['starting_station', '=', $this->middle['id']],
            ['ending_station', '=', $this->end['id']],
        ])->first();
        $endToMiddle = Connection::query()->where([
            ['starting_station', '=', $this->end['id']],
            ['ending_station', '=', $this->middle['id']],
        ])->first();
        $middleToStart = Connection::query()->where([
            ['starting_station', '=', $this->middle['id']],
            ['ending_station', '=', $this->start['id']]
        ])->first();

        $captured = Station::query()->where('station_id', '=', $this->middle['id'])->first();

        $this->assertNotEmpty($captured);
        $this->assertEquals(360, $startToMiddle->duration);
        $this->assertEquals(90, $middleToEnd->duration);
        $this->assertEquals(60, $endToMiddle->duration);
        $this->assertEquals(390, $middleToStart->duration);
    }

    public function testCaptureCommandDifferentCountries()
    {
        $this->setUpStationsInDifferentCountries();
        $this->setUpConnections();

        $this->addFakeJsonResponse([$this->leg1, $this->leg2]);
        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $startToMiddle = Connection::query()->where([
            ['starting_station', '=', $this->start['id']],
            ['ending_station', '=', $this->middle['id']]
        ])->first();
        $middleToEnd = Connection::query()->where([
            ['starting_station', '=', $this->middle['id']],
            ['ending_station', '=', $this->end['id']],
        ])->first();
        $endToMiddle = Connection::query()->where([
            ['starting_station', '=', $this->end['id']],
            ['ending_station', '=', $this->middle['id']],
        ])->first();
        $middleToStart = Connection::query()->where([
            ['starting_station', '=', $this->middle['id']],
            ['ending_station', '=', $this->start['id']]
        ])->first();

        $captured = Station::query()->where('station_id', '=', $this->middle['id'])->first();

        $this->assertNotEmpty($captured);
        $this->assertEquals(360, $startToMiddle->duration);
        $this->assertEquals(90, $middleToEnd->duration);
        $this->assertEmpty($endToMiddle);
        $this->assertEmpty($middleToStart);
    }

    public function testCaptureCommandConnectionsAlreadyHaveDurations()
    {
        $this->setUpStations();
        $this->setUpConnectionsWithDurations();

        $this->addFakeJsonResponse([$this->leg1, $this->leg2]);
        $this->addFakeJsonResponse([$this->leg3, $this->leg4]);

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->middle['id'])->first();

        $this->assertEmpty($captured);
    }

    public function testCaptureCommandConnectionsFailedResponse()
    {
        $this->setUpStations();
        $this->setUpConnections();

        $this->addErrorResponse();

        $this->artisan('connections:capture ES')
            ->expectsOutput('Failed')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->middle['id'])->first();

        $this->assertEmpty($captured);
    }

    public function testCaptureCommandShouldNotCallApiIfDurationHasNotExpired()
    {
        $this->setUpStations();
        $this->setUpConnections();
        $this->artisan('connections:capture ES --days=1')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $captured = Station::query()->where('station_id', '=', $this->middle['id'])->first();

        $this->assertEmpty($captured);
    }
}
