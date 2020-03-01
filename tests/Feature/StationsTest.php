<?php

namespace Tests\Feature;

use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;

class StationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testEnabled()
    {
        $station = factory(Station::class)->create();
        $anotherStation = factory(Station::class)->create();
        factory(Station::class)->create(['enabled' => false]);

        $response = $this->get('/api/stations');

        $response->assertExactJson([$station->toArray(), $anotherStation->toArray()]);
    }

    public function testNearest()
    {
        $close = ["lat" => 1, "lng" => 1.5];
        $far = ["lat" => 10, "lng" => 10];
        $closeDisabled = ["lat" => 1, "lng" => 1, 'enabled' => false];

        $closeStation = factory(Station::class)->create($close);
        factory(Station::class)->create($far);
        factory(Station::class)->create($closeDisabled);

        $response = $this->post('/api/stations/nearest', ["lat" => 1, "lng" => 1]);
        
        $response->assertExactJson($closeStation->toArray());
        $response->assertStatus(200);
    }
}
