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

    public function test_store_calendar_session_creates_one_activity_per_day_in_range(): void
    {
        $this->committeeUser();

        Event::create(['title' => 'Camp Season 4', 'event_type' => 'camp', 'start_date' => now()]);

        $this->post(route('calendar.sessions.store'), [
            'session_date'   => '2026-09-07',
            'end_date'       => '2026-09-10',
            'title'          => 'Morning Devotion',
            'start_time'     => '07:00',
            'end_time'       => '08:00',
            'venue'          => 'Main Hall',
            'category'       => 'Worship',
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('event_sessions', 4);
        $this->assertDatabaseHas('event_sessions', ['session_date' => '2026-09-07 00:00:00', 'title' => 'Morning Devotion', 'category' => 'Worship']);
        $this->assertDatabaseHas('event_sessions', ['session_date' => '2026-09-10 00:00:00', 'title' => 'Morning Devotion']);

        $dates = EventSession::orderBy('session_date')->pluck('session_date')
            ->map(fn ($d) => $d->format('Y-m-d'))->all();
        $this->assertEquals(['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-10'], $dates);
    }

    public function test_store_calendar_session_single_day_when_end_date_missing(): void
    {
        $this->committeeUser();

        Event::create(['title' => 'Camp Season 4', 'event_type' => 'camp', 'start_date' => now()]);

        $this->post(route('calendar.sessions.store'), [
            'session_date' => '2026-09-07',
            'title'        => 'Closing Devotion',
            'start_time'   => '19:00',
            'end_time'     => '20:00',
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('event_sessions', 1);
        $this->assertDatabaseHas('event_sessions', ['session_date' => '2026-09-07 00:00:00', 'title' => 'Closing Devotion']);
    }

    public function test_store_calendar_session_rejects_end_date_before_start_date(): void
    {
        $this->committeeUser();

        Event::create(['title' => 'Camp Season 4', 'event_type' => 'camp', 'start_date' => now()]);

        $this->post(route('calendar.sessions.store'), [
            'session_date' => '2026-09-10',
            'end_date'     => '2026-09-07',
            'title'        => 'Bad Range',
            'start_time'   => '07:00',
            'end_time'     => '08:00',
        ])->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('event_sessions', 0);
    }
}
