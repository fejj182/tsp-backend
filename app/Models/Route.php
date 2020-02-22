<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $route_id
 * @property string $service_name
 */
class Route extends Model
{
    public $timestamps = false;
    protected $table = 'routes';
}
