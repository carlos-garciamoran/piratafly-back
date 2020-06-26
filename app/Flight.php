<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'departure_date_UTC', 'ordered', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'departure_date' => 'datetime',
        'departure_date_UTC' => 'datetime',
        'ordered' => 'boolean',
    ];

    /**
     * Get the passengers for the flight.
     *
     * @return Passenger
     */
    public function passengers()
    {
        return $this->hasMany('App\Passenger');
    }
}
