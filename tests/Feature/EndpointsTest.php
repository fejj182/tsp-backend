<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndpointsTest extends TestCase
{
    public function testStations()
    {
        $response = $this->get('/api/stations');
        $response->assertJsonFragment(["name" => "Barcelona-Sants"]);
        $response->assertStatus(200);
    }

    public function testStationsNearest()
    {
        $response = $this->post('/api/stations/nearest', ["lat" => "39.465064", "lon" => "-0.377433"]);
        $response->assertJsonFragment(["name" => "Valencia-Estacio del Nord"]);
        $response->assertStatus(200);
    }
}
