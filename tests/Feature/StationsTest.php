<?php

namespace Tests\Feature;

use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationsTest extends TestCase
{
    use RefreshDatabase;

    public function testAll()
    {
        factory(Station::class)->create([
            'name' => 'Barcelona-Sants',
        ]);
        $response = $this->get('/api/stations');
        $response->assertJsonFragment(["name" => "Barcelona-Sants"]);
        $response->assertStatus(200);
    }

    public function testNearest()
    {
        factory(Station::class)->create([
            'name' => 'Barcelona-Sants',
        ]);
        factory(Station::class)->create([
            'name' => 'Valencia-Estacio del Nord',
            'lat' => '39.465064',
            'lon' => '-0.377433'
        ]);
        $response = $this->post('/api/stations/nearest', ["lat" => "39.465", "lon" => "-0.377"]);
        $response->assertJsonFragment(["name" => "Valencia-Estacio del Nord"]);
        $response->assertStatus(200);
    }
}
