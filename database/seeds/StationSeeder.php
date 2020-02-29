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
        $response = $googleDrive->getFile('1Mk1YngCGedaeZOGzuF6Yt-gUbpfdRhxe');
        $lines = explode("\n", $response);

         foreach($lines as $line) {
             if (strlen($line) > 0) {
                $row = str_getcsv($line, ";");
                DB::table('stations')->insert([
                    'id' => Uuid::uuid4(),
                    'station_id' => $row[1],
                    'name' => $row[0],
                    'lat' => round(floatval($row[2]),6),
                    'lng' => round(floatval($row[3]),6),
                    'country' => $row[4],
                    'enabled' => true,
                ]);
             }
        }
    }
}
