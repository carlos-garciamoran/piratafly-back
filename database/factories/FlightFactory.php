<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Flight;
use Faker\Generator as Faker;

$factory->define(Flight::class, function (Faker $faker) {
    $date = $faker->dateTimeBetween($startDate = 'yesterday', $endDate = '+3 days');

    return [
        // 'number' => $faker->regexify('[0-9A-Z]{2}[0-9]{1,4}'),
        'number' => strtoupper($faker->bothify('??###')),
        'origin' => strtoupper($faker->lexify('???')),
        'destination' => strtoupper($faker->lexify('???')),
        'departure_date' => $date,
        'departure_date_UTC' => $date,
    ];
});
