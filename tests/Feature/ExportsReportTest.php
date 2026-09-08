<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Member;
use App\Models\MessageTemplate;
use App\Models\Pledge;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportsReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        Role::updateOrCreate(['name' => 'Admin'], ['permissions' => ['*']]);
        $user = User::factory()->create(['role_id' => Role::where('name', 'Admin')->value('id')]);
        $this->actingAs($user);

        \App\Models\Setting::put('event.name', 'Open Gate Camp Season Three');
        \App\Models\Setting::put('event.start_date', now());
        \App\Models\Setting::put('event.end_date', now()->addDays(3));
    }

    public function test_attendee_full_name_is_reduced_to_first_name_in_templates(): void
    {
        $this->assertSame('Shukurani', MessageTemplate::firstName('Shukurani Neema Mwakyusa'));
        $this->assertSame('Daniel', MessageTemplate::firstName('Daniel Mwinuka'));
        $this->assertSame('Emmanuel', MessageTemplate::firstName('Dr. Emmanuel Moshi'));
        $this->assertSame('Maria', MessageTemplate::firstName('Maria'));
    }

    public function test_attendees_export_returns_pdf(): void
    {
        $this->admin();

        $event = Event::currentCamp();
        EventAttendee::create([
            'event_id' => $event->id,
            'name' => 'Shukurani Neema Mwakyusa',
            'phone' => '+255700000000',
            'status' => 'confirmed',
        ]);

        $response = $this->get(route('attendees.export'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pledges_export_returns_pdf(): void
    {
        $this->admin();

        $event = Event::currentCamp();
        Pledge::create([
            'event_id' => $event->id,
            'pledge_no' => 'PL-001',
            'name' => 'Daniel Mwinuka',
            'phone' => '+255711111111',
            'amount' => 50000,
            'paid_amount' => 20000,
            'pledge_date' => now(),
        ]);

        $response = $this->get(route('pledges.export'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
    }

    public function test_members_export_returns_pdf(): void
    {
        $this->admin();

        Member::create([
            'member_no' => 'M001',
            'name' => 'Neema John',
            'phone' => '+255722222222',
            'status' => 'Active',
            'member_type' => 'student',
        ]);

        $response = $this->get(route('members.export'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
    }
}