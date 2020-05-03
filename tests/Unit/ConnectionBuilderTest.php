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

        $this->barcelona = [
            'name' => 'Barcelona-Sants',
            'station_id' => 123,
            'country' => 'ES',
            'connected_countries' => 'PT'
        ];
        $this->porto = [
            'name' => 'Porto',
            'station_id' => 456,
            'country' => 'PT',
            'connected_countries' => 'ES'
        ];
        $this->madrid = [
            'name' => 'Madrid',
            'station_id' => 789,
            'country' => 'ES'
        ];
        $this->berlin = [
            'name' => 'Berlin',
            'station_id' => 654,
            'country' => 'DE',
            'connected_countries' => 'FR'
        ];
        $this->disabled = [
            'station_id' => 999,
            'important' => false,
            'country' => 'ES'
        ];
    }

    public function testConnectionBuilderCommand()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->porto);

        $this->artisan('connections:build --country=ES --country=PT')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToPorto = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $portoToBarcelona = Connection::query()->where('starting_station', '=', $this->porto['station_id'])->first();

        $this->assertEquals(null, $barcelonaToPorto->duration);
        $this->assertEquals(null, $portoToBarcelona->duration);
    }

    public function testConnectionBuilderOnlyUsesImportantStations()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->porto);
        factory(Station::class)->create($this->disabled);

        $this->artisan('connections:build --country=ES --country=PT')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToPorto = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $this->assertEquals(null, $barcelonaToPorto->duration);

        $this->assertEmpty(Connection::query()->where('starting_station', '=', $this->disabled['station_id'])->first());
    }

    public function testConnectionBuilderOnlyUsesStationsWithCorrectCountry()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->porto);
        factory(Station::class)->create($this->berlin);

        $this->artisan('connections:build --country=ES --country=PT')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToPorto = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $this->assertEquals(null, $barcelonaToPorto->duration);

        $this->assertEmpty(Connection::query()->where('starting_station', '=', $this->berlin['station_id'])->first());
    }

    public function testConnectionBuilderOnlyBuildsNewConnections()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->porto);

        $this->artisan('connections:build --country=ES --country=PT');

        factory(Station::class)->create($this->madrid);

        $this->artisan('connections:build --country=ES --country=PT');

        $barcelonaToPorto = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $portoToBarcelona = Connection::query()->where('starting_station', '=', $this->porto['station_id'])->first();
        $madridToSomewhere = Connection::query()->where('starting_station', '=', $this->madrid['station_id'])->first();

        $this->assertEquals(null, $barcelonaToPorto->duration);
        $this->assertEquals(null, $portoToBarcelona->duration);
        $this->assertEquals(null, $madridToSomewhere->duration);
    }

    public function testConnectionBuilderCrossCountry()
    {
        $barcelona = factory(Station::class)->create($this->barcelona);
        $madrid = factory(Station::class)->create($this->madrid);
        $porto = factory(Station::class)->create($this->porto);

        $this->artisan('connections:build --country=ES --country=PT --xc');

        $barcelonaConnections = Connection::query()->where('starting_station', '=', $barcelona->station_id)->get();
        $this->assertEquals(1, count($barcelonaConnections));
        $this->assertEquals($porto->station_id, $barcelonaConnections[0]->ending_station);

        $portoConnections = Connection::query()->where('starting_station', '=', $porto->station_id)->get();
        $this->assertEquals(1, count($portoConnections));
        $this->assertEquals($barcelona->station_id, $portoConnections[0]->ending_station);

        $madridConnections = Connection::query()->where('starting_station', '=', $madrid->station_id)->get();
        $this->assertEmpty($madridConnections);
    }
}
