<?php

use App\Models\Station;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class TripsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barcelona = [
            'id' => Uuid::uuid4(),
            'name' => 'Barcelona-Sants',
            'lat' => 41.379520,
            'lng' => 2.140624
        ];
        $this->valencia = [
            'id' => Uuid::uuid4(),
            'name' => 'Valencia-Estacio del Nord',
            'lat' => 39.465064,
            'lng' => -0.377433
        ];

        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);
    }

    public function testCreateTrip()
    {
        $response = $this->post('/api/trip', ["trip" => array($this->barcelona, $this->valencia)]);
        $trip = Trip::query()->first();
        $response->assertStatus(200);
        $response->assertExactJson(["alias" => $trip->alias]);
    }
}