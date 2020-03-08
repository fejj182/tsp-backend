<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Connection;

$factory->define(Connection::class, function () {
    return [
        'starting_station' => rand(1,99999),
        'ending_station' => rand(1,99999),
        'duration' => null
    ];
});
