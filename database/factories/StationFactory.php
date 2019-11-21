<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Illuminate\Support\Str;
use App\Models\Station;
use Faker\Generator as Faker;

$factory->define(Station::class, function (Faker $faker) {
    return [
        'station_id' => Str::random(5),
        'name' => $faker->city,
        'lat' => $faker->latitude,
        'lon' => $faker->longitude,
        'enabled' => true,
    ];
});
