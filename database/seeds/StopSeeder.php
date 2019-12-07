<?php

use Illuminate\Database\Seeder;

class StopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rows = array_map('str_getcsv', file('database/seeds/data/stop_times.csv'));

         foreach($rows as $row) {
            DB::table('stops')->insert([
                'station_id' => $row[3],
                'arrival_time' => $row[1],
                'departure_time' => $row[2],
                'stop_sequence' => $row[4],
                'journey_id' => $row[0]
            ]);
        }
    }
}
