<?php

namespace App\Http\Controllers;

use App\Flight;
use App\Jobs\LogToSlack;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    /**
     * Create a new PassengerController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt');
    }

    /**
     * Store a new passenger in storage.
     *
     * @param  Request  $request
     * @return null
     */
    public function store(Request $request)
    {
        // V1.1: check if user has already added seats previously (restrict total to 5)
        // V1.1: enhance/clarify validation error messages (e.g. "seats.0 format is invalid")
        // V1.1: discard seats 00A, 00B, i.e. /^00.*/
        $passenger = $request->validate([
            'flight.number' => [
                'required',
                'regex:/^[0-9A-Z]{2}[0-9]{1,4}$/'
            ],
            'flight.departure_date' => [
                'required',
                'date_format:Y-m-d H:i:s',
            ],

            // V1.1: add numerical maximum to regex (e.g. number < 88) => Boeing 747-8
            'seats' => 'required|array|max:5',
            'seats.*' => [
                'distinct',
                'regex:/^[0-8]{1}[0-9]{1}[A-K]{1}$/',
            ],

            'terms' => 'required|accepted'
        ]);

        // V1.1: when basket is closed, specify reason to user ("¡Ups! Demasiado tarde :(")
        $flight = Flight::firstWhere([
            ['number', $passenger['flight']['number']],
            ['departure_date', $passenger['flight']['departure_date']],
            ['ordered', false]
        ]);

        if ($flight === null) {
            return response()->json(['message' =>
                'No hemos encontrado tu vuelo. Por favor, empieza desde el principio'
            ], 400);
        }

        // Check for seats already owned by another passenger (i.e. duplicates). 
        $duplicates = $flight->passengers()->pluck('old_seats')->map(function($seats) use($passenger) {
            return array_intersect($passenger['seats'], $seats);
        })->flatten()->toArray();

        if ($duplicates !== []) {
            LogToSlack::dispatch(
                "*Duplicate seats* submitted\n".
                "• Flight number: `".$flight['number']."`\n".
                "• Departure: `".date('l j\, F \| H:i', strtotime($flight['departure_date']))."`".
                "• Seats: `".implode(', ', $passenger['seats'])."`\n".
                "• Duplicates: `".implode(', ', $duplicates)."`\n"
            );

            return response()->json(['message' =>
                'Estos asientos ya han sido cogidos: '.implode(', ', $duplicates).
                '. Por favor, usa los asientos asignados por tu aerolínea'
            ], 400);
        }

        $flight->passengers()->create([
            'user_id' => $request->get('user')->id,
            'old_seats' => $passenger['seats']
        ]);

        LogToSlack::dispatch(
            "*New passenger signed up!*\n".
            "• Flight number: `".$flight['number']."`\n".
            "• Departure: `".date('l j\, F \| H:i', strtotime($flight['departure_date']))."`".
            "• Seats: `".implode(', ', $passenger['seats'])."`\n"
        );

        return response()->json([ 'message' => 'OK' ]);
    }
}
