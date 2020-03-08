<?php

use App\Models\Connection;
use App\Models\Station;
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
        ];
        $this->valenciaToBarcelona = [
            'starting_station' => 456,
            'ending_station' => 123,
        ];

        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);
        factory(Connection::class)->create($this->barcelonaToValencia);
        factory(Connection::class)->create($this->valenciaToBarcelona);
    }

    public function testConnectionFinderConsoleCommand()
    {
        $this->addFakeJsonResponse(['duration' => 60]);
        $this->addFakeJsonResponse(['duration' => 90]);

        $this->artisan('connections:find ES --days=1')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertEquals(60, $barcelonaToValencia->duration);
        $this->assertEquals(90, $valenciaToBarcelona->duration);
    }

    public function testShouldFailIfDoesNotReturn200()
    {
        $this->addErrorResponse();
        $this->addFakeJsonResponse(['duration' => 90]);

        $this->artisan('connections:find ES --days=1')
            ->expectsOutput('Failed')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertNull($barcelonaToValencia->duration);
        $this->assertNull($valenciaToBarcelona->duration);
    }

    public function testShouldCallApiOnlyIfDurationIsOld()
    {
        $this->addFakeJsonResponse(['duration' => 60]);
        $this->addFakeJsonResponse(['duration' => 90]);

        $this->artisan('connections:find ES --days=1')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $this->addFakeJsonResponse(['duration' => 120]);
        $this->addFakeJsonResponse(['duration' => 150]);

        $this->artisan('connections:find ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertEquals(60, $barcelonaToValencia->duration);
        $this->assertEquals(90, $valenciaToBarcelona->duration);
    }

    public function testShouldNotBreakIfNoDurationReturned()
    {
        $this->addFakeJsonResponse([]);
        $this->addFakeJsonResponse(['duration' => 90]);

        $this->artisan('connections:find ES --days=1')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertSame(0, $barcelonaToValencia->duration);
        $this->assertEquals(90, $valenciaToBarcelona->duration);
    }
}
