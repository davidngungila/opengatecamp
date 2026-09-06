<?php

namespace Tests\Feature;

use App\Models\DigitalCard;
use App\Models\DigitalCardRecipient;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Pledge;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommitteeScopingSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function committeeUser(string $name = 'Committee One'): User
    {
        $role = Role::firstOrCreate(['name' => 'Committee Member']);
        $user = User::factory()->create(['name' => $name, 'role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_attendees_index_is_scoped_for_committee(): void
    {
        $user = $this->committeeUser();
        $event = Event::create(['title' => 'Camp', 'event_type' => 'camp', 'start_date' => now()]);

        EventAttendee::create(['event_id' => $event->id, 'name' => 'Mine', 'registered_by' => $user->name, 'status' => 'confirmed']);
        EventAttendee::create(['event_id' => $event->id, 'name' => 'Not Mine', 'registered_by' => 'Someone Else', 'status' => 'confirmed']);

        $res = $this->get(route('attendees.index'));
        $res->assertOk();
        $res->assertSee('Mine');
        $res->assertDontSee('Not Mine');
    }

    public function test_pledges_index_is_scoped_for_committee_and_has_no_frequency(): void
    {
        $user = $this->committeeUser();
        $event = Event::create(['title' => 'Camp', 'event_type' => 'camp', 'start_date' => now()]);

        Pledge::create(['event_id' => $event->id, 'pledge_no' => 'PLG-001', 'name' => 'My Pledge', 'amount' => 100, 'created_by' => $user->name, 'pledge_date' => now()]);
        Pledge::create(['event_id' => $event->id, 'pledge_no' => 'PLG-002', 'name' => 'Other Pledge', 'amount' => 100, 'created_by' => 'Someone Else', 'pledge_date' => now()]);

        $res = $this->get(route('pledges.index'));
        $res->assertOk();
        $res->assertSee('My Pledge');
        $res->assertDontSee('Other Pledge');
        $res->assertDontSee('data-frequency');
        $res->assertDontSee('Frequency');
    }

    public function test_digital_cards_index_is_scoped_for_committee(): void
    {
        $user = $this->committeeUser();
        $card = DigitalCard::create([
            'title' => 'Card', 'message' => 'M', 'card_type' => 'camp_invitation',
            'background_color' => '#ffffff', 'accent_color' => '#ffd700',
            'status' => 'active', 'card_no' => DigitalCard::nextCardNo(), 'hash' => 'x'.substr(md5(uniqid()), 0, 30),
        ]);

        DigitalCardRecipient::create(['digital_card_id' => $card->id, 'phone' => '+255 700 111 222', 'name' => 'Added By Me', 'added_by' => $user->name]);
        DigitalCardRecipient::create(['digital_card_id' => $card->id, 'phone' => '+255 700 333 444', 'name' => 'Added By Other', 'added_by' => 'Someone Else']);

        $res = $this->get(route('cards.index'));
        $res->assertOk();
        $res->assertSee('Added By Me');
        $res->assertDontSee('Added By Other');
    }
}