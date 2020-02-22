<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property int $journey_id
 * @property int $route_id
 */
class Journey extends Model
{
    public $timestamps = false;
    protected $table = 'journeys';
}
