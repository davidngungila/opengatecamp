<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Canonical SMS templates keyed so automated flows can render messages from them.
     * Every key must match a flow in MessageTemplate::usages() so the templates page
     * can show where each template is used.
     * Placeholders: {name}, {event}, {year}, {venue}, {amount}, {paid}, {remaining}, {link}
     */
    public static function templates(): array
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
     * Upsert the canonical templates by key (idempotent; safe to run repeatedly).
     */
    public function run(): void
    {
        if (! Schema::hasTable('message_templates')) {
            return;
        }

        foreach (static::templates() as $key => $template) {
            MessageTemplate::updateOrCreate(
                ['key' => $key],
                [
                    'name'       => $template['name'],
                    'message'    => $template['message'],
                    'created_by' => 'System',
                ]
            );
        }
    }
}