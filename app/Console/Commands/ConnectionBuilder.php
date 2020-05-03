<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\Station;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Log;

class ConnectionBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:build {--country=*} {--xc}';

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
        $isCrossCountry = $this->option('xc');
        $countries = $this->option('country');

        $stations = $this->getStartingStationsToBuild($countries, $isCrossCountry);

        $stations->each(function ($startingStation) use ($stations, $isCrossCountry) {

            if ($isCrossCountry) {
                $endingStations = $stations->filter(function($station) use ($startingStation) {
                    return strpos($station->connected_countries, $startingStation->country) !== false;
                });
            } else {
                $endingStations = $stations;
            }

            $endingStations->each(function ($endingStation) use ($startingStation) {
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

    protected function getStartingStationsToBuild(array $countries, bool $isCrossCountry): Collection
    {
        if ($isCrossCountry) {
            $stationsQuery = Station::where('important', true)
                ->whereIn('country', $countries);

            $firstCountry = array_shift($countries);

            $stationsQuery->where(function ($query) use ($countries, $firstCountry) {
                $query->where('connected_countries', $firstCountry);
                foreach ($countries as $country) {
                    $query->orWhere('connected_countries', 'LIKE', '%' . $country . '%');
                }
            });
        } else {
            $stationsQuery = Station::where('important', true)
                ->whereIn('country', $countries);
        }

        return $stationsQuery->get();
    }
}
