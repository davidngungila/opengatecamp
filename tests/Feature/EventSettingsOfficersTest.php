<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSettingsOfficersTest extends TestCase
{
    use RefreshDatabase;

    private function makeRole(string $name): Role
    {
        return Role::updateOrCreate(['name' => $name], ['permissions' => []]);
    }

    public function test_from_settings_creates_camp_event_without_dates(): void
    {
        Setting::put('event.name', 'Open Gate Camp Season Three');
        Setting::put('event.start_date', null);
        Setting::put('event.end_date', null);

        $event = Event::currentCamp();

        $this->assertNotNull($event);
        $this->assertSame('Open Gate Camp Season Three', $event->title);
        $this->assertSame('camp', $event->event_type);
        $this->assertNull($event->start_date);
        $this->assertSame(Event::currentCamp()->id, $event->id, 'currentCamp stays idempotent');
    }

    public function test_selecting_event_officers_updates_their_roles(): void
    {
        $adminRole = $this->makeRole('Chairperson');
        $secretaryRole = $this->makeRole('Secretary');
        $treasurerRole = $this->makeRole('Treasurer');
        $admin = User::factory()->create(['name' => 'Admin One', 'role_id' => $adminRole->id]);
        $secretary = User::factory()->create(['name' => 'Secretary One', 'role_id' => null]);
        $treasurer = User::factory()->create(['name' => 'Treasurer One', 'role_id' => null]);

        $this->actingAs($admin);

        $res = $this->post(route('settings.general'), [
            'event_name' => 'Open Gate Camp Season Three',
            'event_chairperson_id' => $admin->id,
            'event_secretary_id' => $secretary->id,
            'event_treasurer_id' => $treasurer->id,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $this->assertSame($adminRole->id, $admin->fresh()->role_id);
        $this->assertSame($secretaryRole->id, $secretary->fresh()->role_id);
        $this->assertSame($treasurerRole->id, $treasurer->fresh()->role_id);

        $this->assertSame((string) $admin->id, (string) Setting::get('event.chairperson_id'));
        $this->assertSame('Admin One', Setting::get('event.organizer'));
    }

    public function test_duplicate_officer_is_rejected(): void
    {
        $adminRole = $this->makeRole('Chairperson');
        $admin = User::factory()->create(['name' => 'Admin One', 'role_id' => $adminRole->id]);
        $other = User::factory()->create(['name' => 'Other User', 'role_id' => null]);

        $this->actingAs($admin);

        $res = $this->post(route('settings.general'), [
            'event_name' => 'Open Gate Camp Season Three',
            'event_chairperson_id' => $other->id,
            'event_secretary_id' => $other->id,
            'event_treasurer_id' => '',
        ]);

        $res->assertSessionHas('error');
        $this->assertNotSame('Chairperson', $other->fresh()->role?->name);
    }
}