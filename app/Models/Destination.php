<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Destination extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $hidden = ['country'];
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($destination) {
            if (!$destination->{$destination->getKeyName()}) {
                $destination->{$destination->getKeyName()} = (string) Uuid::uuid4();
            }
        });
    }

    public function stations()
    {
        return $this->hasMany('App\Models\Station');
    }
}
