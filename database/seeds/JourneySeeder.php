<?php

use App\Services\GoogleDrive;
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
        $googleDrive = new GoogleDrive();
        $response = $googleDrive->getFile('19_kk9Mc88OAUV32QoJ7Y9V1Or821j9rH');
        $lines = explode("\n", $response);

        foreach($lines as $line) {
            if (strlen($line) > 0) {
                $row = str_getcsv($line);
                DB::table('journeys')->insert([
                    'journey_id' => $row[1],
                    'route_id' => $row[0]
                ]);
            }
        }
    }
}
