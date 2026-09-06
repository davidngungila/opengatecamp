<?php

namespace App\Http\Controllers;

use App\Models\ActivityTask;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\MessageTemplate;
use App\Models\TaskUpdate;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;

class ActivityTaskController extends Controller
{
    private function assignableUsers()
    {
        return User::where(fn ($q) => $q->where('status', '!=', 'Suspended')->orWhereNull('status'))
            ->orderBy('name')
            ->get();
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $priority = $request->query('priority');
        $category = $request->query('category');
        $assignee = $request->query('assignee');
        $q = trim((string) $request->query('q'));

        $query = ActivityTask::with(['event', 'assignee', 'updates']);

        $query->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($priority, fn ($qr) => $qr->where('priority', $priority))
            ->when($category, fn ($qr) => $qr->where('category', $category))
            ->when($assignee, fn ($qr) => $qr->whereHas('assignees', fn ($a) => $a->where('users.id', (int) $assignee)))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('task_no', 'like', "%{$q}%")
                ->orWhere('assignee_name', 'like', "%{$q}%")));

        $tasks = $query->orderByRaw('CASE
                WHEN status IN ("open", "in_progress", "pending_review") THEN 0
                ELSE 1
                END')
            ->orderBy('deadline')
            ->paginate(15)->withQueryString();

        $base = ActivityTask::query();
        $count = fn (string $col, $cond) => $base->clone()->where($col, $cond)->count();

        $overdue = ActivityTask::query()
            ->where('deadline', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->count();

        $stats = [
            'total'          => $base->clone()->count(),
            'open'           => $count('status', 'open'),
            'in_progress'    => $count('status', 'in_progress'),
            'pending_review' => $count('status', 'pending_review'),
            'completed'      => $count('status', 'completed'),
            'closed'         => $count('status', 'closed'),
            'overdue'        => $overdue,
            'urgent'         => $base->clone()->where('priority', 'urgent')->whereNotIn('status', ['completed', 'closed', 'cancelled'])->count(),
        ];

        return view('activities.index', [
            'tasks' => $tasks,
            'statuses' => ActivityTask::statuses(),
            'priorities' => ActivityTask::priorities(),
            'categories' => ActivityTask::categories(),
            'assignees' => $this->assignableUsers(),
            'campEvent' => Event::currentCamp(),
            'stats' => $stats,
            'filters' => compact('status', 'priority', 'category', 'assignee', 'q'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:100',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'integer|exists:users,id',
            'deadline' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'event_id' => 'nullable|exists:events,id',
        ]);

        $assigneeIds = $data['assignee_ids'] ?? [];
        $assignees = $assigneeIds ? User::whereIn('id', $assigneeIds)->get() : collect();

        $task = ActivityTask::create([
            'task_no' => ActivityTask::nextTaskNo(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'event_id' => $data['event_id'] ?? Event::currentCamp()?->id,
            'assignee_id' => $assignees->first()?->id,
            'assignee_name' => $assignees->pluck('name')->implode(', '),
            'assigned_by_id' => auth()->id(),
            'deadline' => $data['deadline'] ?? null,
            'priority' => $data['priority'],
            'status' => 'open',
            'progress' => 0,
            'created_by' => auth()->user()?->name,
        ]);

        $task->syncAssignees($assigneeIds);

        TaskUpdate::create([
            'activity_task_id' => $task->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'type' => 'assignment',
            'content' => $assignees->isNotEmpty()
                ? 'Assigned to '.$assignees->pluck('name')->implode(', ')
                    .($task->deadline ? " (deadline {$task->deadline->format('d M Y')})" : '').'.'
                : 'Created — waiting for assignee.',
            'progress' => 0,
            'new_value' => $assignees->pluck('name')->implode(', ') ?: null,
        ]);

        $this->notifyAssignees($task, $assignees);

        AuditLog::record('Created task', 'Activities & Tasks', "{$task->task_no} — {$task->title}");

        return back()->with('success', "Task {$task->task_no} created and assigned.");
    }

    public function update(Request $request, ActivityTask $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:100',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'integer|exists:users,id',
            'deadline' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $newIds = array_values(array_unique(array_filter($data['assignee_ids'] ?? [])));
        $assignees = $newIds ? User::whereIn('id', $newIds)->get() : collect();
        $oldIds = $task->assignees->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        sort($oldIds);
        sort($newIds);

        if ($oldIds !== $newIds) {
            TaskUpdate::create([
                'activity_task_id' => $task->id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'type' => 'assignment',
                'content' => 'Assignees updated to: '.($assignees->pluck('name')->implode(', ') ?: 'Unassigned').'.',
                'old_value' => $task->assignee_name,
                'new_value' => $assignees->pluck('name')->implode(', ') ?: null,
            ]);
            $data['assignee_name'] = $assignees->pluck('name')->implode(', ') ?: null;
            $data['assignee_id'] = $assignees->first()?->id;
        }

        unset($data['assignee_ids']);

        $task->update($data);
        $task->syncAssignees($newIds);
        $task->loadMissing('assignees');

        $this->notifyAssignees($task, $assignees, array_diff($newIds, $oldIds));

        AuditLog::record('Updated task', 'Activities & Tasks', "{$task->task_no} — {$task->title}");

        return back()->with('success', "Task {$task->task_no} updated.");
    }

    public function destroy(ActivityTask $task)
    {
        AuditLog::record('Deleted task', 'Activities & Tasks', "{$task->task_no} — {$task->title}");
        $task->delete();

        return back()->with('success', "Task {$task->task_no} deleted.");
    }

    public function submitReport(Request $request, ActivityTask $task)
    {
        if (! $task->isOpenForWork() && $task->status !== 'pending_review') {
            return back()->with('error', "Task {$task->task_no} is {$task->getStatusLabel()} — it can no longer accept progress updates.");
        }

        $data = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
            'report' => 'nullable|string|max:5000',
            'challenges' => 'nullable|string|max:2000',
            'way_forward' => 'nullable|string|max:2000',
            'ready_for_review' => 'nullable|in:1,on,true',
        ]);

        $progress = (int) $data['progress'];
        $ready = ! empty($data['ready_for_review']);

        $content = trim(implode("\n\n", array_filter([
            ($data['report'] ?? null),
            ($data['challenges'] ?? null) ? "Challenges: {$data['challenges']}" : null,
            ($data['way_forward'] ?? null) ? "Way forward: {$data['way_forward']}" : null,
        ])));

        TaskUpdate::create([
            'activity_task_id' => $task->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'type' => 'report',
            'content' => $content ?: 'Progress updated.',
            'progress' => $progress,
        ]);

        $nextStatus = $ready || $progress >= 100 ? 'pending_review' : ($task->status === 'open' ? 'in_progress' : $task->status);

        $task->update([
            'progress' => $progress,
            'challenges' => $data['challenges'] ?? $task->challenges,
            'way_forward' => $data['way_forward'] ?? $task->way_forward,
            'status' => $nextStatus,
        ]);

        AuditLog::record('Task progress updated', 'Activities & Tasks', "{$task->task_no} — {$task->title} → {$progress}%");

        return back()->with('success',
            "Progress updated to {$progress}% for {$task->task_no}."
            .($ready || $progress >= 100 ? ' Submitted for review.' : '')
        );
    }

    public function updateStatus(Request $request, ActivityTask $task)
    {
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,pending_review,completed,closed,cancelled',
        ]);

        $from = $task->status;
        $to = $data['status'];

        if ($to === $from) {
            return back()->with('error', "Task {$task->task_no} is already {$task->getStatusLabel()}.");
        }

        $updates = ['status' => $to];

        if ($to === 'completed') {
            $updates['completed_at'] = now();
            $updates['progress'] = 100;
        } elseif ($to === 'closed') {
            $updates['closed_at'] = now();
            $updates['closed_by'] = auth()->user()?->name;
        }

        $task->update($updates);

        TaskUpdate::create([
            'activity_task_id' => $task->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'type' => 'status',
            'content' => 'Status changed from '.($from ? ActivityTask::statuses()[$from] ?? $from : '—').' to '.$task->getStatusLabel().'.',
            'old_value' => $from,
            'new_value' => $to,
        ]);

        AuditLog::record('Task status changed', 'Activities & Tasks', "{$task->task_no} — {$task->title} → {$task->getStatusLabel()}");

        return back()->with('success', "Task {$task->task_no} marked as {$task->getStatusLabel()}.");
    }

    public function reassign(Request $request, ActivityTask $task)
    {
        $data = $request->validate([
            'assignee_ids' => 'required|array|min:1',
            'assignee_ids.*' => 'integer|exists:users,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $assignees = User::whereIn('id', $data['assignee_ids'])->get();

        $oldIds = $task->assignees->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $newIds = array_values(array_unique(array_filter($data['assignee_ids'])));

        $task->syncAssignees($newIds);
        $task->loadMissing('assignees');
        $task->update([
            'assignee_id' => $assignees->first()?->id,
            'assignee_name' => $assignees->pluck('name')->implode(', ') ?: null,
        ]);

        TaskUpdate::create([
            'activity_task_id' => $task->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'type' => 'assignment',
            'content' => 'Task assigned to '.$assignees->pluck('name')->implode(', ').'.'
                .($data['note'] ?? '' ? " Note: {$data['note']}" : ''),
            'old_value' => null,
            'new_value' => $assignees->pluck('name')->implode(', ') ?: null,
        ]);

        $this->notifyAssignees($task, $assignees, $oldIds ? array_diff($newIds, $oldIds) : $newIds);

        AuditLog::record('Reassigned task', 'Activities & Tasks', "{$task->task_no} → ".$assignees->pluck('name')->implode(', '));

        return back()->with('success', "Task {$task->task_no} assigned to ".$assignees->pluck('name')->implode(', ').'.');
    }

    public function exportCsv(Request $request)
    {
        $status = $request->query('status');
        $priority = $request->query('priority');
        $category = $request->query('category');
        $assignee = $request->query('assignee');
        $q = trim((string) $request->query('q'));

        $query = ActivityTask::with(['event', 'assignees']);

        $query->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($priority, fn ($qr) => $qr->where('priority', $priority))
            ->when($category, fn ($qr) => $qr->where('category', $category))
            ->when($assignee, fn ($qr) => $qr->whereHas('assignees', fn ($a) => $a->where('users.id', (int) $assignee)))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('task_no', 'like', "%{$q}%")
                ->orWhere('assignee_name', 'like', "%{$q}%")));

        $rows = $query->orderBy('deadline')->get();

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Task No', 'Title', 'Category', 'Assigned To', 'Deadline', 'Priority', 'Status', 'Progress %', 'Challenges', 'Way Forward', 'Description', 'Created By']);

        foreach ($rows as $task) {
            fputcsv($csv, [
                $task->task_no,
                $task->title,
                $task->category ?? '',
                $task->assignee_name ?? 'Unassigned',
                $task->deadline?->format('Y-m-d') ?: '',
                $task->priority,
                $task->status,
                $task->progress,
                $task->challenges ?? '',
                $task->way_forward ?? '',
                $task->description ?? '',
                $task->created_by ?? '',
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=tasks-'.now()->format('Y-m-d-His').'.csv',
        ]);
    }

    /**
     * Send the Swahili assignment SMS (with a login link to report progress) to
     * each newly-assigned user who has a phone number.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $assignees
     */
    private function notifyAssignees(ActivityTask $task, $assignees, ?array $onlyIds = null): void
    {
        $sms = new SmsService();

        if (! $sms->isConfigured()) {
            return;
        }

        $event = $task->event ?? Event::currentCamp();
        foreach ($assignees as $user) {
            if ($onlyIds !== null && ! in_array((int) $user->id, array_map('intval', $onlyIds), true)) {
                continue;
            }
            if (empty($user->phone)) {
                continue;
            }

            $message = MessageTemplate::forUsage('task_assignment', [
                'name' => $user->name,
                'task' => $task->title,
                'event' => $event?->title ?? \App\Models\Setting::get('event.name', 'Open Gate Camp'),
                'link' => url('/login'),
            ]);

            if (! empty($message)) {
                $sms->send($user->phone, $message);
            }
        }
    }
}