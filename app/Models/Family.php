<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $fillable = ['name', 'head', 'address'];

    public function members() { return $this->hasMany(Member::class); }
}
