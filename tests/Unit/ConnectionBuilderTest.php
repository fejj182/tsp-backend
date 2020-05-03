<?php

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConnectionBuilderTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    public function testConnectionBuilderCommandOneCountry()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES']);

        $this->artisan('connections:build --country=ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $firstToSecond = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->first();
        $secondToFirst = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->first();

        $this->assertEquals(null, $firstToSecond->duration);
        $this->assertEquals(null, $secondToFirst->duration);
    }

    public function testConnectionBuilderCommandTwoCountries()
    {
        $stationES = factory(Station::class)->create(['country' => 'ES']);
        $stationPT = factory(Station::class)->create(['country' => 'PT']);

        $this->artisan('connections:build --country=ES --country=PT')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $connectionEStoPT = Connection::query()->where('starting_station', '=', $stationES->station_id)->first();
        $connectionPTtoES = Connection::query()->where('starting_station', '=', $stationPT->station_id)->first();

        $this->assertEquals(null, $connectionEStoPT->duration);
        $this->assertEquals(null, $connectionPTtoES->duration);
    }

    public function testConnectionBuilderOnlyUsesImportantStations()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES']);
        $disabled = factory(Station::class)->create(['country' => 'ES', 'important' => false]);

        $this->artisan('connections:build --country=ES')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $firstToSecond = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->first();
        $secondToFirst = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->first();

        $this->assertEquals(null, $firstToSecond->duration);
        $this->assertEquals(null, $secondToFirst->duration);

        $this->assertEmpty(Connection::query()->where('starting_station', '=', $disabled->station_id)->first());
    }

    public function testConnectionBuilderDoesntRepeatBuild()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES']);

        $this->artisan('connections:build --country=ES');
        $this->artisan('connections:build --country=ES');

        $firstConnections = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $secondConnections = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->get();

        $this->assertEquals(1, count($firstConnections));
        $this->assertEquals(1, count($secondConnections));
    }

    public function testConnectionBuilderOnlyBuildsNewConnections()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES']);

        $this->artisan('connections:build --country=ES');

        $thirdStationES = factory(Station::class)->create(['country' => 'ES']);

        $this->artisan('connections:build --country=ES --country=PT');

        $firstConnections = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $secondConnections = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->get();
        $thirdConnections = Connection::query()->where('starting_station', '=', $thirdStationES->station_id)->get();

        $this->assertEquals(2, count($firstConnections));
        $this->assertEquals(2, count($secondConnections));
        $this->assertEquals(2, count($thirdConnections));
    }

    public function testConnectionBuilderOnlyUsesStationsWithCorrectCountry()
    {
        $stationES = factory(Station::class)->create(['country' => 'ES']);
        $stationPT = factory(Station::class)->create(['country' => 'PT']);
        $stationFR = factory(Station::class)->create(['country' => 'FR']);

        $this->artisan('connections:build --country=ES --country=PT')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $connectionEStoPT = Connection::query()->where('starting_station', '=', $stationES->station_id)->first();
        $connectionPTtoES = Connection::query()->where('starting_station', '=', $stationPT->station_id)->first();

        $this->assertEquals(null, $connectionEStoPT->duration);
        $this->assertEquals(null, $connectionPTtoES->duration);

        $this->assertEmpty(Connection::query()->where('starting_station', '=', $stationFR->station_id)->first());
    }

    public function testConnectionBuilderCrossCountry()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES']);
        $stationFR = factory(Station::class)->create(['country' => 'FR', 'connected_countries' => 'ES']);

        $this->artisan('connections:build --country=ES --country=FR --xc');

        $connectionsES = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $this->assertEquals(1, count($connectionsES));
        $this->assertEquals($stationFR->station_id, $connectionsES[0]->ending_station);

        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEquals($firstStationES->station_id, $connectionsFR[0]->ending_station);

        $madridConnections = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->get();
        $this->assertEmpty($madridConnections);
    }

    public function testConnectionBuilderCrossCountryOnlyCountryOptionsProvided()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES','connected_countries' => 'PT']);
        $stationFR = factory(Station::class)->create(['country' => 'FR', 'connected_countries' => 'ES']);

        $this->artisan('connections:build --country=ES --country=FR --xc');

        $connectionsES = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $this->assertEquals(1, count($connectionsES));
        $this->assertEquals($stationFR->station_id, $connectionsES[0]->ending_station);

        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEquals($firstStationES->station_id, $connectionsFR[0]->ending_station);

        $salamancaConnections = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->get();
        $this->assertEmpty($salamancaConnections);
    }
}
