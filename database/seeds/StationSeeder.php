<?php

use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rows = array_map('str_getcsv', file('database/seeds/data/stops.csv'));

         foreach($rows as $row) {
            $enabled = false;
            $enabledStations = ['71801', '65000', '18000'];
            if (in_array($row[0], $enabledStations)){
                $enabled = true;
            }
            DB::table('stations')->insert([
                'id' => Uuid::uuid4(),
                'station_id' => $row[0],
                'name' => $row[2],
                'lat' => $row[4],
                'lng' => $row[5],
                'enabled' => $enabled,
            ]);
        }
    }
}
