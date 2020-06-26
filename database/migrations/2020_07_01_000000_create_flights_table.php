<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->bigIncrements('id');
            // V1.1: determine if relationship with airports could be useful
            $table->string('number', 6);  // IATA identifiers
            $table->string('origin', 3);
            $table->string('destination', 3);
            // V1.1: refactorise codebase to only use UTC tz as single STD
            $table->dateTime('departure_date');
            $table->dateTime('departure_date_UTC');
            // V1.1: is this needed? prob. can determine with current time + departure_date(_UTC) comparison
            $table->boolean('ordered')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flights');
    }
}
