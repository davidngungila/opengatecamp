<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\DigitalCard;
use App\Models\DigitalCardRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Delete
        $this->actingAs($user)
            ->delete("/digital-cards/{$card->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('digital_cards', ['id' => $card->id]);
    }
}
