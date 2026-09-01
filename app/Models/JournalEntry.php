<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = ['entry_no', 'entry_date', 'description', 'reference', 'status', 'created_by'];

    protected $casts = ['entry_date' => 'date'];

    public function lines() { return $this->hasMany(JournalLine::class); }

    public static function nextEntryNo(): string
    {
        $max = (int) substr((string) (static::query()->max('entry_no') ?? 'JE-0000'), -4);

        return 'JE-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
