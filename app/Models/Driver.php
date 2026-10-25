<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['name', 'code', 'nationality'];

    public function results() { return $this->hasMany(Result::class); }
}
