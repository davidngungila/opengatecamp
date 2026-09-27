<?php

namespace App\Models;

use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'message', 'created_by', 'key'];

    /**
     * Canonical templates keyed so automated flows can render messages from them.
     * Source of truth lives in Database\Seeders\MessageTemplateSeeder so the data
     * can be re-seeded independently (php artisan db:seed --class=MessageTemplateSeeder).
     * Placeholders: {name}, {event}, {year}, {venue}, {amount}, {paid}, {remaining}, {link}
     */
    public static function defaultTemplates(): array
    {
        return MessageTemplateSeeder::templates();
    }

    /**
     * Seed only missing canonical templates by key (idempotent; safe on every request).
     * Existing rows are left untouched so admin edits to name/message persist.
     */
    public static function seedDefaults(): void
    {
        foreach (static::defaultTemplates() as $key => $template) {
            static::firstOrCreate(
                ['key' => $key],
                ['name' => $template['name'], 'message' => $template['message']]
            );
        }
    }

    /**
     * Render a template by key, filling {placeholders}. Falls back to the
     * canonical default (and then to null) if the row is missing or empty.
     */
    public static function render(string $key, array $data = []): ?string
    {
        $row = static::where('key', $key)->first();

        if ($row && trim($row->message) !== '') {
            $raw = $row->message;
        } else {
            $defaults = static::defaultTemplates();
            $raw = $defaults[$key]['message'] ?? null;
            if ($raw === null) {
                return null;
            }
        }

        return static::renderRow($raw, $data);
    }

    /**
     * Every automated SMS flow that reads its content from a template.
     * Assignments (template id per usage) are stored in settings so admins can
     * remap "where a template is used" from the templates page.
     * slug => ['label' => ..., 'description' => ..., 'default' => default_key]
     */
    public static function usages(): array
    {
        return [
            'pledge_received' => [
                'label' => 'Pledge — Contribution received',
                'description' => 'Sent automatically when a pledge payment is recorded and the pledge is still partial.',
                'default' => 'pledge_received',
            ],
            'pledge_fulfilled' => [
                'label' => 'Pledge — Fulfilled confirmation',
                'description' => 'Sent when a pledge payment completes the pledge.',
                'default' => 'pledge_fulfilled',
            ],
            'pledge_reminder' => [
                'label' => 'Pledge — Reminder with balance',
                'description' => 'Sent from the Remind (SMS) button on a pledge or the pending-pledges reminder.',
                'default' => 'pledge_reminder',
            ],
            'attendee_registered' => [
                'label' => 'Attendee — Registration success',
                'description' => 'Sent after an attendee is registered on the event page.',
                'default' => 'attendee_registered',
            ],
            'attendee_welcome' => [
                'label' => 'Attendee — Welcome at admission',
                'description' => 'Sent when an attendee is checked in / admitted at the gate.',
                'default' => 'attendee_welcome',
            ],
            'attendee_payment' => [
                'label' => 'Attendee — Payment received',
                'description' => 'Sent when an attendee payment is recorded with the notify SMS option.',
                'default' => 'attendee_payment',
            ],
            'card_invite' => [
                'label' => 'Digital card — Contribution invitation',
                'description' => 'Sent to digital card invitees (save list / send pending / resend).',
                'default' => 'card_invite',
            ],
            'member_welcome' => [
                'label' => 'Member — Portal welcome',
                'description' => 'Sent from the Users page (Send / Send bulk) when a portal member is welcomed to Open Gate Camp Connect.',
                'default' => 'member_welcome',
            ],
            'task_assignment' => [
                'label' => 'Activity — Assignment notification',
                'description' => 'Sent automatically to each assignee when an activity/task is created or (re)assigned, with a login link to report progress.',
                'default' => 'task_assignment',
            ],
        ];
    }

    /**
     * Resolve the template configured for an SMS flow and render it with data.
     * Respects a stored assignment (template id) and falls back to the flow default.
     */
    public static function forUsage(string $usage, array $data = []): ?string
    {
        $usages = static::usages();

        if (! isset($usages[$usage])) {
            return static::render($usage, $data);
        }

        $defaultKey = $usages[$usage]['default'];
        $assignedId = Setting::get('template.usage.'.$usage);

        if ($assignedId !== null && $assignedId !== '') {
            $assigned = static::find((int) $assignedId);
            if ($assigned && trim($assigned->message) !== '') {
                return static::renderRow($assigned->message, $data);
            }
        }

        return static::render($defaultKey, $data);
    }

    /**
     * Map of template id => list of flows using it, for the templates table.
     * A flow counts when a template is explicitly assigned or is the flow default.
     */
    public static function usageMap(iterable $templates): array
    {
        $usages = static::usages();
        $map = [];

        foreach ($templates as $template) {
            $labels = [];
            foreach ($usages as $slug => $usage) {
                $assigned = Setting::get('template.usage.'.$slug);
                $isAssigned = $assigned !== null && $assigned !== '' && (int) $assigned === (int) $template->id;
                $isDefault = ! $isAssigned && $usage['default'] === $template->key;

                if ($isAssigned || $isDefault) {
                    $labels[$slug] = ['label' => $usage['label'], 'active' => $isAssigned];
                }
            }

            $map[$template->id] = $labels;
        }

        return $map;
    }

    /**
     * Resolve the current assignment (template id) and status for every flow.
     */
    public static function usageAssignments(): array
    {
        $usages = static::usages();

        return collect($usages)->mapWithKeys(function ($usage, $slug) {
            $assignedId = Setting::get('template.usage.'.$slug);
            $assignedId = $assignedId !== null && $assignedId !== '' ? (int) $assignedId : null;

            if ($assignedId === null) {
                $status = 'default';
            } else {
                $template = static::find($assignedId);
                $status = $template && trim($template->message) !== '' ? 'connected' : 'missing';
            }

            return [$slug => [
                'assigned_id'   => $assignedId,
                'assigned_name' => $assignedId ? static::find($assignedId)?->name : null,
                'status'        => $status,
            ]];
        })->all();
    }

    /**
     * Return the RAW (unrendered) template message configured for an SMS flow.
     * Respects the stored assignment (template id) and falls back to the flow default.
     * Use this when you need to render per-recipient (e.g. digital-card invites
     * where each SMS has a different {name}/{link}). Rendering once with empty
     * data would wipe the placeholders — so fetch raw once, then call
     * renderText($raw, $placeholders) for each recipient, including single-user sends.
     */
    public static function rawForUsage(string $usage): ?string
    {
        $usages = static::usages();
        $defaultKey = $usages[$usage]['default'] ?? $usage;

        $assignedId = Setting::get('template.usage.'.$usage);
        if ($assignedId !== null && $assignedId !== '') {
            $assigned = static::find((int) $assignedId);
            if ($assigned && trim($assigned->message) !== '') {
                return $assigned->message;
            }
        }

        $row = static::where('key', $defaultKey)->first();
        if ($row && trim($row->message) !== '') {
            return $row->message;
        }

        $defaults = static::defaultTemplates();

        return $defaults[$defaultKey]['message'] ?? null;
    }

    /**
     * Render a raw template string with the given placeholders.
     * Public so per-recipient senders (digital cards, single-user SMS) can
     * render from rawForUsage() without double-rendering.
     */
    public static function renderText(string $raw, array $data = []): string
    {
        return static::renderRow($raw, $data);
    }

    private static function renderRow(string $raw, array $data): string
    {
        // Greet by first name only — convert before replacement so the
        // {name} placeholder is replaced exactly once with the first name.
        if (array_key_exists('name', $data)) {
            $data['name'] = self::firstName((string) $data['name']);
        }

        $search = [];
        $replace = [];
        foreach ($data as $placeholder => $value) {
            $search[] = '{'.$placeholder.'}';
            $replace[] = $value === null ? '' : (string) $value;
        }

        $rendered = str_replace($search, $replace, $raw);

        // Never leak raw {placeholders} into a real SMS — even for a single
        // user with a custom-assigned template that uses extra keys.
        // Any known placeholder left unfilled becomes an empty string.
        $rendered = preg_replace(
            '/\{(name|event|year|venue|amount|paid|remaining|link|task|phone)\}/',
            '',
            $rendered
        );

        return $rendered;
    }

    /**
     * Reduce a full person name to its first name (leading tokens up to an
     * honorific/title boundary). Falls back to the trimmed original.
     */
    public static function firstName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $tokens = preg_split('/\s+/u', $name);
        $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));

        if (count($tokens) === 0) {
            return $name;
        }

        $first = $tokens[0];

        // Titles like "Dr.", "Fr.", "Rev.", "Mr.", "Mrs.", "Ms." are not a
        // person's name — skip them so we greet by the actual first name.
        if (preg_match('/^(Dr|Fr|Rev|Mr|Mrs|Ms|Sr|Br|Prof|Hon)\.?$/i', $first)) {
            return count($tokens) > 1 ? $tokens[1] : $first;
        }

        return $first;
    }
}