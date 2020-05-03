<?php

use App\Services\GoogleDrive;
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
        $googleDrive = new GoogleDrive();
        $response = $googleDrive->getFile(env('STATIONS_SEED'));
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            if (strlen($line) > 0) {
                $row = str_getcsv($line);
                DB::table('stations')->insert([
                    'id' => Uuid::uuid4(),
                    'station_id' => $row[2],
                    'name' => $row[0],
                    'slug' => $row[1],
                    'lat' => round(floatval($row[3]), 6),
                    'lng' => round(floatval($row[4]), 6),
                    'country' => $row[5],
                    'enabled' => false,
                    'important' => $row[6],
                    'connected_countries' => $row[7] != "" ? $row[7] : null 
                ]);
            }
        }
    }
}
