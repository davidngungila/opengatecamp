<?php

namespace Tests\Feature;

use App\Models\DigitalCard;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DigitalCardsSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_sms_parses_name_and_phone_recipients(): void
    {
        $this->seed(['DatabaseSeeder']);

        $fake = $this->createMock(SmsService::class);
        $fake->method('isConfigured')->willReturn(true);
        $fake->method('send')->willReturn(['success' => true, 'status' => 'sent', 'api_message_id' => null, 'raw' => []]);
        $this->app->instance(SmsService::class, $fake);

        $user = User::factory()->create();
        $card = DigitalCard::create([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'SMS Test Card',
            'message' => 'SMS test',
            'card_type' => 'general',
            'hash' => Str::random(32),
            'status' => 'active',
            'is_published' => 1,
        ]);

        $resp = $this->actingAs($user)->post("/digital-cards/{$card->id}/send-sms", [
            'phones' => "John Doe, +255712345678\nJane Smith, 0712345678, extra\n+255712000111",
        ]);

        $resp->assertRedirect();
        $resp->assertSessionHas('success');

        $this->assertDatabaseHas('digital_card_recipients', ['name' => 'John Doe', 'phone' => '+255712345678']);
        $this->assertDatabaseHas('digital_card_recipients', ['name' => 'Jane Smith', 'phone' => '0712345678']);
        $this->assertDatabaseHas('digital_card_recipients', ['name' => null, 'phone' => '+255712000111']);
        $this->assertDatabaseCount('digital_card_recipients', 3);
        $this->assertDatabaseHas('messages', [
            'channel' => 'sms',
            'subject' => "Digital card SMS — {$card->card_no}",
        ]);
    }
}
