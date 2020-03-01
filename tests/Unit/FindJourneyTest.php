<?php

use App\Models\Connection;
use App\Models\Station;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FindJourneyTest extends TestCase 
{
    use DatabaseMigrations;

    protected $barcelona;
    protected $valencia;

    protected function setUp(): void
    {
        parent::setUp();

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
    $this->setUpMockClient();
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

  protected function setUpMockClient()
  {
    $headers = ['Content-Type' => 'application/json'];

    $container = [];
    $mock = new MockHandler([
      new Response(200, $headers, json_encode(['duration' => 60])),
      new Response(200, $headers, json_encode(['duration' => 90]))
    ]);
    
    $handler = HandlerStack::create($mock);
    $handler->push(Middleware::history($container));

    $this->app->instance(Client::class, new Client(['handler' => $handler]));
  }
}
