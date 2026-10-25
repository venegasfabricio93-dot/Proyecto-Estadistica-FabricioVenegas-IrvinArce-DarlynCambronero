<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = ['race_id', 'driver_id', 'position', 'points', 'sprint_points'];

    public function race() { return $this->belongsTo(Race::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
}
