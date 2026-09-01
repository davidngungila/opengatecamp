<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'is_default', 'is_closed'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_default' => 'boolean',
        'is_closed' => 'boolean',
    ];

    public static function currentId(): ?int
    {
        if ($id = session('fy_id')) {
            return (int) $id;
        }

        return static::where('is_default', true)->value('id');
    }

    public static function current(): ?self
    {
        $id = static::currentId();

        return $id ? static::find($id) : null;
    }

    public static function scopeForCurrent($query, string $column = 'created_at')
    {
        if ($fy = static::current()) {
            return $query->whereBetween($column, [$fy->start_date, $fy->end_date]);
        }

        return $query;
    }
}
