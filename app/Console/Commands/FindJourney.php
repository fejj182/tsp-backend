<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\Station;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class FindJourney extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journey:find {country}';

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
            $stations->each(function($startingStation) use ($stations) {
                $stations->each(function($endingStation) use ($startingStation) {
                    if ($startingStation != $endingStation) {
                        $this->createConnection($startingStation, $endingStation);
                    }
                });
            });
            $this->info('Finished');
        } catch (\Exception $e) {
            //TODO: where did it fail
            $this->info('Failed');
        }
    }

    protected function createConnection($startingStation, $endingStation)
    {
        $url = 'http://localhost:3000/journeys/' . $startingStation->station_id . '/' . $endingStation->station_id;

        $res = $this->client->request('GET', $url);

        $duration = json_decode($res->getBody())->duration;

        //TODO: check entry exists and if exists, update instead of create
        Connection::create([
            'starting_station' => $startingStation->station_id,
            'ending_station' => $endingStation->station_id,
            'duration' => $duration
        ]);
    }
}
