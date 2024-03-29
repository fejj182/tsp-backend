<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\Station;
use App\Services\CountryCodes;
use App\Http\MakesHttpRequests;
use Carbon\Carbon;
use DB;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use Log;

class ConnectionCapture extends Command
{
    use MakesHttpRequests;

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

    protected $host;

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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $connections = $this->getConnectionsToCapture();
            $connections->each(function ($connection) {

                $journey = $this->get("{$this->host}/journeys/{$connection->starting_station}/{$connection->ending_station}/capture");

                if (isset($journey->firstLeg) && isset($journey->secondLeg)) {
                    $captured = $this->captureJoiningStation($journey, $connection);
                    if ($captured->wasRecentlyCreated) {
                        Log::info($captured->name . " (" . $captured->station_id . ") captured from " . $connection->starting_station . "-" . $connection->ending_station);
                    } else {
                        $this->updateConnection($connection);
                        Log::info("Already captured: " . $captured->name . " (" . $captured->station_id . ") ");
                    }
                } else {
                    $this->updateConnection($connection, $journey);
                    Log::info("No capture: " . $connection->starting_station . "-" . $connection->ending_station);
                }

                usleep($this->option('sleep') * 1000 * 1000);
            });
            $this->info('Finished');
        } catch (Exception $e) {
            $this->info('Failed');
            $this->info($e->getMessage());
        }
    }

    protected function getConnectionsToCapture(): Collection
    {
        return Connection::query()
            ->select('connections.*')
            ->join('stations as s1', function ($join) {
                $join->on('connections.starting_station', '=', 's1.station_id')
                    ->where([
                        ['s1.important', '=', true],
                        ['s1.country', '=', $this->argument('country')]
                    ]);
            })
            ->join('stations as s2', function ($join2) {
                $join2->on('connections.ending_station', '=', 's2.station_id')
                    ->where('s2.important', '=', true);
            })
            ->where([
                ['duration', '=', 0],
                ['updated_at', '<=', Carbon::now()->subDays($this->option('days'))]
            ])
            ->get();
    }

    protected function captureJoiningStation($journey, $connection)
    {
        return DB::transaction(function () use ($journey, $connection) {
            $capture = $journey->firstLeg->destination;
            $country = CountryCodes::countryCodeLookup($capture->name);

            $stationName = ucwords(strtolower(trim(str_replace("(" . $country["name"] . ")", "", $capture->name))));

            $this->saveConnection($journey->firstLeg);
            $this->saveConnection($journey->secondLeg);

            return Station::firstOrCreate(
                ['station_id' => $capture->id],
                [
                    'name' => $stationName,
                    'slug' => '',
                    'country' => $country["code"],
                    'lat' => $capture->location->latitude,
                    'lng' => $capture->location->longitude,
                    'captured_by' => $connection->id
                ]
            );
        });
    }

    protected function saveConnection($leg)
    {
        $duration = Carbon::parse($leg->arrival)->diffInMinutes(Carbon::parse($leg->departure));
        return Connection::firstOrCreate(
            [
                'starting_station' => $leg->origin->id,
                'ending_station' => $leg->destination->id
            ],
            ['duration' => $duration]
        );
    }

    protected function updateConnection($connection, $journey = null)
    {
        if (isset($journey->firstLeg)) {
            $duration = Carbon::parse($journey->firstLeg->arrival)->diffInMinutes(Carbon::parse($journey->firstLeg->departure));
            $connection->duration = $duration;
            Log::info("Bonus capture: " . $connection->starting_station . "-" . $connection->ending_station);
        }
        $connection->updated_at = Carbon::now();
        $connection->save();
    }
}
