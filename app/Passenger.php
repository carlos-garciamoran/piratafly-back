<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [ 'new_seats' ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'flight_id', 'user_id', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'old_seats' => 'array',
        'new_seats' => 'array',
    ];

    /**
     * Get the flight that owns the passenger.
     *
     * @return Flight
     */
    public function flight()
    {
        return $this->belongsTo('App\Flight');
    }
}
