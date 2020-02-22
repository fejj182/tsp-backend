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
        $response = $googleDrive->getFile('1m292u-o1Y-9HnOmpxaJkQjtuQO6dqsJo');
        $lines = explode("\n", $response);

         foreach($lines as $line) {
             if (strlen($line) > 0) {
                $row = str_getcsv($line);
                $enabled = false;
                $enabledStations = ['66100','78704','12100','75111','22308','04040','60911','71500','13200','22300','22100','21001','10600','54201','05474','70200','14203','06005','54413','10500','31412','05019','60000','51003','51402','65000','71801','18000'];
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
}
