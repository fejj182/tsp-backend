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

        $this->artisan('connections:build --country=ES');

        $firstConnections = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $secondConnections = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->get();
        $thirdConnections = Connection::query()->where('starting_station', '=', $thirdStationES->station_id)->get();

        $this->assertEquals(2, count($firstConnections));
        $this->assertEquals(2, count($secondConnections));
        $this->assertEquals(2, count($thirdConnections));
    }

    public function testConnectionBuilderCrossCountry()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES']);
        $stationFR = factory(Station::class)->create(['country' => 'FR', 'connected_countries' => 'ES']);

        $this->artisan('connections:build --country=ES --country=FR');

        $firstConnectionsES = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $this->assertEquals(1, count($firstConnectionsES));
        $this->assertEquals($stationFR->station_id, $firstConnectionsES[0]->ending_station);

        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEquals($firstStationES->station_id, $connectionsFR[0]->ending_station);

        $secondConnectionsES = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->get();
        $this->assertEmpty($secondConnectionsES);
    }

    public function testConnectionBuilderStationWithMultipleConnectedCountries()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR,PT']);
        $stationFR = factory(Station::class)->create(['country' => 'FR', 'connected_countries' => 'ES']);

        $this->artisan('connections:build --country=ES --country=FR');

        $connectionsES = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $this->assertEquals(1, count($connectionsES));
        $this->assertEquals($stationFR->station_id, $connectionsES[0]->ending_station);

        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEquals($firstStationES->station_id, $connectionsFR[0]->ending_station);
    }

    public function testConnectionBuilderStationWithMultipleConnectedCountriesReverseOrder()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR,PT']);
        $stationFR = factory(Station::class)->create(['country' => 'FR', 'connected_countries' => 'ES']);

        $this->artisan('connections:build --country=FR --country=ES');

        $connectionsES = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $this->assertEquals(1, count($connectionsES));
        $this->assertEquals($stationFR->station_id, $connectionsES[0]->ending_station);

        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEquals($firstStationES->station_id, $connectionsFR[0]->ending_station);
    }

    public function testConnectionBuilderCrossCountryOnlyCountryOptionsProvided()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR']);
        $secondStationES = factory(Station::class)->create(['country' => 'ES','connected_countries' => 'PT']);
        $stationFR = factory(Station::class)->create(['country' => 'FR', 'connected_countries' => 'ES']);

        $this->artisan('connections:build --country=ES --country=FR');

        $firstConnectionsES = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $this->assertEquals(1, count($firstConnectionsES));
        $this->assertEquals($stationFR->station_id, $firstConnectionsES[0]->ending_station);

        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEquals($firstStationES->station_id, $connectionsFR[0]->ending_station);

        $secondConnectionsES = Connection::query()->where('starting_station', '=', $secondStationES->station_id)->get();
        $this->assertEmpty($secondConnectionsES);
    }

    public function testConnectionBuilderOnlyBuildsCrossCountryConnectionsForIncludedCountry()
    {
        $firstStationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR']);
        $stationFR = factory(Station::class)->create(['country' => 'FR', 'connected_countries' => 'ES']);
        $stationDE = factory(Station::class)->create(['country' => 'DE', 'connected_countries' => 'FR']);

        $this->artisan('connections:build --country=ES --country=FR');

        $connectionsES = Connection::query()->where('starting_station', '=', $firstStationES->station_id)->get();
        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $connectionsDE = Connection::query()->where('starting_station', '=', $stationDE->station_id)->get();

        $this->assertEquals(1, count($connectionsES));
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEmpty(count($connectionsDE));
    }

    public function testConnectionBuilderCrossCountryOnlyBuildCountriesDirectlyConnected()
    {
        $stationES = factory(Station::class)->create(['country' => 'ES', 'connected_countries' => 'FR']);
        $stationFR = factory(Station::class)->create(['country' => 'FR','connected_countries' => 'PT']);
        $stationPT = factory(Station::class)->create(['country' => 'PT', 'connected_countries' => 'ES']);

        $this->artisan('connections:build --country=ES --country=FR --country=PT');

        $connectionsES = Connection::query()->where('starting_station', '=', $stationES->station_id)->get();
        $connectionsFR = Connection::query()->where('starting_station', '=', $stationFR->station_id)->get();
        $connectionsPT = Connection::query()->where('starting_station', '=', $stationPT->station_id)->get();

        $this->assertEquals(1, count($connectionsES));
        $this->assertEquals(1, count($connectionsFR));
        $this->assertEquals(1, count($connectionsPT));
        $this->assertEquals($stationPT->station_id, $connectionsES[0]->ending_station);
        $this->assertEquals($stationES->station_id, $connectionsFR[0]->ending_station);
        $this->assertEquals($stationFR->station_id, $connectionsPT[0]->ending_station);
    }
}
