<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    protected $fillable = ['season_id', 'round', 'name', 'date', 'has_sprint'];

    public function season() { return $this->belongsTo(Season::class); }
    public function results() { return $this->hasMany(Result::class); }
}
