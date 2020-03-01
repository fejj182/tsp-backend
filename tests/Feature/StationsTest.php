<?php

namespace Tests\Feature;

use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;

class StationsTest extends TestCase
{
    use RefreshDatabase;

    protected $barcelona;
    protected $valencia;
    protected $disabled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barcelona = [
            'id' => Uuid::uuid4(),
            'name' => 'Barcelona-Sants',
            'lat' => 41.379520,
            'lng' => 2.140624
        ];
        $this->cuenca = [
            'id' => Uuid::uuid4(),
            'name' => 'Cuenca',
            'lat' => 40.06734,
            'lng' => -2.136471,
            'enabled' => false
        ];
        $this->valencia = [
            'id' => Uuid::uuid4(),
            'name' => 'Valencia-Estacio del Nord',
            'lat' => 39.465064,
            'lng' => -0.377433
        ];
        $this->disabled = [
            'id' => Uuid::uuid4(),
            'name' => 'Glasgow',
            'lat' => 0,
            'lng' => 0,
            'enabled' => false
        ];
    }

    public function testEnabled()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);
        factory(Station::class)->create($this->disabled);

        $response = $this->get('/api/stations');

        $response->assertExactJson([$this->barcelona, $this->valencia]);
    }

    public function testNearest()
    {
        factory(Station::class)->create($this->barcelona);
        factory(Station::class)->create($this->valencia);
        factory(Station::class)->create($this->disabled);

        $response = $this->post('/api/stations/nearest', ["lat" => 41.379520, "lng" => 2.140624]);
        
        $response->assertExactJson($this->barcelona);
        $response->assertStatus(200);
    }
}
