<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'leader', 'meeting_schedule'];

    public function members() { return $this->hasMany(Member::class); }
}
