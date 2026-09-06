<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function committeeUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Committee Member']);
        $user = User::factory()->create(['name' => 'Committee Calendar', 'role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_timetable_route_is_removed(): void
    {
        $this->committeeUser();

        $this->get('/calendar/timetable')->assertNotFound();
    }

    public function test_planner_renders_plan_day_activity_view(): void
    {
        $this->committeeUser();

        $event = Event::create(['title' => 'Camp Season 4', 'event_type' => 'camp', 'start_date' => now()]);
        EventSession::create([
            'event_id' => $event->id,
            'session_date' => now()->toDateString(),
            'title' => 'Opening Devotion',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'venue' => 'Main Hall',
            'category' => 'Worship',
            'speaker' => 'Fr. Daniel',
        ]);

        $res = $this->get(route('calendar.planner', ['date' => now()->format('Y-m-d')]));

        $res->assertOk();
        $res->assertSee('Plan Day Activity');
        $res->assertSee('Schedule an activity for a specific day and hours');
        $res->assertSee('Opening Devotion');
        $res->assertSee('08:00');
        $res->assertSee('openPlanDrawer');
        $res->assertSee('openEditDrawer');
    }

    public function test_calendar_index_links_to_planner_instead_of_timetable(): void
    {
        $this->committeeUser();

        $res = $this->get(route('calendar.index'));

        $res->assertOk();
        $res->assertSee('Day Planner');
        $res->assertDontSee('Print Timetable');
    }
}