<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $targetTitle = 'Open Gate Camp Season 3';
        $targetSlug  = 'open-gate-camp-season-3';

        $event = DB::table('events')
            ->where('slug', 'open-gate-summer-camp-AzZz')
            ->orWhere(function ($q) use ($targetTitle) {
                $q->where('title', 'like', 'Open Gate%Summer%Camp%')
                  ->where('title', '!=', $targetTitle);
            })
            ->orderBy('id')
            ->first();

        if (! $event) {
            return;
        }

        $slug = $targetSlug;
        $i = 2;
        while (DB::table('events')->where('slug', $slug)->where('id', '!=', $event->id)->exists()) {
            $slug = $targetSlug.'-'.$i++;
        }

        DB::table('events')->where('id', $event->id)->update([
            'title' => $targetTitle,
            'slug'  => $slug,
        ]);
    }

    public function down(): void
    {
        // Reverting is ambiguous once renamed; no-op to avoid corrupting other data.
    }
};
