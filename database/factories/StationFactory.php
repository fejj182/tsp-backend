<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Station;
use Faker\Generator as Faker;

$factory->define(Station::class, function (Faker $faker) {
    return [
        'station_id' => rand(1,99999),
        'name' => $faker->city,
        'slug' => $faker->slug,
        'lat' => $faker->latitude,
        'lng' => $faker->longitude,
        'country' => $faker->country,
        'enabled' => true,
        'important' => true
    ];
});
