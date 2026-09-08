<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Fellowship;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FellowshipDelegationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    private function leaderUser(): User
    {
        $user = User::factory()->create(['role_id' => null, 'status' => 'Active']);
        $this->actingAs($user);

        return $user;
    }

    private function makeFellowship(string $name, array $leaderIds = []): Fellowship
    {
        $fellowship = Fellowship::create(['name' => $name, 'active' => true]);

        if ($leaderIds) {
            $pivots = [];
            foreach ($leaderIds as $i => $id) {
                $pivots[$id] = ['is_primary' => $i === 0, 'title' => $i === 0 ? 'Primary Leader' : null];
            }
            $fellowship->leaders()->attach($pivots);
        }

        return $fellowship;
    }

    private function provisionEvent(): Event
    {
        Setting::put('event.name', 'Open Gate Camp Test');
        Setting::put('event.registration_fee', 10000);
        Setting::put('event.start_date', now()->toDateString());

        return Event::currentCamp();
    }

    public function test_admin_can_create_fellowship_with_leaders(): void
    {
        $this->adminUser();
        $leader = User::factory()->create();

        $this->post(route('fellowships.store'), [
            'name' => 'Test University (TU)',
            'type' => 'christian_union',
            'university' => 'Test University',
            'active' => 1,
            'leader_ids' => [$leader->id],
            'primary_leader_id' => $leader->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('fellowships', ['name' => 'Test University (TU)', 'active' => true]);

        $fellowship = Fellowship::where('name', 'Test University (TU)')->first();
        $this->assertTrue($fellowship->leaders()->where('user_id', $leader->id)->exists());
        $this->assertTrue((bool) $fellowship->leaders()->find($leader->id)->pivot->is_primary);

        $this->get(route('fellowships.index'))->assertOk()->assertSee('Test University (TU)');
    }

    public function test_committee_cannot_manage_fellowships(): void
    {
        $role = Role::firstOrCreate(['name' => 'Committee Member']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($user);

        $this->post(route('fellowships.store'), ['name' => 'Blocked'])->assertForbidden();
    }

    public function test_fellowship_with_delegates_cannot_be_deleted(): void
    {
        $this->adminUser();
        $event = $this->provisionEvent();
        $fellowship = $this->makeFellowship('Delegated Fellowship');

        EventAttendee::create([
            'event_id' => $event->id,
            'fellowship_id' => $fellowship->id,
            'fellowship' => $fellowship->name,
            'name' => 'Jane Delegate',
            'phone' => '255700000001',
            'status' => 'pending',
            'amount_paid' => 0,
            'fee_amount' => 10000,
        ]);

        $this->delete(route('fellowships.destroy', $fellowship))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('fellowships', ['name' => 'Delegated Fellowship']);
    }

    public function test_registration_fellowship_dropdown_reads_from_table(): void
    {
        $this->adminUser();
        $this->makeFellowship('NM-AIST Catholic Union');
        $this->provisionEvent();

        $this->get(route('attendees.index'))->assertOk()->assertSee('NM-AIST Catholic Union');
    }

    public function test_leader_sees_their_fellowship_in_portal(): void
    {
        $leader = $this->leaderUser();
        $fellowship = $this->makeFellowship('Kilimanjaro Christian Union', [$leader->id]);

        $this->get(route('portal.fellowship.dashboard'))->assertOk()->assertSee('Kilimanjaro Christian Union');
        $this->get(route('portal.fellowship.members', $fellowship))->assertOk()->assertSee('Delegation Members');
    }

    public function test_leader_cannot_open_other_fellowship(): void
    {
        $this->leaderUser();
        $other = $this->makeFellowship('Other University');

        $this->get(route('portal.fellowship.members', $other))->assertForbidden();
    }

    public function test_leader_registers_a_member(): void
    {
        $leader = $this->leaderUser();
        $fellowship = $this->makeFellowship('MoCU Christian Union', [$leader->id]);
        $event = $this->provisionEvent();

        $this->post(route('portal.fellowship.members.store', $fellowship), [
            'name' => 'Nelson Mwakyusa',
            'phone' => '+255 713 000 000',
            'email' => 'nelson@example.com',
            'pickup_location' => 'moshi',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'fellowship_id' => $fellowship->id,
            'fellowship' => 'MoCU Christian Union',
            'name' => 'Nelson Mwakyusa',
            'status' => 'pending',
            'amount_paid' => 0,
            'fee_amount' => 10000,
            'registered_by' => $leader->name,
        ]);

        $this->get(route('portal.fellowship.members', $fellowship))->assertSee('Nelson Mwakyusa');
    }

    public function test_leader_can_edit_own_member_but_not_others(): void
    {
        $leader = $this->leaderUser();
        $mine = $this->makeFellowship('My CU', [$leader->id]);
        $other = $this->makeFellowship('Other CU');
        $event = $this->provisionEvent();

        $mineMember = EventAttendee::create([
            'event_id' => $event->id,
            'fellowship_id' => $mine->id,
            'fellowship' => $mine->name,
            'name' => 'My Person',
            'phone' => '255700000001',
            'pickup_location' => 'arusha',
            'status' => 'pending',
            'amount_paid' => 0,
            'fee_amount' => 10000,
        ]);

        $otherMember = EventAttendee::create([
            'event_id' => $event->id,
            'fellowship_id' => $other->id,
            'fellowship' => $other->name,
            'name' => 'Their Person',
            'phone' => '255700000002',
            'pickup_location' => 'arusha',
            'status' => 'pending',
            'amount_paid' => 0,
            'fee_amount' => 10000,
        ]);

        $this->put(route('portal.fellowship.members.update', $mineMember->hashed_id), [
            'name' => 'My Person Renamed',
            'phone' => '255700000001',
            'pickup_location' => 'arusha',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_attendees', ['id' => $mineMember->id, 'name' => 'My Person Renamed']);

        $this->put(route('portal.fellowship.members.update', $otherMember->hashed_id), [
            'name' => 'Hacked',
            'phone' => '255700000002',
            'pickup_location' => 'arusha',
        ])->assertForbidden();
    }

    public function test_paid_member_cannot_be_removed_by_leader(): void
    {
        $leader = $this->leaderUser();
        $fellowship = $this->makeFellowship('Active CU', [$leader->id]);
        $event = $this->provisionEvent();

        $member = EventAttendee::create([
            'event_id' => $event->id,
            'fellowship_id' => $fellowship->id,
            'fellowship' => $fellowship->name,
            'name' => 'Paying Member',
            'phone' => '255700000001',
            'status' => 'confirmed',
            'amount_paid' => 10000,
            'fee_amount' => 10000,
        ]);

        $this->delete(route('portal.fellowship.members.destroy', $member->hashed_id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('event_attendees', ['id' => $member->id]);
    }

    public function test_login_redirects_leader_to_fellowship_dashboard(): void
    {
        $leader = User::factory()->create([
            'role_id' => null,
            'status' => 'Active',
            'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
        ]);
        $this->makeFellowship('Redirect CU', [$leader->id]);

        $this->post(route('login.attempt'), [
            'login' => $leader->email,
            'password' => 'secret123',
        ])->assertRedirect(route('portal.fellowship.dashboard'));
    }
}