<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
/**
 * @property string $id
 * @property int $station_id
 * @property string $name
 * @property string $slug
 * @property string $lat
 * @property string $lng
 * @property int $enabled
 * @property string $country
 * @property boolean $important
 * @property int $captured_by
 * @property string $country
 * @property string $destination_id
 */
class Station extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $table = 'stations';
    protected $hidden = ['station_id', 'enabled', 'country', 'distance', 'important', 'captured_by', 'connected_countries', 'destination_id'];
    protected $fillable = ['station_id', 'name', 'slug', 'lat', 'lng', 'enabled', 'country', 'important', 'captured_by', 'destination_id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($station) {
            if (!$station->{$station->getKeyName()}) {
                $station->{$station->getKeyName()} = (string) Uuid::uuid4();
            }
        });
    }

    public function connections()
    {
        return $this->hasMany('App\Models\Connection', 'starting_station', 'station_id');
    }
}
