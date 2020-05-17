<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Destination;
use Faker\Generator as Faker;

$factory->define(Destination::class, function (Faker $faker) {
    return [
        'name' => $faker->city,
        'slug' => $faker->slug,
        'lat' => $faker->latitude,
        'lng' => $faker->longitude,
        'country' => $faker->country,
    ];
});
