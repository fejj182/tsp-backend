<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Illuminate\Support\Str;
use App\Models\Stop;
use Faker\Generator as Faker;

$factory->define(Stop::class, function (Faker $faker) {
    return [
        'station_id' => Str::random(5),
        'arrival_time' => $faker->time(),
        'departure_time' => $faker->time(),
        'stop_sequence' => $faker->randomNumber(3),
        'journey_id' => Str::random(10),
    ];
});
