<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ministry extends Model
{
    protected $fillable = ['name', 'leader', 'description'];

    public function members() { return $this->hasMany(Member::class); }
}
