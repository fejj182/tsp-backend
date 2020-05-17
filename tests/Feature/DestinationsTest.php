<?php

namespace Tests\Feature;

use App\Models\Destination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testEnabled()
    {
        $station = factory(Destination::class)->create();
        $station2 = factory(Destination::class)->create();

        $response = $this->get('/api/destinations');

        $response->assertExactJson([$station->toArray(), $station2->toArray()]);
    }
}
