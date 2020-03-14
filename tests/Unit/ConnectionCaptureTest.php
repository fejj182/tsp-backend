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

    protected $barcelona;
    protected $parisNord;
    protected $bcn;
    protected $ply;
    protected $pno;
    protected $leg1;
    protected $leg2;
    protected $leg3;
    protected $leg4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClient();
        Log::spy();

        $this->bcn = [
            'id' => 123,
            'name' => 'BARCELONA-SANTS (Spain)',
            'location' => [
                "longitude" => 1,
                "latitude" => 1
            ],
        ];
        $this->ply = [
            'id' => 456,
            'name' => 'PARIS GARE DE LYON (France)',
            'location' => [
                "longitude" => 2,
                "latitude" => 2
            ],
        ];
        $this->pno = [
            'id' => 789,
            'name' => 'PARIS NORD (France)',
            'location' => [
                "longitude" => 3,
                "latitude" => 3
            ],
        ];

        $this->leg1 = [
            "origin" => $this->bcn,
            "destination" => $this->ply,
            "departure" => "2020-03-11T10:00:00.000+01:00",
            "arrival" => "2020-03-11T16:00:00.000+01:00"
        ];

        $this->leg2 = [
            "origin" => $this->ply,
            "destination" => $this->pno,
            "departure" => "2020-03-11T16:00:00.000+01:00",
            "arrival" => "2020-03-11T17:30:00.000+01:00"
        ];

        $this->leg3 = [
            "origin" => $this->pno,
            "destination" => $this->ply,
            "departure" => "2020-03-11T10:00:00.000+01:00",
            "arrival" => "2020-03-11T11:00:00.000+01:00"
        ];

        $this->leg4 = [
            "origin" => $this->ply,
            "destination" => $this->bcn,
            "departure" => "2020-03-11T12:00:00.000+01:00",
            "arrival" => "2020-03-11T18:30:00.000+01:00"
        ];
    }

    protected function setUpStationsInSameCountry()
    {
        $this->barcelona = [
            'name' => 'Barcelona-Sants',
            'station_id' => 123,
            'country' => 'ES'
        ];
        $this->parisNord = [
            'name' => 'Paris Nord',
            'station_id' => 789,
            'country' => 'ES'
        ];

        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->parisNord);
    }

    protected function setUpStationsInDifferentCountries()
    {
        $this->barcelona = [
            'name' => 'Barcelona-Sants',
            'station_id' => 123,
            'country' => 'ES'
        ];
        $this->parisNord = [
            'name' => 'Paris Nord',
            'station_id' => 789,
            'country' => 'FR'
        ];

        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->parisNord);
    }

    protected function setUpConnections()
    {
        $this->barcelonaToParis = [
            'starting_station' => 123,
            'ending_station' => 789,
            'duration' => 0
        ];
        $this->parisToBarcelona = [
            'starting_station' => 789,
            'ending_station' => 123,
            'duration' => 0
        ];

        factory(Connection::class)->create($this->barcelonaToParis);
        factory(Connection::class)->create($this->parisToBarcelona);
    }

    protected function setUpConnectionsWithDurations()
    {
        $this->barcelonaToParis = [
            'starting_station' => 123,
            'ending_station' => 789,
            'duration' => 90
        ];
        $this->parisToBarcelona = [
            'starting_station' => 789,
            'ending_station' => 123,
            'duration' => 120
        ];

        factory(Connection::class)->create($this->barcelonaToParis);
        factory(Connection::class)->create($this->parisToBarcelona);
    }

    protected function setUpConnectionsWithNoDurations()
    {
        $this->barcelonaToParis = [
            'starting_station' => 123,
            'ending_station' => 789,
        ];
        $this->parisToBarcelona = [
            'starting_station' => 789,
            'ending_station' => 123,
        ];

        factory(Connection::class)->create($this->barcelonaToParis);
        factory(Connection::class)->create($this->parisToBarcelona);
    }

    public function testCaptureCommandSameCountry()
    {
        $this->setUpStationsInSameCountry();
        $this->setUpConnections
();

        $this->addFakeJsonResponse([$this->leg1, $this->leg2]);
        $this->addFakeJsonResponse([$this->leg3, $this->leg4]);

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $bcnToPly = Connection::query()->where([
            ['starting_station', '=', $this->bcn['id']],
            ['ending_station', '=', $this->ply['id']]
        ])->first();
        $plyToPno = Connection::query()->where([
            ['starting_station', '=', $this->ply['id']],
            ['ending_station', '=', $this->pno['id']],
        ])->first();
        $pnoToPly = Connection::query()->where([
            ['starting_station', '=', $this->pno['id']],
            ['ending_station', '=', $this->ply['id']],
        ])->first();
        $plyToBcn = Connection::query()->where([
            ['starting_station', '=', $this->ply['id']],
            ['ending_station', '=', $this->bcn['id']]
        ])->first();

        $parisLyon = Station::query()->where('station_id', '=', $this->ply['id'])->first();

        $this->assertNotEmpty($parisLyon);
        $this->assertEquals(360, $bcnToPly->duration);
        $this->assertEquals(90, $plyToPno->duration);
        $this->assertEquals(60, $pnoToPly->duration);
        $this->assertEquals(390, $plyToBcn->duration);
    }

    public function testCaptureCommandDifferentCountries()
    {
        $this->setUpStationsInDifferentCountries();
        $this->setUpConnections();

        $this->addFakeJsonResponse([$this->leg1, $this->leg2]);
        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $bcnToPly = Connection::query()->where([
            ['starting_station', '=', $this->bcn['id']],
            ['ending_station', '=', $this->ply['id']]
        ])->first();
        $plyToPno = Connection::query()->where([
            ['starting_station', '=', $this->ply['id']],
            ['ending_station', '=', $this->pno['id']],
        ])->first();
        $pnoToPly = Connection::query()->where([
            ['starting_station', '=', $this->pno['id']],
            ['ending_station', '=', $this->ply['id']],
        ])->first();
        $plyToBcn = Connection::query()->where([
            ['starting_station', '=', $this->ply['id']],
            ['ending_station', '=', $this->bcn['id']]
        ])->first();

        $parisLyon = Station::query()->where('station_id', '=', $this->ply['id'])->first();

        $this->assertNotEmpty($parisLyon);
        $this->assertEquals(360, $bcnToPly->duration);
        $this->assertEquals(90, $plyToPno->duration);
        $this->assertEmpty($pnoToPly);
        $this->assertEmpty($plyToBcn);
    }

    public function testCaptureCommandConnectionsAlreadyHaveDurations()
    {
        $this->setUpStationsInSameCountry();
        $this->setUpConnectionsWithDurations();

        $this->addFakeJsonResponse([$this->leg1, $this->leg2]);
        $this->addFakeJsonResponse([$this->leg3, $this->leg4]);

        $this->artisan('connections:capture ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $parisLyon = Station::query()->where('station_id', '=', $this->ply['id'])->first();

        $this->assertEmpty($parisLyon);
    }

    public function testCaptureCommandConnectionsFailedResponse()
    {
        $this->setUpStationsInSameCountry();
        $this->setUpConnections();

        $this->addErrorResponse();

        $this->artisan('connections:capture ES')
            ->expectsOutput('Failed')
            ->assertExitCode(0);

        $parisLyon = Station::query()->where('station_id', '=', $this->ply['id'])->first();

        $this->assertEmpty($parisLyon);
    }

    public function testCaptureCommandShouldNotCallApiIfDurationHasNotExpired()
    {
        $this->setUpStationsInSameCountry();
        $this->setUpConnections();
        $this->artisan('connections:capture ES --days=1')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $parisLyon = Station::query()->where('station_id', '=', $this->ply['id'])->first();

        $this->assertEmpty($parisLyon);
    }
}
