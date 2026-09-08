<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $raw = (string) DB::table('settings')->where('key', 'fellowships.list')->value('value');

        $names = collect(explode("\n", $raw))
            ->map(fn ($f) => trim($f))
            ->filter()
            ->values()
            ->all();

        $now = now();
        foreach ($names as $name) {
            $exists = DB::table('fellowships')->where('name', $name)->exists();
            if (! $exists) {
                DB::table('fellowships')->insert([
                    'name' => $name,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Backfill existing attendees whose free-text fellowship matches a known fellowship.
        $rows = DB::table('event_attendees')
            ->whereNotNull('fellowship')
            ->whereNull('fellowship_id')
            ->get(['id', 'fellowship']);

        foreach ($rows as $row) {
            $id = DB::table('fellowships')->where('name', $row->fellowship)->value('id');
            if ($id) {
                DB::table('event_attendees')->where('id', $row->id)->update(['fellowship_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        DB::table('fellowships')->truncate();
    }
};