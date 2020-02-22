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
        $response = $googleDrive->getFile('1-t3daQ6ccsL-rGz4CBWSlGZRTSBq7LVs');
        $lines = explode("\n", $response);

        foreach($lines as $line) {
            if (strlen($line) > 0) {
                $row = str_getcsv($line);
                DB::table('journeys')->insert([
                    'journey_id' => $row[2],
                    'route_id' => $row[0]
                ]);
            }
        }
    }
}
