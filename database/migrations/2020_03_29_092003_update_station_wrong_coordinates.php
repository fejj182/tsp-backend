<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStationWrongCoordinates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('UPDATE stations SET lat=48.5607 WHERE name="St-Denis-Voyageurs"');
        DB::statement('UPDATE stations SET lng=2.2044 WHERE name="St-Denis-Voyageurs"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
