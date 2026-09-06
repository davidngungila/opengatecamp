<?php

namespace Tests\Feature;

use App\Models\ActivityTask;
use App\Models\Event;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\TaskUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ActivitiesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Administrator']);
        $user = User::factory()->create(['name' => 'Big Leader', 'role_id' => $role->id]);
        $this->actingAs($user);

        return $user;
    }

    private function committeeUser(string $name, ?string $phone = null): User
    {
        $role = Role::firstOrCreate(['name' => 'Committee Member']);
        return User::factory()->create(['name' => $name, 'role_id' => $role->id, 'phone' => $phone]);
    }

    public function test_full_task_workflow_with_multiple_assignees(): void
    {
        $admin = $this->adminUser();
        $john = $this->committeeUser('John Peter', '+255712000001');
        $jane = $this->committeeUser('Jane Mushi', '+255712000002');
        $event = Event::create(['title' => 'Camp Season 3', 'event_type' => 'camp', 'start_date' => now()]);

        $res = $this->post(route('activities.store'), [
            'title' => 'Find accommodation for invited facilitators',
            'category' => 'Venue & Accommodation',
            'assignee_ids' => [$john->id, $jane->id],
            'deadline' => now()->addDays(2)->format('Y-m-d'),
            'priority' => 'high',
        ]);
        $res->assertSessionHasNoErrors();
        $res->assertRedirect();

        $task = ActivityTask::where('title', 'Find accommodation for invited facilitators')->first();
        $this->assertNotNull($task);
        $this->assertSame('ACT-0001', $task->task_no);
        $this->assertSame('open', $task->status);
        $this->assertSame($event->id, $task->event_id);
        $this->assertSame('John Peter, Jane Mushi', $task->assignee_name);
        $this->assertEquals(2, $task->assignees()->count());
        $this->assertEquals(1, TaskUpdate::where('type', 'assignment')->count());

        $this->get(route('activities.index'))->assertOk()->assertSee('ACT-0001');

        // Both assignees can report on the same activity.
        $this->actingAs($john)->post(route('activities.report', $task), [
            'progress' => 50,
            'report' => '3 hotels contacted',
            'challenges' => 'Two quoted higher than budget',
            'way_forward' => 'Negotiate prices',
        ]);
        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertSame(50, $task->progress);

        $this->actingAs($jane)->post(route('activities.report', $task), [
            'progress' => 100,
            'report' => 'Quotation selected',
            'ready_for_review' => '1',
        ]);
        $task->refresh();
        $this->assertSame('pending_review', $task->status);
        $this->assertSame(100, $task->progress);

        $this->actingAs($admin)->post(route('activities.status', $task), ['status' => 'completed']);
        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        // Reassign clears and re-sets the assignee set.
        $this->post(route('activities.reassign', $task), ['assignee_ids' => [$jane->id]]);
        $task->refresh();
        $this->assertSame('Jane Mushi', $task->assignee_name);
        $this->assertEquals(1, $task->assignees()->count());
        $this->assertEquals(2, TaskUpdate::where('activity_task_id', $task->id)->where('type', 'assignment')->count());

        $this->get(route('activities.export'))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Filter by a secondary assignee finds the task via the pivot.
        $this->get(route('activities.index', ['assignee' => $jane->id]))->assertOk()->assertSee('ACT-0001');
        $this->get(route('activities.index', ['assignee' => $john->id]))->assertOk()->assertDontSee('ACT-0001');
    }

    public function test_assignment_sms_sent_in_swahili_with_login_link_to_each_assignee(): void
    {
        $this->adminUser();
        $john = $this->committeeUser('John Peter', '+255712000001');
        $jane = $this->committeeUser('Jane Mushi', '+255712000002');
        Event::create(['title' => 'Camp Season 3', 'event_type' => 'camp', 'start_date' => now()]);

        Setting::put('sms.api_token', 'test-token');
        Setting::put('sms.sender_id', 'TMCS MoCU');

        Http::fake();

        $this->post(route('activities.store'), [
            'title' => 'Prepare sound system',
            'category' => 'Logistics',
            'assignee_ids' => [$john->id, $jane->id],
            'priority' => 'medium',
        ])->assertSessionHasNoErrors();

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) use ($john) {
            $text = $request['text'];
            return $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/single'
                && $request['to'] === '255712000001'
                && str_contains($text, 'John Peter')
                && str_contains($text, 'Prepare sound system')
                && str_contains($text, 'Camp Season 3')
                && str_contains($text, '/login')
                && str_contains($text, 'Umepewa kazi')
                && str_contains($text, 'ingia kwenye mfumo');
        });
        Http::assertSent(function (Request $request) {
            return str_contains($request['text'], 'Jane Mushi');
        });
    }

    public function test_reject_progress_on_closed_task(): void
    {
        $this->adminUser();
        $task = ActivityTask::create([
            'task_no' => ActivityTask::nextTaskNo(),
            'title' => 'Closed task',
            'status' => 'closed',
            'progress' => 100,
            'priority' => 'medium',
        ]);

        $this->post(route('activities.report', $task), ['progress' => 10])
            ->assertSessionHas('error');
        $task->refresh();
        $this->assertSame('closed', $task->status);
    }
}