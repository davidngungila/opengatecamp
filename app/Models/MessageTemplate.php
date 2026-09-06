<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'message', 'created_by', 'key'];

    /**
     * Canonical templates keyed so automated flows can render messages from them.
     * Placeholders: {name}, {event}, {year}, {venue}, {amount}, {paid}, {remaining}, {link}
     */
    public static function defaultTemplates(): array
    {
        return [
            'pledge_received' => [
                'name' => 'Pledge — Contribution Received (Partial)',
                'message' => 'Shaloom {name}. Tumepokea mchango wako wa TZS {amount}, ikiwa ni sehemu ya kukamilisha ahadi yako kwa ajili ya {event}. Tunakushukuru kwa moyo wako wa kujitoa. Mungu akubariki sana.',
            ],
            'pledge_fulfilled' => [
                'name' => 'Pledge — Fulfilled Confirmation',
                'message' => 'Shaloom {name}! Tumepokea na kuthibitisha kukamilika kwa ahadi yako ya TZS {amount} kwa ajili ya {event}. Tunakushukuru kwa moyo wako wa kujitoa na kusaidia kazi hii. Mungu akubariki sana. Endapo utapenda, unaweza kuendelea kuchangia zaidi kwa ajili ya kufanikisha kambi hii.',
            ],
            'pledge_reminder' => [
                'name' => 'Pledge — Reminder with Balance',
                'message' => 'Shaloom {name}. Tunakukumbusha kuhusu ahadi yako ya TZS {amount} kwa ajili ya {event}. Tumepokea TZS {paid}, hivyo salio lililobaki ni TZS {remaining}. Tafadhali tunaomba ukamilishe ahadi yako. Tunakushukuru kwa moyo wako wa kujitoa. Mungu akubariki sana.',
            ],
            'attendee_registered' => [
                'name' => 'Attendee — Registration Success',
                'message' => 'Hongera {name}! Umefanikiwa kusajiliwa kushiriki katika {event} {year}. Tunakukaribisha kwa furaha na tunatarajia kukuona kambini. Tafadhali wasilisha mchango wako wa ushiriki kwa ajili ya kuwezesha maandalizi ya kambi hii. Mungu akubariki.',
            ],
            'attendee_welcome' => [
                'name' => 'Attendee — Welcome to Camp',
                'message' => 'Shaloom {name}. Asante kwa kufika na kushiriki katika {event} {year}. Tunakuombea baraka tele, amani na furaha katika kambi hii. Uwepo wa Mungu usikupungukie, na ukae daima katika uwepo wake. Karibu sana!',
            ],
            'attendee_payment' => [
                'name' => 'Attendee — Payment Received',
                'message' => 'Shaloom {name}. Tumepokea mchango wako wa TZS {amount} kwa ajili ya {event} {year}. Tunakushukuru kwa moyo wako wa kujitoa. Mungu akubariki sana.',
            ],
            'card_invite' => [
                'name' => 'Digital Card — Contribution Invitation',
                'message' => "Shaloom {name}. Umoja wa Vyuo Karismatiki Katoliki Tanzania unakualika kushiriki katika uwezeshaji wa {event} {year}, itakayofanyika {venue}, na kuratibiwa kwa ushirikiano na Umoja wa Vyuo wa Jimbo Kuu la Arusha na Jimbo la Moshi.\n\nTazama kadi yako ya mwaliko ya kidijitali na ushiriki katika kutoa mchango wako kwa ajili ya kuwezesha kambi hii, ili Injili iwafikie vijana wengi zaidi.\n\nMchango wako ni muhimu katika kuhakikisha kambi hii inafanikiwa. Mungu akubariki sana.\n\n{link}",
            ],
        ];
    }

    /**
     * Upsert the canonical templates by key (idempotent; safe on every request).
     */
    public static function seedDefaults(): void
    {
        foreach (static::defaultTemplates() as $key => $template) {
            static::updateOrCreate(
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

        $search = [];
        $replace = [];
        foreach ($data as $placeholder => $value) {
            $search[] = '{'.$placeholder.'}';
            $replace[] = $value === null ? '' : $value;
        }

        return str_replace($search, $replace, $raw);
    }
}