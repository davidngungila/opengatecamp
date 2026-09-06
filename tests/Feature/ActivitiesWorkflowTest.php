<?php

namespace Tests\Feature;

use App\Models\ActivityTask;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use App\Models\TaskUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function committeeUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Committee Member']);
        return User::factory()->create(['name' => 'John Peter', 'role_id' => $role->id]);
    }

    public function test_full_task_workflow(): void
    {
        $admin = $this->adminUser();
        $john = $this->committeeUser();
        $event = Event::create(['title' => 'Camp Season 3', 'event_type' => 'camp', 'start_date' => now()]);

        $res = $this->post(route('activities.store'), [
            'title' => 'Find accommodation for invited facilitators',
            'category' => 'Venue & Accommodation',
            'assignee_id' => $john->id,
            'deadline' => now()->addDays(2)->format('Y-m-d'),
            'priority' => 'high',
        ]);
        $res->assertSessionHasNoErrors();
        $res->assertRedirect();

        $task = ActivityTask::where('title', 'Find accommodation for invited facilitators')->first();
        $this->assertNotNull($task);
        $this->assertSame('ACT-0001', $task->task_no);
        $this->assertSame('John Peter', $task->assignee_name);
        $this->assertSame('open', $task->status);
        $this->assertSame($event->id, $task->event_id);
        $this->assertEquals(1, TaskUpdate::where('type', 'assignment')->count());

        $this->get(route('activities.index'))->assertOk()->assertSee('Find accommodation');

        $this->post(route('activities.report', $task), [
            'progress' => 50,
            'report' => '3 hotels contacted',
            'challenges' => 'Two quoted higher than budget',
            'way_forward' => 'Negotiate prices',
        ]);
        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertSame(50, $task->progress);
        $this->assertSame('Two quoted higher than budget', $task->challenges);
        $this->assertSame('Negotiate prices', $task->way_forward);

        $this->post(route('activities.report', $task), [
            'progress' => 100,
            'report' => 'Quotation selected',
            'ready_for_review' => '1',
        ]);
        $task->refresh();
        $this->assertSame('pending_review', $task->status);
        $this->assertSame(100, $task->progress);

        $this->post(route('activities.status', $task), ['status' => 'completed']);
        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        $jane = User::factory()->create(['name' => 'Jane Mushi', 'role_id' => Role::firstOrCreate(['name' => 'Secretary'])->id]);
        $this->post(route('activities.reassign', $task), ['assignee_id' => $jane->id]);
        $task->refresh();
        $this->assertSame('Jane Mushi', $task->assignee_name);
        $this->assertEquals(2, TaskUpdate::where('activity_task_id', $task->id)->where('type', 'assignment')->count());

        $this->get(route('activities.export'))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
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