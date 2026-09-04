<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\DigitalCard;
use App\Models\DigitalCardContribution;
use App\Models\DigitalCardRecipient;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DigitalCardsFlowTest extends TestCase
{
    use RefreshDatabase;

    private function seedAccounts(): void
    {
        Account::create(['code' => '1000', 'name' => 'Bank Account', 'type' => 'asset', 'is_cash' => true]);
        Account::create(['code' => '1010', 'name' => 'Cash on Hand', 'type' => 'asset', 'is_cash' => true]);
        Account::create(['code' => '1020', 'name' => 'Mobile Money Float', 'type' => 'asset', 'is_cash' => true]);
        Account::create(['code' => '4020', 'name' => 'Donation Income', 'type' => 'income']);
    }

    private function makeCard(?Event $event = null, array $extra = []): DigitalCard
    {
        return DigitalCard::create(array_merge([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'Dashboard Card',
            'message' => 'Message',
            'card_type' => 'fundraising',
            'background_color' => '#1a237e',
            'accent_color' => '#ffd700',
            'event_id' => $event?->id,
            'target_amount' => 200000,
            'hash' => Str::random(32),
            'status' => 'active',
            'is_published' => 1,
        ], $extra));
    }

    public function test_dashboard_aggregates_single_event(): void
    {
        $user = User::factory()->create();

        $eventA = Event::create(['title' => 'Camp 2026', 'event_type' => 'camp', 'start_date' => now()]);
        $eventB = Event::create(['title' => 'Conference 2026', 'event_type' => 'conference', 'start_date' => now()]);

        $cardA = $this->makeCard($eventA);
        $cardB = $this->makeCard($eventA, ['target_amount' => 800000]);
        $cardC = $this->makeCard($eventB);

        DigitalCardContribution::create(['digital_card_id' => $cardA->id, 'contributor_name' => 'Asha', 'amount' => 50000, 'method' => 'mobile', 'status' => 'confirmed']);
        DigitalCardContribution::create(['digital_card_id' => $cardB->id, 'contributor_name' => 'Baraka', 'amount' => 25000, 'method' => 'cash', 'status' => 'confirmed']);
        DigitalCardContribution::create(['digital_card_id' => $cardB->id, 'contributor_name' => 'Pumba', 'amount' => 90000, 'method' => 'bank', 'status' => 'failed']);
        DigitalCardContribution::create(['digital_card_id' => $cardC->id, 'contributor_name' => 'Other Event', 'amount' => 999999, 'method' => 'cash', 'status' => 'confirmed']);

        // No ?e= → auto-selects the event whose card has the highest confirmed total (Camp card A/B vs Conference card C).
        $this->actingAs($user)
            ->get('/digital-cards')
            ->assertOk()
            ->assertSee('Conference 2026')
            ->assertSee('Other Event')
            ->assertSee('999,999')
            ->assertDontSee('Asha');

        // ?e= override scopes everything to the chosen event and aggregates its cards.
        $this->actingAs($user)
            ->get('/digital-cards?e='.$eventA->id)
            ->assertOk()
            ->assertSee('Camp 2026')
            ->assertSee('Asha')
            ->assertSee('Baraka')
            ->assertSee('Pumba')
            ->assertSee('TZS 75,000')
            ->assertSee('TZS 1,000,000')
            ->assertDontSee('Other Event')
            ->assertDontSee('999,999');
    }

    public function test_admin_can_record_contribution(): void
    {
        $this->seedAccounts();

        $user = User::factory()->create();
        $event = Event::create(['title' => 'Giving Event', 'event_type' => 'camp', 'start_date' => now()]);
        $card = $this->makeCard($event);

        $this->actingAs($user)
            ->post("/digital-cards/{$card->id}/add-contribution", [
                'contributor_name' => 'Grace Mushi',
                'contributor_phone' => '+255712000000',
                'amount' => 120000,
                'method' => 'bank',
                'reference_no' => 'REF-2001',
                'note' => 'Bank transfer from Grace',
            ])->assertRedirect();

        $card->refresh();
        $this->assertEquals(1, $card->contributions_count);
        $this->assertEquals(120000, $card->total_contributions);

        $this->assertDatabaseHas('digital_card_contributions', [
            'digital_card_id' => $card->id,
            'contributor_name' => 'Grace Mushi',
            'amount' => 120000,
            'method' => 'bank',
            'reference_no' => 'REF-2001',
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('journal_entries', ['description' => "Digital card contribution — {$card->card_no}: {$card->title}"]);
    }

    public function test_update_card_saves_blank_title_and_message_as_empty_strings(): void
    {
        $user = User::factory()->create();
        $card = DigitalCard::create([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'Temp Title',
            'message' => 'Temp message',
            'card_type' => 'general',
            'hash' => Str::random(32),
            'status' => 'draft',
            'is_published' => 0,
        ]);

        $this->actingAs($user)
            ->put("/digital-cards/{$card->id}", [
                'title' => '',
                'message' => '',
                'card_type' => 'general',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('digital_cards', ['id' => $card->id, 'title' => '', 'message' => '']);
    }

    public function test_admin_can_upload_and_remove_background_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $card = $this->makeCard();

        $this->actingAs($user)
            ->put("/digital-cards/{$card->id}", [
                'title' => 'With Image',
                'message' => '',
                'card_type' => 'fundraising',
                'image_path' => UploadedFile::fake()->image('bg.jpg', 640, 800),
            ])
            ->assertRedirect();

        $card->refresh();
        $this->assertNotNull($card->image_path);
        $this->assertStringStartsWith('digital-cards/', $card->image_path);
        Storage::disk('public')->assertExists($card->image_path);

        $storedPath = $card->image_path;

        $this->actingAs($user)
            ->put("/digital-cards/{$card->id}", [
                'title' => 'With Image',
                'message' => '',
                'card_type' => 'fundraising',
                'remove_image' => '1',
            ])
            ->assertRedirect();

        $card->refresh();
        $this->assertNull($card->image_path);
        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_public_card_flow(): void
    {
        $this->seedAccounts();

        $card = DigitalCard::create([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'HTTP Test Card',
            'message' => 'Testing the HTTP flow.',
            'card_type' => 'camp_invitation',
            'background_color' => '#1a237e',
            'accent_color' => '#ffd700',
            'target_amount' => 200000,
            'cta_text' => 'Contribute Now',
            'hash' => Str::random(32),
            'status' => 'active',
            'is_published' => 1,
        ]);

        $this->get("/card/{$card->hash}")
            ->assertOk()
            ->assertSee($card->title)
            ->assertSee('Changia Sasa')
            ->assertSee('Weka Ahadi Leo')
            ->assertSee('Mobile Money')
            ->assertSee('Bank Transfer');

        $this->post("/card/{$card->hash}/contribute", [
            'contributor_name' => 'Jane Doe',
            'contributor_phone' => '+255712345678',
            'amount' => 25000,
            'method' => 'mobile',
            'reference_no' => 'TXN-1',
            'note' => 'blessings',
        ])->assertRedirect("/card/{$card->hash}");

        $this->assertDatabaseHas('digital_card_contributions', [
            'contributor_name' => 'Jane Doe',
            'amount' => 25000,
            'status' => 'confirmed',
        ]);

        $card->refresh();
        $this->assertEquals(1, $card->contributions_count);
        $this->assertEquals(25000, $card->total_contributions);
    }

    public function test_inactive_card_cannot_receive_contributions(): void
    {
        $this->seedAccounts();

        $card = DigitalCard::create([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'Closed Card',
            'message' => 'No longer accepting contributions.',
            'card_type' => 'general',
            'hash' => Str::random(32),
            'status' => 'closed',
            'is_published' => 0,
        ]);

        $this->post("/card/{$card->hash}/contribute", [
            'amount' => 1000,
            'method' => 'cash',
        ])->assertStatus(404);
    }

    public function test_public_card_pledge_flow(): void
    {
        $card = DigitalCard::create([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'Pledge Card',
            'message' => 'Make a pledge.',
            'card_type' => 'fundraising',
            'background_color' => '#1a237e',
            'accent_color' => '#ffd700',
            'target_amount' => 500000,
            'cta_text' => 'Contribute Now',
            'hash' => Str::random(32),
            'status' => 'active',
            'is_published' => 1,
        ]);

        $this->post("/card/{$card->hash}/contribute", [
            'mode' => 'pledge',
            'contributor_name' => 'Peter Pledge',
            'contributor_phone' => '+255700000000',
            'amount' => 50000,
            'due_date' => now()->addMonth()->toDateString(),
            'note' => 'will pay after harvest',
        ])->assertRedirect("/card/{$card->hash}");

        $this->assertDatabaseHas('pledges', [
            'name' => 'Peter Pledge',
            'phone' => '+255700000000',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'pending',
            'frequency' => 'one_time',
        ]);

        $card->refresh();
        $this->assertEquals(0, $card->contributions_count);
        $this->assertEquals(0, $card->total_contributions);
    }

    public function test_recipient_token_prefills_details(): void
    {
        $card = DigitalCard::create([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'Personalized Card',
            'message' => 'For you.',
            'card_type' => 'thank_you',
            'background_color' => '#1a237e',
            'accent_color' => '#ffd700',
            'hash' => Str::random(32),
            'status' => 'active',
            'is_published' => 1,
        ]);

        $recipient = DigitalCardRecipient::create([
            'digital_card_id' => $card->id,
            'name' => 'Neema Mwakyusa',
            'phone' => '+255712345678',
            'token' => Str::random(32),
        ]);

        $this->get("/card/{$card->hash}?r={$recipient->token}")
            ->assertOk()
            ->assertSee('Shukurani Neema Mwakyusa')
            ->assertSee('value="Neema Mwakyusa"', false)
            ->assertSee('value="+255712345678"', false);
    }

    public function test_admin_flow(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/digital-cards')
            ->assertOk();

        $card = DigitalCard::create([
            'card_no' => DigitalCard::nextCardNo(),
            'title' => 'Store Test',
            'message' => 'Store test message',
            'card_type' => 'christmas',
            'hash' => Str::random(32),
            'status' => 'draft',
        ]);

        // Activate
        $this->actingAs($user)
            ->post("/digital-cards/{$card->id}/status", ['status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseHas('digital_cards', ['id' => $card->id, 'status' => 'active', 'is_published' => 1]);

        // Preview page renders as ticket-size sheet
        $this->actingAs($user)
            ->get("/digital-cards/{$card->id}/preview")
            ->assertOk()
            ->assertSee('Store Test')
            ->assertSee('DIGITAL CARD')
            ->assertSee('Pakua PDF');

        // PDF endpoint should not error (browser flow serves the binary directly)
        $this->actingAs($user)
            ->get("/digital-cards/{$card->id}/pdf")
            ->assertStatus(200);

        // Public PDF (receipt/ticket style) is downloadable without auth
        $this->get("/card/{$card->hash}/pdf")
            ->assertStatus(200);

        // Public page offers the PDF download link
        $this->get("/card/{$card->hash}")
            ->assertOk()
            ->assertSee(route('cards.publicPdf', $card->hash));

        // Details page shows one card's full information
        $this->actingAs($user)
            ->get("/digital-cards/{$card->id}")
            ->assertOk()
            ->assertSee($card->card_no)
            ->assertSee('Contributions')
            ->assertSee('SMS Recipients')
            ->assertSee($card->public_url);

        // Details drawer fragment loads for the right-side drawer
        $this->actingAs($user)
            ->get("/digital-cards/{$card->id}?drawer=1")
            ->assertOk()
            ->assertSee('cardDetailDrawer')
            ->assertSee('SMS Recipients')
            ->assertSee('Pakua PDF');

        // Delete
        $this->actingAs($user)
            ->delete("/digital-cards/{$card->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('digital_cards', ['id' => $card->id]);
    }
}
