<?php

namespace App\Console\Commands;

use App\Http\MakesHttpRequests;
use App\Models\Connection;
use App\Models\Station;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Log;

class ConnectionFinder extends Command
{
    use MakesHttpRequests;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:find {--country=*} {--days=0} {--sleep=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds and saves journey time between two stations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        parent::__construct();
        $this->client = $client;
        $this->host = env('CONNECTION_COMMAND_HOST');

        $this->outOfDate = function ($query) {
            $days = $this->option('days');
            $query->where('updated_at', '<=', Carbon::now()->subDays($days))
                ->orWhereNull('duration');
        };
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $countries = $this->option('country');

        $stations = Station::query()->whereIn('country', $countries)->get();

        try {
            $stations->each(function ($station) {
                $connections = Connection::query()
                    ->where('starting_station', '=', $station->station_id)
                    ->where($this->outOfDate)
                    ->get();
                $connections->each(function ($connection) {
                    $this->updateConnection($connection);
                    usleep($this->option('sleep') * 1000 * 1000);
                });
            });
            $this->info('Finished');
        } catch (Exception $e) {
            $this->info('Failed');
            $this->info($e->getMessage());
        }
    }

    protected function updateConnection($connection)
    {
        $result = $this->get("{$this->host}/journeys/{$connection->starting_station}/{$connection->ending_station}/duration");

        $duration = $result->duration;
        if ($connection->duration != $duration) {
            $connection->duration = $duration;
            $connection->save();
        } else {
            $connection->touch();
        }

        if ($duration > 0) {
            Log::info($connection->starting_station . "-" . $connection->ending_station . " update time: " . $connection->updated_at);
        } else {
            Log::info("Connection not found: " . $connection->starting_station . "-" . $connection->ending_station);
        }
    }
}
