<?php

namespace App\Console\Commands;

use App\Brain;
use App\Flight;
use App\Jobs\LogToSlack;
use App\Notifications\NewSeatsAssigned;
use App\Notifications\NoSeatsAssigned;
use App\Passenger;
use App\User;
use Illuminate\Console\Command;

class DispatchFlights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piratafly:dispatch_flights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Order the seats of upcoming flights and notify its passengers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get all not ordered flights that will depart in 1 hour or less.
        $flights = Flight::where([
            ['ordered', false],
            ['departure_date_UTC', '<=', now()->add(1, 'hours')]
        ])->get();

        if (count($flights) === 0) return;

        foreach ($flights as $flight) {
            $this->info($flight->id.' | '.$flight->number.' | '.$flight->departure_date_UTC.' | '.$flight->ordered);

            $passengers = Passenger::where('flight_id', $flight->id)
                // ID is needed to store the passenger object.
                ->get(['id', 'user_id', 'old_seats', 'created_at'])
                ->makeVisible('user_id')
                ->sortBy('created_at')
                ->values();

            $this->info($passengers);

            // V1.1: perf. improvement ==> set all flights as ordered outside loop
            $flight->ordered = true;
            $flight->save();

            // V1.1: check (passengers !== 0) on $flights query so first if can be removed
            if (count($passengers) === 0) continue;
            elseif (count($passengers) === 1) {
                User::find($passengers[0]->user_id)->notify(
                    new NoSeatsAssigned($flight)
                );

                LogToSlack::dispatch(
                    "Flight with *1 passenger dispatched* :(\n".
                    "• Number: `".$flight['number']."`\n".
                    "• Departure: `".date('l j\, F \| H:i', strtotime($flight['departure_date']))."`\n".
                    "• User ID: `".$passengers[0]->user_id."`"
                );

                continue;
            }

            $groups = [];

            foreach ($passengers as $passenger) {
                $seats = $passenger->old_seats;

                foreach ($seats as $seat) {
                    $groups[$passenger->user_id][] = $seat;
                }
            }

            // Damn. Look at that.
            $pirateo = new Brain($groups);

            $map = $pirateo->main();

            // V1.1: perf improvement needed AND possible!
            //   ==> instead of looping over $passengers twice
            //   ==> save new seats on-the-fly in Brain@assignSeatPairs
            foreach ($passengers as $passenger) {
                $new_seats = $map[$passenger->user_id];

                $passenger->new_seats = $new_seats;
                $passenger->save();
                $this->info($passenger);

                // V1.1: implement Redis queue
                User::find($passenger->user_id)->notify(
                    new NewSeatsAssigned($flight, $new_seats)
                );
            }

            LogToSlack::dispatch(
                "Flight with *".count($passengers)." passengers dispatched*!!\n".
                "• Number: `".$flight['number']."`\n".
                "• Route: `".$flight['origin']." — ".$flight['destination']."`\n".
                "• Departure: `".date('l j\, F \| H:i', strtotime($flight['departure_date']))."`\n"
            );
        }
    }
}
