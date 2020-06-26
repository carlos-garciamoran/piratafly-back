<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Passenger;
use Faker\Generator as Faker;

$factory->define(Passenger::class, function (Faker $faker) {
    return [
        'flight_id' => function() {
            return factory('App\Flight')->create()->id;
        },
        'user_id' => function() {
            return factory('App\User')->create()->id;
        },
        'old_seats' => [
            $faker->regexify('[0-8]{1}[0-9]{1}[A-K]{1}'),
            $faker->regexify('[0-8]{1}[0-9]{1}[A-K]{1}'),
            $faker->regexify('[0-8]{1}[0-9]{1}[A-K]{1}'),
        ]
    ];
});
