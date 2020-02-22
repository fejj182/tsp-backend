<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property string $journey_id
 * @property string $route_id
 */
class Journey extends Model
{
    public $timestamps = false;
    protected $table = 'journeys';
}
