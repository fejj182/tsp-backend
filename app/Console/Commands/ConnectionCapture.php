<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\Station;
use App\Helpers\CountryCodes;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Exception;
use Log;

class ConnectionCapture extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:capture {country} {--days=0} {--sleep=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Captures connecting city (and durations) where train changes between two stations';

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

        $stations = Station::query()->where([
            ['country', '=', $country],
            ['important', '=', true]
        ])->get();

        try {
            $stations->each(function ($station) {
                $days = $this->option('days');
                $connections = Connection::query()
                    ->where([
                        ['starting_station', '=', $station->station_id],
                        ['duration', '=', 0],
                        ['updated_at', '<=', Carbon::now()->subDays($days)]
                    ])
                    ->get();
                $connections->each(function ($connection) {
                    $endingStation = Station::query()->where([
                        ['station_id', '=', $connection->ending_station],
                        ['important', '=', true]
                    ])->first();
                    if (!empty($endingStation)) {
                        $this->updateConnection($connection);
                        usleep($this->option('sleep') * 1000 * 1000);
                    }
                });
            });
            $this->info('Finished');
        } catch (Exception $e) {
            $this->info('Failed');
        }
    }

    protected function updateConnection($connection)
    {
        $url = 'http://localhost:3000/journeys/' . $connection->starting_station . '/' . $connection->ending_station . "/capture";
        $res = $this->client->request('GET', $url);
        $legs = json_decode($res->getBody());

        if (count($legs) == 2) {
            $this->captureJoiningStation($legs[0]);
            $this->saveConnection($legs[0]);
            $this->saveConnection($legs[1]);
        } else {
            Log::info("No capture: " . $connection->starting_station . "-" . $connection->ending_station);
        }
    }

    protected function captureJoiningStation($firstLeg)
    {
        $capture = $firstLeg->destination;

        preg_match_all('/\([A-Za-z]+\)/', $capture->name, $matches);
        $country = str_replace(['(', ')'], '', end($matches[0]));
        $countryCode = CountryCodes::countryCodeLookup($country);

        Station::firstOrCreate([
            'name' => $capture->name,
            'station_id' => $capture->id,
            'country' => $countryCode,
            'lat' => $capture->location->latitude,
            'lng' => $capture->location->longitude,
            'captured' => true
        ]);
    }

    protected function saveConnection($leg)
    {
        $connection = Connection::firstOrCreate([
            'starting_station' => $leg->origin->id,
            'ending_station' => $leg->destination->id
        ]);

        $duration = Carbon::parse($leg->arrival)->diffInMinutes(Carbon::parse($leg->departure));
        $connection->duration = $duration;
        $connection->save();

        Log::info($connection->starting_station . "-" . $connection->ending_station . " update time: " . $connection->updated_at);
    }
}
