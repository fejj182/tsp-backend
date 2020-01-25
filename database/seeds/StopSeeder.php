<?php

use App\Services\GoogleDrive;
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
        $googleDrive = new GoogleDrive();
        $response = $googleDrive->getFile('1oJ3U_JcVugWadkAozN7bYVJu562qauqA');
        $lines = explode("\n", $response);

        foreach($lines as $line) {
            if (strlen($line) > 0) {
                $row = str_getcsv($line);
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
}
