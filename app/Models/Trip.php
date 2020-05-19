<?php

namespace App\Models;

use App\Services\Aliases\AliasGenerator;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $alias
 */
class Trip extends Model
{
    public $timestamps = true;
    protected $table = 'trips';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($station) {
            if (!$station->alias) {
                $station->alias = (string) AliasGenerator::generate();
                //TODO: should retry if alias already exists
            }
        });
    }

    public function tripStops()
    {
        return $this->hasMany('App\Models\TripStop');
    }

    public function tripDestinations()
    {
        return $this->hasMany('App\Models\TripDestination');
    }
}
