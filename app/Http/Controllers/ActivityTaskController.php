<?php

namespace App\Http\Controllers;

use App\Models\ActivityTask;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\TaskUpdate;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityTaskController extends Controller
{
    private function committeeUsers()
    {
        return User::whereHas('role', function ($q) {
            $q->whereIn('name', ['Super Administrator', 'Chairperson', 'Secretary', 'Treasurer', 'Committee Member']);
        })->orderBy('name')->get();
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
            ->when($assignee, fn ($qr) => $qr->where('assignee_id', (int) $assignee))
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
            'assignees' => $this->committeeUsers(),
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
            'assignee_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'event_id' => 'nullable|exists:events,id',
        ]);

        $assignee = isset($data['assignee_id']) ? User::find($data['assignee_id']) : null;

        $task = ActivityTask::create($data + [
            'task_no' => ActivityTask::nextTaskNo(),
            'event_id' => $data['event_id'] ?? Event::currentCamp()?->id,
            'assignee_name' => $assignee?->name,
            'assigned_by_id' => auth()->id(),
            'status' => 'open',
            'progress' => 0,
            'created_by' => auth()->user()?->name,
        ]);

        TaskUpdate::create([
            'activity_task_id' => $task->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'type' => 'assignment',
            'content' => $assignee
                ? "Assigned to {$assignee->name}".($task->deadline ? " (deadline {$task->deadline->format('d M Y')})" : '').'.'
                : 'Created — waiting for assignee.',
            'progress' => 0,
            'new_value' => $assignee?->name,
        ]);

        AuditLog::record('Created task', 'Activities & Tasks', "{$task->task_no} — {$task->title}");

        return back()->with('success', "Task {$task->task_no} created and assigned.");
    }

    public function update(Request $request, ActivityTask $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:100',
            'assignee_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        if (isset($data['assignee_id']) && (int) $data['assignee_id'] !== $task->assignee_id) {
            $assignee = User::find($data['assignee_id']);
            TaskUpdate::create([
                'activity_task_id' => $task->id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'type' => 'assignment',
                'content' => "Task reassigned to {$assignee?->name}.",
                'old_value' => $task->assignee_name,
                'new_value' => $assignee?->name,
            ]);
            $data['assignee_name'] = $assignee?->name;
        }

        $task->update($data);
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
            'assignee_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $assignee = User::find($data['assignee_id']);

        if (! $assignee) {
            return back()->with('error', 'Selected assignee was not found.');
        }

        $task->update([
            'assignee_id' => $assignee->id,
            'assignee_name' => $assignee->name,
        ]);

        TaskUpdate::create([
            'activity_task_id' => $task->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'type' => 'assignment',
            'content' => "Task reassigned to {$assignee->name}.".($data['note'] ?? '' ? " Note: {$data['note']}" : ''),
            'old_value' => null,
            'new_value' => $assignee->name,
        ]);

        AuditLog::record('Reassigned task', 'Activities & Tasks', "{$task->task_no} → {$assignee->name}");

        return back()->with('success', "Task {$task->task_no} reassigned to {$assignee->name}.");
    }

    public function exportCsv(Request $request)
    {
        $status = $request->query('status');
        $priority = $request->query('priority');
        $category = $request->query('category');
        $assignee = $request->query('assignee');
        $q = trim((string) $request->query('q'));

        $query = ActivityTask::with('event');

        $query->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($priority, fn ($qr) => $qr->where('priority', $priority))
            ->when($category, fn ($qr) => $qr->where('category', $category))
            ->when($assignee, fn ($qr) => $qr->where('assignee_id', (int) $assignee))
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
}