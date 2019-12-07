<?php

use Illuminate\Database\Seeder;

class JourneySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rows = array_map('str_getcsv', file('database/seeds/data/trips.csv'));

         foreach($rows as $row) {
            DB::table('journeys')->insert([
                'journey_id' => $row[2],
                'route_id' => $row[0],
                'journey_id_full' => $row[1]
            ]);
        }
    }
}
