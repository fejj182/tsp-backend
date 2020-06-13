<?php

use App\Models\Connection;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Concerns\FakeRequests;

class ConnectionFinderTest extends TestCase
{
    use DatabaseMigrations;
    use FakeRequests;

    protected $barcelona;
    protected $valencia;
    protected $barcelonaToValencia;
    protected $valenciaToBarcelona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClient();
        Log::spy();

        $this->barcelona = [
            'name' => 'Barcelona-Sants',
            'station_id' => 123,
            'country' => 'ES'
        ];
        $this->valencia = [
            'name' => 'Valencia-Estacio del Nord',
            'station_id' => 456,
            'country' => 'ES'
        ];
        $this->barcelonaToValencia = [
            'starting_station' => 123,
            'ending_station' => 456,
            'duration' => null
        ];
        $this->valenciaToBarcelona = [
            'starting_station' => 456,
            'ending_station' => 123,
            'duration' => null
        ];
    }

    private function createStationsAndConnections()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);

        factory(Connection::class)->create($this->barcelonaToValencia);
        factory(Connection::class)->create($this->valenciaToBarcelona);
    }

    public function testFinderCommand()
    {
        $this->createStationsAndConnections();        

        $this->addFakeJsonResponse(['duration' => 60]);
        $this->addFakeJsonResponse(['duration' => 90]);

        $this->artisan('connections:find --country=ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertEquals(60, $barcelonaToValencia->duration);
        $this->assertEquals(90, $valenciaToBarcelona->duration);

        $this->assertGuzzleCalledTimes(2);
        $this->assertGuzzleCalledWithUrl("mockHost/journeys/123/456/duration");
        $this->assertGuzzleCalledWithUrl("mockHost/journeys/456/123/duration");
    }

    public function testFinderCommand_shouldUpdateTimestampIfDurationTheSame()
    {
        $yesterday = Carbon::now()->subDay();
        $this->barcelonaToValencia = [
            'starting_station' => 123,
            'ending_station' => 456,
            'duration' => 120,
            'updated_at' => $yesterday
        ];

        $this->createStationsAndConnections();        

        $this->addFakeJsonResponse(['duration' => 120]);
        $this->addFakeJsonResponse(['duration' => 0]);

        $this->artisan('connections:find --country=ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();

        $this->assertEquals(120, $barcelonaToValencia->duration);
        $this->assertTrue($barcelonaToValencia->updated_at > $yesterday);

        $this->assertGuzzleCalledTimes(2);
        $this->assertGuzzleCalledWithUrl("mockHost/journeys/123/456/duration");
    }

    public function testFinderCommandShouldFailedResponse()
    {
        $this->createStationsAndConnections();

        $this->addErrorResponse();
        $this->addFakeJsonResponse(['duration' => 90]);

        $this->artisan('connections:find --country=ES')
            ->expectsOutput('Failed')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertNull($barcelonaToValencia->duration);
        $this->assertNull($valenciaToBarcelona->duration);
        
        $this->assertGuzzleCalledTimes(1);
        $this->assertGuzzleCalledWithUrl("mockHost/journeys/123/456/duration");
    }

    public function testFinderCommandShouldNotCallApiIfDurationHasNotExpired()
    {
        $this->barcelonaToValencia['duration'] = 0;
        $this->valenciaToBarcelona['duration'] = 0;

        $this->createStationsAndConnections();

        $this->artisan('connections:find --country=ES --days=1')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertEquals(0, $barcelonaToValencia->duration);
        $this->assertEquals(0, $valenciaToBarcelona->duration);

        $this->assertGuzzleNotCalled();
    }

    public function testFinderCommandShouldWorkBetweenCountries()
    {
        $this->paris = [
            'name' => 'Paris',
            'station_id' => 789,
            'country' => 'FR'
        ];

        $this->barcelonaToParis = [
            'starting_station' => 123,
            'ending_station' => 789,
            'duration' => null
        ];

        $this->parisToBarcelona = [
            'starting_station' => 789,
            'ending_station' => 123,
            'duration' => null
        ];

        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->paris);
        factory(Connection::class)->create($this->barcelonaToParis);
        factory(Connection::class)->create($this->parisToBarcelona);

        $this->addFakeJsonResponse(['duration' => 60]);
        $this->addFakeJsonResponse(['duration' => 90]);

        $this->artisan('connections:find --country=ES --country=FR')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToParis = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $parisToBarcelona = Connection::query()->where('starting_station', '=', $this->paris['station_id'])->first();

        $this->assertEquals(60, $barcelonaToParis->duration);
        $this->assertEquals(90, $parisToBarcelona->duration);
    }
    
    // TODO: Test logs
}
