<?php

use App\Services\GoogleDrive;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $googleDrive = new GoogleDrive();
        $response = $googleDrive->getFile('1_V5ejhcH6FECAHrqs8DWRlQEx7goVyUP');
        $lines = explode("\n", $response);

        foreach($lines as $line) {
            if (strlen($line) > 0) {
                $row = str_getcsv($line);
                DB::table('routes')->insert([
                    'route_id' => $row[0],
                    'service_name' => $row[2]
                ]);
            }
        }
    }
}
