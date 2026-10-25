<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = ['year'];

    public function races() { return $this->hasMany(Race::class); }
}
