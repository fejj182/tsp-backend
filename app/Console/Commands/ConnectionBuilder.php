<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Console\Command;
use Log;

class ConnectionBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:build {--country=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build combinations of stations as connections';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $stations = Station::where('important', '=', true)
            ->whereIn('country', $this->option('country'))
            ->get();

        $stations->each(function ($startingStation) use ($stations) {
            $stations->each(function ($endingStation) use ($startingStation) {
                if ($startingStation != $endingStation) {
                    $connection = Connection::firstOrCreate([
                        'starting_station' => $startingStation->station_id,
                        'ending_station' => $endingStation->station_id
                    ]);
                    if ($connection->wasRecentlyCreated) {
                        Log::info($connection->starting_station . "-" . $connection->ending_station . " update time: " . $connection->updated_at);
                    }
                }
            });
        });

        $this->info('Finished');
    }
}
