<?php

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Concerns\FakeRequests;

class FindJourneyTest extends TestCase 
{
    use DatabaseMigrations;
    use FakeRequests;

    protected $barcelona;
    protected $valencia;

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
        $this->disabled = [
          'enabled' => false
        ];
    }

  public function testConsoleCommand()
  {
    $this->addFakeJsonResponse(['duration' => 60]);
    $this->addFakeJsonResponse(['duration' => 90]);

    factory(Station::class)->create($this->barcelona);
    factory(Station::class)->create($this->valencia);

    $this->artisan('journey:find ES')
          ->expectsOutput('Finished!')
          ->assertExitCode(0);
      
    $barcelonaToValencia = Connection::query()->where('starting_station', '=', $this->barcelona['station_id'])->first();
    $valenciaToBarcelona = Connection::query()->where('starting_station', '=', $this->valencia['station_id'])->first();

    $this->assertEquals(60, $barcelonaToValencia->duration);
    $this->assertEquals(90, $valenciaToBarcelona->duration);
  }

  //TODO: Test with only enabled stations, repeated calls etc.
}
