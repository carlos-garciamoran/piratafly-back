<?php

use Illuminate\Database\Seeder;

class AirportsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $airports = [];

        if (($handle = fopen(storage_path('app/iata.tzmap'), 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                $airports[] = [
                    'code'     => $data[0],
                    'timezone' => $data[1]
                ];
            }
            fclose($handle);
        }

        DB::table('airports')->insert($airports);
    }
}
