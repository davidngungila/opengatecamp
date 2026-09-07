<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessagingIndividualSmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);
        $user = User::factory()->create(['name' => 'Messaging Admin', 'role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_sms_page_exposes_individual_manual_number_option(): void
    {
        $this->admin();
        Setting::put('sms.api_token', 'test-token');

        $res = $this->get(route('messaging.sms'));

        $res->assertOk();
        $res->assertSee('Individual / Manual Number');
        $res->assertSee('manualPhone');
    }

    public function test_sending_to_an_individual_phone_sends_one_sms(): void
    {
        $this->admin();
        Setting::put('sms.api_token', 'test-token');

        Http::fake([
            'messaging-service.co.tz/*' => Http::response([
                'messages' => [[
                    'status' => ['id' => 50, 'name' => 'ENROUTE'],
                    'messageId' => 'SMS-INDIV-1',
                    'to' => '255622239304',
                ]],
            ], 200),
        ]);

        $this->post(route('messaging.store'), [
            'channel' => 'sms',
            'recipients' => 'Individual recipient',
            'recipient_type' => 'manual',
            'phone' => '0622 239 304',
            'phones_json' => json_encode(['0622 239 304']),
            'message' => 'Habari rafiki, kumbukumbu ya leo ni jioni. — OpenGate',
            'action' => 'send',
        ])->assertRedirect(route('messaging.sms'))->assertSessionHas('success');

        $this->assertDatabaseHas('messages', [
            'channel' => 'sms',
            'status' => 'sent',
            'phone' => '0622 239 304',
            'api_message_id' => 'SMS-INDIV-1',
            'created_by' => 'Messaging Admin',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/single'
                && $request['to'] === '255622239304'
                && $request['from'] === 'TMCS MoCU'
                && str_contains($request['text'], 'Habari rafiki');
        });
    }

    public function test_individual_phone_can_be_saved_as_draft_without_calling_api(): void
    {
        Http::fake();
        $this->admin();

        $this->post(route('messaging.store'), [
            'channel' => 'sms',
            'recipients' => 'Individual recipient',
            'phone' => '0755 123 456',
            'phones_json' => json_encode(['0755 123 456']),
            'message' => 'Draft message for one person.',
            'action' => 'draft',
        ])->assertRedirect(route('messaging.sms'))->assertSessionHas('success');

        $this->assertDatabaseHas('messages', [
            'channel' => 'sms',
            'status' => 'draft',
            'phone' => '0755 123 456',
        ]);

        Http::assertNothingSent();
    }

    public function test_sending_without_a_phone_number_is_rejected(): void
    {
        Http::fake();
        $this->admin();
        Setting::put('sms.api_token', 'test-token');

        $this->post(route('messaging.store'), [
            'channel' => 'sms',
            'recipients' => 'Individual recipient',
            'phone' => '',
            'phones_json' => '',
            'message' => 'No phone provided.',
            'action' => 'send',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseCount('messages', 0);
        Http::assertNothingSent();
    }
}
