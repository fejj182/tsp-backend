<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\Station;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ConnectionFinder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:find {country} {--days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $country = $this->argument('country');

        $stations = Station::query()->where('country', '=', $country)->get();

        try {
            $stations->each(function($station) {
                $connections = Connection::query()
                ->where('starting_station', '=', $station->station_id)
                ->where(function ($query) {
                    $days = $this->option('days');
                    $query->where('updated_at', '<', Carbon::now()->subDays($days))
                          ->orWhereNull('duration');
                })
                ->get();
                $connections->each(function($connection) {
                    $this->updateConnection($connection);
                });
            });
            $this->info('Finished');
        } catch (Exception $e) {
            $this->info('Failed');
        }
    }

    protected function updateConnection($connection)
    {
        $url = 'http://localhost:3000/journeys/' . $connection->starting_station . '/' . $connection->ending_station;
        $res = $this->client->request('GET', $url);
        $duration = json_decode($res->getBody())->duration;

        $connection->duration = $duration;
        $connection->save();
    }
}
