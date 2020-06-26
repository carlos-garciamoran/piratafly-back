<?php

namespace App\Http\Controllers;

use App\Airport;
use App\Flight;
use App\Jobs\LogToSlack;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Dom;

class FlightController extends Controller
{
    /**
     * Create a new FlightController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt');
        // V1.1: customise & translate throttling message
        $this->middleware('throttle:3,1')->only('search');
    }

    /**
     * Get the flights from the user.
     *
     * @param Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $user = $request->get('user');

        // V1.1: redesign model hierarchy -> improve semantics
        $flights = $user->passengers()->with('flight')->get();

        return response()->json(['flights' => $flights]);
    }

    /**
     * Return existing flights.
     *
     * @param  Request  $request
     * @return Response
     */
    public function search(Request $request)
    {
        // V1.1: enhance error messages
        $flightNumber = $request->validate([
            'checked_in' => 'required|accepted',
            'number'     => [
                'required',
                'regex:/^[0-9A-Z]{2}[0-9]{1,4}$/' // IATA identifier
            ],
        ])['number'];

        // Remove trailing zeroes on flight designator (after airline identifier)
        $flightNumber = substr($flightNumber, 0, 2) . (int) substr($flightNumber, 2);

        // Retrieve all future flights departing in more than 1 hour (open basket)
        $currentFlights = Flight::where([
            ['number', $flightNumber],
            ['departure_date_UTC', '>', now()->add(1, 'hours')],
        ])->get();

        // V1.1: check if user is already signed-up to flight
        //       for adding more seats user should edit their seat basket instead
        //       of signing up for a new flight
        if ($currentFlights->count() > 1) {
            // V2.0: return number of passengers (+ seats) signed up for each flight
            return response()->json([ 'flights' => $currentFlights ]);
        }

        $flights = $this->fetchFlights($flightNumber);

        // Either the flight number is invalid or only past flights were found.
        if ($flights === 1 || $flights === []) {
            return response()->json([
                'message' => 'No hemos encontrado vuelos con ese número'
            ], 400);
        } elseif ($flights === 2) {
            return response()->json([
                'message' => 'No hemos podido validar tu vuelo, por favor inténtalo más tarde'
            ], 503);
        }

        Flight::insert($flights);

        // V1.1: find a cleaner way to hide the specified attributes.
        $currentFlights = array_map(function($f) {
            unset($f['created_at'], $f['updated_at'], $f['departure_date_UTC']);
            return $f;
        }, $flights);

        LogToSlack::dispatch(
            "*".count($flights)." new flight(s)* created\n".
            "• Number: `".$flightNumber."`\n".
            "• Route: `".$flights[0]['origin']." — ".$flights[0]['destination']."`\n".
            "• Departure: `".date('l j\, F \| H:i', strtotime($flights[0]['departure_date']))."`\n"
        );

        return response()->json([ 'flights' => $currentFlights ]);
    }

    /**
     * Query the FlightRadar24 API to retrieve flight data.
     *
     * @param  string  $flightNumber
     * @return array|int  If successful: return flight data; else: return err code
     */
    private function fetchFlights($flightNumber)
    {
        // V1.1: check departure_date > +1 hour (&& departure_date < +48 hours ?)
        // V1.1: do not consider nor add cancelled flights
        try {
            $flights = [];
            $dom = new Dom;
            $dom->load('https://www.flightradar24.com/data/flights/'.strtolower($flightNumber));

            if (strpos($dom->outerHtml, 'no data available')) {
                LogToSlack::dispatch(
                    "*Invalid* flight number submitted: *".$flightNumber."*\n"
                    // "User email: ".$user->email."\n".
                );
                return 1;
            }

            $rawFlights = $dom->find('tbody')->find('tr');

            foreach ($rawFlights as $flight) {
                $flightData = $flight->find('td');

                $departure_date_UTC = $flightData[7]->getAttribute('data-timestamp');

                // If flight is past, ignore.
                if ($departure_date_UTC < time()) continue;

                // Convert UNIX timestamp to human-readable format.
                $departure_date_UTC = date('Y-m-d H:i:s', $departure_date_UTC);

                $origin = trim(substr(
                    $flightData[3]->find('a')->text, 1, -1
                ));
                $destination = trim(substr(
                    $flightData[4]->find('a')->text, 1, -2
                ));

                $tz = Airport::find($origin)->timezone;
                $departure_date = (string) Carbon::createFromFormat(
                    'Y-m-d H:i:s', $departure_date_UTC
                )->setTimezone($tz);

                $now = date('Y-m-d H:i:s');

                // V1.1: refactorise codebase to only use UTC tz as single STD
                $flights[] = [
                    'number' => $flightNumber,
                    'departure_date'     => $departure_date,
                    'departure_date_UTC' => $departure_date_UTC,
                    'origin'      => $origin,
                    'destination' => $destination,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            return $flights;
        } catch (Exception $e) {
            dispatch(function() use ($e) {
                Log::channel('slack')->error('Exception fired', ['debug' => $e]);
            });

            return 2;
        }
    }
}
