<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
/**
 * @property string $id
 * @property int $station_id
 * @property string $name
 * @property string $lat
 * @property string $lng
 * @property int $enabled
 * @property string $country
 */
class Station extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $table = 'stations';
    protected $hidden = ['station_id', 'enabled', 'country', 'distance'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($station) {
            if (!$station->{$station->getKeyName()}) {
                $station->{$station->getKeyName()} = (string) Uuid::uuid4();
            }
        });
    }

    public function stops()
    {
        return $this->hasMany('App\Models\Stop', 'station_id', 'station_id');
    }
}
