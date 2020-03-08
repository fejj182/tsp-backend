<?php

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConnectionBuilderTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barcelona = [
            'name' => 'Barcelona-Sants',
            'station_id' => 123,
        ];
        $this->valencia = [
            'name' => 'Valencia-Estacio del Nord',
            'station_id' => 456,
        ];
        $this->madrid = [
            'name' => 'Valencia-Estacio del Nord',
            'station_id' => 789,
        ];
        $this->disabled = [
            'station_id' => 999,
            'enabled' => false,
        ];
    }

    public function testConnectionBuilderCommand()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);

        $this->artisan('connections:build')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

        $this->assertEquals(null, $barcelonaToValencia->duration);
        $this->assertEquals(null, $valenciaToBarcelona->duration);
    }

    public function testConnectionBuilderOnlyUsesEnabledStations()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);
        factory(Station::class)->create($this->disabled);

        $this->artisan('connections:build')
            ->expectsOutput('Finished')
            ->assertExitCode(0);

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $this->assertEquals(null, $barcelonaToValencia->duration);

        $this->assertEmpty(Connection::query()->where('starting_station', '=', $this->disabled['station_id'])->first());
    }

    public function testConnectionBuilderOnlyBuildsNewConnections()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);

        $this->artisan('connections:build');

        factory(Station::class)->create($this->madrid);

        $this->artisan('connections:build');

        $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
        $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();
        $madridToSomewhere = Connection::query()->where('starting_station', '=', $this->madrid['station_id'])->first();

        $this->assertEquals(null, $barcelonaToValencia->duration);
        $this->assertEquals(null, $valenciaToBarcelona->duration);
        $this->assertEquals(null, $madridToSomewhere->duration);
    }
}
