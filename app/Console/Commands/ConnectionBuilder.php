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
        $countries = $this->option('country');

        if (count($countries) == 1) {
            $this->regularBuilder($countries);
        } else if (count($countries) > 1) {
            $this->crossCountryBuilder($countries);
        }

        $this->info('Finished');
    }

    protected function regularBuilder(array $countries)
    {
        $stations = Station::where('important', true)
            ->whereIn('country', $countries)
            ->get();

        $stations->each(function ($startingStation) use ($stations) {
            $this->buildConnections($stations, $startingStation);
        });
    }

    protected function buildConnections($stations, $startingStation)
    {
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
    }

    protected function crossCountryBuilder(array $countries)
    {
        $stationsQuery = Station::where('important', true)
            ->whereIn('country', $countries);

        $firstCountry = array_shift($countries);

        $stations = $stationsQuery->where(function ($query) use ($countries, $firstCountry) {
            $query->where('connected_countries', 'LIKE', '%' . $firstCountry . '%');
            foreach ($countries as $country) {
                $query->orWhere('connected_countries', 'LIKE', '%' . $country . '%');
            }
        })
            ->get();

        $stations->each(function ($startingStation) use ($stations) {
            $endingStations = $stations->filter(function ($station) use ($startingStation) {
                return strpos($station->connected_countries, $startingStation->country) !== false;
            });

            $this->buildConnections($endingStations, $startingStation);
        });
    }
}
