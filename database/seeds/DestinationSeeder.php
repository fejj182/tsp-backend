<?php

use App\Models\Station;
use App\Services\GoogleDrive;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $googleDrive = new GoogleDrive();
        $response = $googleDrive->getFile(env('DESTINATIONS_SEED'));
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            if (strlen($line) > 0) {
                $row = str_getcsv($line);
                $uuid = Uuid::uuid4();
                DB::table('destinations')->insert([
                    'id' => $uuid,
                    'name' => $row[0],
                    'slug' => $row[1],
                    'lat' => round(floatval($row[3]), 6),
                    'lng' => round(floatval($row[4]), 6),
                    'country' => $row[5]
                ]);
                $stationIdsCsv = $row[2];
                $stationIds = explode(',', $stationIdsCsv);
                foreach($stationIds as $id) {
                    Station::where('station_id', $id)
                    ->update(['destination_id' => $uuid]);
                }
            }
        }
    }
}
