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

    public function test_send_sms_parses_newline_and_comma_phones(): void
    {
        $this->seed(['DatabaseSeeder']);

        $fake = $this->createMock(SmsService::class);
        $fake->method('isConfigured')->willReturn(true);
        $fake->method('sendBulk')->willReturn(['success_count' => 2, 'fail_count' => 0, 'results' => []]);
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
            'phones' => "0712345678, +255712345679\n0723456780; 0734567890",
        ]);

        $resp->assertRedirect();
        $resp->assertSessionHas('success');

        $this->assertDatabaseHas('messages', [
            'channel' => 'sms',
            'subject' => "Digital card SMS — {$card->card_no}",
        ]);
    }
}
