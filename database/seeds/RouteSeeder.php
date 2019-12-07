<?php

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
        $rows = array_map('str_getcsv', file('database/seeds/data/routes.csv'));

         foreach($rows as $row) {
            DB::table('routes')->insert([
                'route_id' => $row[0],
                'service_name' => $row[2]
            ]);
        }
    }
}
