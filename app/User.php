<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the passengers for the user.
     *
     * @return Passenger
     */
    public function passengers()
    {
        return $this->hasMany('App\Passenger');
    }
}
