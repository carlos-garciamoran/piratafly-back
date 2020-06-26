<?php

namespace App;

class Brain
{
    protected $rear    = 0;  // First non-consecutive seat of the basket (even)
    protected $n_seats = 0;  // Total number of seats (got it from the basket)

    protected $groups      = [];  // Input seat groups
    protected $group_map   = [];  // Each key value is the group's number of seats
    protected $seat_basket = [];  // Keeps track of the sorting

    // THINK: do we really need this variable? Could/Should we instead overwrite
    //        $groups during the sorting?
    protected $final_groups = [];  // The name speaks by itself

    /**
     * Create a new SeatController instance.
     *
     * @param array $data
     * @return void
     */
    public function __construct($data)
    {
        $this->groups = $data;
    }

    /**
     * [_/ $ init 3; \_]
     *
     * @return array [2D]
     */
    public function main()
    {
        $this->parseGroups();

        $this->sortSeatsByPairs();

        $this->assignSeatPairs();

        $this->assignIndividualSeats();

        return $this->final_groups;
    }

    /**
     * Generate the arrays final_groups, group_map and seat_basket.
     *
     * @return null
     */
    protected function parseGroups()
    {
        foreach ($this->groups as $group => $seats) {
            $this->group_map[$group]    = count($seats);
            $this->final_groups[$group] = [];

            foreach ($seats as $seat) { $this->seat_basket[] = $seat; }
        }

        $this->n_seats = count($this->seat_basket);
    }

    /**
     * Put consecutive seats of 2 (a pair) in top of the seat basket.
     *
     * @return null
     */
    protected function sortSeatsByPairs()
    {
        $top = 0;  // Last seat pair (index) at the top of the list

        sort($this->seat_basket);  // Sort the seats alphabetically

        for ($i=0; $i < $this->n_seats - 1; $i++) { 
            $seat      = $this->seat_basket[$i];
            $next_seat = $this->seat_basket[$i + 1];

            $is_same_row = 
                (strcmp(substr($seat, 0, 2), substr($next_seat, 0, 2)) === 0)
                    ? true : false;
            $is_next_to =
                (ord(substr($seat, -1)) - ord(substr($next_seat, -1)) === -1)
                    ? true : false;

            // WARNING: any change inside this block may easily FUCK UP the
            //          whole sorting, trust me.
            if ($is_same_row && $is_next_to) {
                // If the current pair of seats are at the top of the list
                // there's no need to order them.
                if ($top === $i) {
                    $i++;
                    $top += 2;
                    $this->rear += 2;
                    continue;
                }

                $single = $this->seat_basket[$this->rear];

                $this->seat_basket[$i + 1]      = $single;
                $this->seat_basket[$this->rear] = $next_seat;

                if ($i - $this->rear !== 1) {
                    $single = $this->seat_basket[$this->rear + 1];

                    $this->seat_basket[$i]              = $single;
                    $this->seat_basket[$this->rear + 1] = $seat;                
                }

                $this->rear += 2;
            }
        }
    }

    /**
     * Add 1 seat pair to each group sequentially.
     *
     * @return null
     */
    protected function assignSeatPairs()
    {
        // WARNING: if we haven't sorted by pairs previously this FUCKS UP
        $g_keys = array_keys($this->group_map);
        $g_index = 0;
        $n_groups = count($this->groups);  // Total number of groups

        for ($i=0; $i < $this->rear; $i++) {
            $res_number = $g_keys[$g_index];

            $current_size = count($this->final_groups[$res_number]);
            $real_size = $this->group_map[$res_number];

            $needs_more = (($current_size + 2) <= $real_size);

            if ($needs_more) {  // Add a pair of seats
                $this->final_groups[$res_number][$current_size] = $this->seat_basket[$i];
                $this->final_groups[$res_number][$current_size + 1] = $this->seat_basket[$i + 1];

                $g_index = ($g_index === $n_groups - 1)
                    ? 0              // if reached last group: start again
                    : $g_index + 1;  // else: check next group

                $i += 1;
                continue;
            }

            // There is no space so check the next group
            $g_index += 1;

            // Keep current $i for not losing the seat index
            $i -= 1;
        }
    }

    /**
     * Add the missing individual seats to the corresponding groups.
     *
     * @return null
     */
    protected function assignIndividualSeats()
    {
        $c = 0;  // Counter for keeping track of the last seat.

        $individual_seats = array_slice($this->seat_basket, $this->rear, $this->n_seats);
        sort($individual_seats);

        foreach ($this->groups as $group => $seats) {
            $last_seat = $c;

            $real_size = $this->group_map[$group];
            $current_size = count($this->final_groups[$group]);

            $seats_needed = $real_size - $current_size;

            if ($seats_needed > 0) {  // Add as many seats as needed
                for ($j=$last_seat; $j < $seats_needed + $last_seat; $j++) { 
                    $this->final_groups[$group][] = $individual_seats[$j];
                    $c += 1;
                }
            }
        }
    }
}
