@extends('layouts.app')

@section('title', 'Activities & Tasks — OpenGate Camp Connect')
@section('crumb', 'Committee / Activities & Tasks')
@section('page_title', 'Activities & Tasks')

@section('content')
@php
    $v = fn($f) => old($f, $filters[$f] ?? null);
@endphp
<div class="fade-in">
  <div class="section-head">
    <div><h2>Activities &amp; Tasks</h2><div class="sub">
      {{ $stats['total'] }} tasks · {{ $stats['in_progress'] }} in progress · {{ $stats['overdue'] }} overdue · {{ $stats['completed'] }} completed
    </div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="taskNewDrawer">+ New Activity/Task</button>
  </div>

  {{-- KPI grid --}}
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div></div>
      <div class="kpi-value">{{ $stats['total'] }}</div>
      <div class="kpi-label">Total Tasks</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--info-bg);color:var(--info)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div></div>
      <div class="kpi-value">{{ $stats['open'] }}</div>
      <div class="kpi-label">Open</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8"/><circle cx="12" cy="12" r="3"/></svg></div></div>
      <div class="kpi-value">{{ $stats['in_progress'] }}</div>
      <div class="kpi-label">In Progress</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--warning-bg);color:var(--warning)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div></div>
      <div class="kpi-value" style="color:var(--warning)">{{ $stats['pending_review'] }}</div>
      <div class="kpi-label">Pending Review</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--danger-bg);color:var(--danger)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg></div></div>
      <div class="kpi-value" style="color:var(--danger)">{{ $stats['overdue'] }}</div>
      <div class="kpi-label">Overdue</div>
      <div style="font-size:11px;color:var(--danger)">{{ $stats['urgent'] }} urgent open</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div></div>
      <div class="kpi-value" style="color:var(--success)">{{ $stats['completed'] }}</div>
      <div class="kpi-label">Completed</div>
      <div style="font-size:11px;color:var(--purple)">{{ $stats['closed'] }} closed</div>
    </div>
  </div>

  <form class="toolbar" method="GET" action="{{ route('activities.index') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ $v('q') }}" placeholder="Search by task, no, assignee..."></div>
    <select class="filter-select" name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      @foreach($statuses as $k=>$s)<option value="{{ $k }}" {{ $v('status')===$k ? 'selected' : '' }}>{{ $s }}</option>@endforeach
    </select>
    <select class="filter-select" name="priority" onchange="this.form.submit()">
      <option value="">All Priorities</option>
      @foreach($priorities as $k=>$p)<option value="{{ $k }}" {{ $v('priority')===$k ? 'selected' : '' }}>{{ $p }}</option>@endforeach
    </select>
    <select class="filter-select" name="category" onchange="this.form.submit()">
      <option value="">All Categories</option>
      @foreach($categories as $k=>$c)<option value="{{ $k }}" {{ $v('category')===$k ? 'selected' : '' }}>{{ $c }}</option>@endforeach
    </select>
    <select class="filter-select" name="assignee" onchange="this.form.submit()">
      <option value="">All Assignees</option>
      @foreach($assignees as $u)<option value="{{ $u->id }}" {{ $v('assignee')==(string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>@endforeach
    </select>
    <a class="btn btn-secondary btn-sm" href="{{ route('activities.export', request()->query()) }}">Export</a>
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Task</th><th>Assigned To</th><th>Deadline</th><th>Priority</th><th>Progress</th><th>Status</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($tasks as $t)
          @php
            $updatesArr = $t->updates->sortBy('created_at')->map(fn($u) => [
                'type'     => $u->type,
                'label'    => $u->getTypeLabel(),
                'content'  => $u->content,
                'user'     => $u->user_name,
                'date'     => $u->created_at?->format('d M Y H:i'),
                'progress' => $u->progress,
            ])->values()->all();
            $updatesJson = json_encode($updatesArr, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
          @endphp
          <tr style="cursor:pointer" data-view-task
            data-id="{{ $t->id }}"
            data-no="{{ $t->task_no }}"
            data-title="{{ $t->title }}"
            data-category="{{ $t->category ?? '—' }}"
            data-description="{{ $t->description ?? '' }}"
            data-assignee="{{ $t->assignee_name ?? 'Unassigned' }}"
            data-assigned-by="{{ $t->assignedBy?->name ?? '—' }}"
            data-deadline="{{ $t->deadline?->format('d M Y') ?? '—' }}"
            data-event="{{ $t->event?->title ?? '—' }}"
            data-priority="{{ $t->getPriorityLabel() }}"
            data-priority-key="{{ $t->priority }}"
            data-status="{{ $t->getStatusLabel() }}"
            data-status-key="{{ $t->status }}"
            data-progress="{{ $t->progress }}"
            data-challenges="{{ $t->challenges ?? '' }}"
            data-way-forward="{{ $t->way_forward ?? '' }}"
            data-created="{{ $t->created_by ?? '—' }}"
            data-overdue="{{ $t->is_overdue ? '1' : '0' }}"
            data-updates="{{ $updatesJson }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:var(--info-bg);color:var(--info)">{{ collect(explode(' ', $t->title ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div><div class="cu-name">{{ $t->title }}</div><div class="cu-sub"><span class="badge badge-neutral badge-dotted">{{ $t->task_no }}</span> {{ $t->category ?? '' }}</div></div>
              </div>
            </td>
            <td>
              <div class="cu-name" style="font-size:13px">{{ $t->assignee_name ?? '—' }}</div>
              @if($t->assignee)
              <div class="cu-sub">{{ $t->assignee->role?->name ?? '' }}</div>
              @endif
            </td>
            <td>
              <span style="font-weight:600">{{ $t->deadline?->format('d M') ?? '—' }}</span>
              @if($t->is_overdue)<span class="badge badge-danger badge-dotted" style="margin-left:4px">Overdue</span>@endif
            </td>
            <td><span class="badge badge-{{ $t->getPriorityColor() }}">{{ $t->getPriorityLabel() }}</span></td>
            <td style="min-width:110px">
              <div style="display:flex;align-items:center;gap:8px">
                <div class="dp-track" style="width:70px;height:6px"><div class="dp-fill" style="width:{{ $t->progress }}%;background:{{ $t->progress >= 100 ? 'var(--success)' : ($t->progress > 0 ? 'var(--accent)' : '#cbd5e1') }}"></div></div>
                <span style="font-size:12px;font-weight:700;color:{{ $t->progress >= 100 ? 'var(--success)' : 'var(--text-secondary)' }}">{{ $t->progress }}%</span>
              </div>
            </td>
            <td><span class="badge badge-{{ $t->getStatusColor() }} badge-dotted">{{ $t->getStatusLabel() }}</span></td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-task-{{ $t->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-task-{{ $t->id }}">
                  <button type="button" data-open-progress data-id="{{ $t->id }}" data-title="{{ $t->title }}" data-progress="{{ $t->progress }}" data-status="{{ $t->status }}">Update Progress</button>
                  <button type="button" data-open-reassign data-id="{{ $t->id }}" data-title="{{ $t->title }}" data-assignee="{{ $t->assignee_id ?? '' }}">Reassign Task</button>
                  @if(!$isCommittee)
                  <button type="button" data-edit-task data-id="{{ $t->id }}" data-title="{{ $t->title }}" data-description="{{ $t->description ?? '' }}" data-category="{{ $t->category ?? '' }}" data-assignee="{{ $t->assignee_id ?? '' }}" data-deadline="{{ $t->deadline?->format('Y-m-d') ?? '' }}" data-priority="{{ $t->priority }}">Edit</button>
                  <form method="POST" action="{{ route('activities.destroy', $t) }}" data-confirm
                        data-confirm-title="Delete this task?"
                        data-confirm-message="{{ $t->task_no }} — {{ $t->title }} will be permanently removed."
                        data-confirm-label="Delete Task">@csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state" style="padding:40px 20px"><h3>No activities/tasks yet</h3><p>Create your first committee task and assign it to a member.</p><button type="button" class="btn btn-accent" data-drawer-open="taskNewDrawer">+ New Activity/Task</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $tasks->firstItem() ?? 0 }}–{{ $tasks->lastItem() ?? 0 }} of {{ $tasks->total() }} tasks</span>
      <div class="pagination">{{ $tasks->links() }}</div>
    </div>
  </div>
</div>

{{-- Create drawer --}}
<div class="drawer-overlay" id="taskNewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>New Activity / Task</h3><p>Assign against {{ $campEvent?->title ?? \App\Models\Setting::get('event.name', 'Open Gate Camp') }}</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('activities.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Task Title *</label><input name="title" placeholder="e.g. Find accommodation for invited facilitators" value="{{ old('title') }}" required></div>
          <div class="field"><label>Category</label><select name="category">
            <option value="">Select category…</option>
            @foreach($categories as $k=>$c)<option value="{{ $k }}" {{ old('category')===$k ? 'selected' : '' }}>{{ $c }}</option>@endforeach
          </select></div>
          <div class="field"><label>Assign To</label><select name="assignee_id">
            <option value="">— Unassigned —</option>
            @foreach($assignees as $u)<option value="{{ $u->id }}" {{ old('assignee_id')==(string)$u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->role?->name ?? 'User' }})</option>@endforeach
          </select></div>
          <div class="field"><label>Deadline</label><input type="date" name="deadline" value="{{ old('deadline') }}"></div>
          <div class="field"><label>Priority</label><select name="priority">
            @foreach($priorities as $k=>$p)<option value="{{ $k }}" {{ old('priority', 'medium')===$k ? 'selected' : '' }}>{{ $p }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Description / Instructions</label><textarea name="description" rows="3" placeholder="What needs to be done?">{{ old('description') }}</textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Create &amp; Assign Task</button>
      </div>
    </form>
  </div>
</div>

{{-- Progress update drawer --}}
<div class="drawer-overlay" id="taskProgressDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Update Progress</h3><p id="progressTaskTitle">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="progressForm">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Progress (%) *</label><input type="number" name="progress" id="progressValue" min="0" max="100" value="0" required></div>
          <div class="field"><label>Current Status</label><div style="background:var(--blue-light);color:var(--blue-accent);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:700" id="progressTaskStatus">—</div></div>
          <div class="field full"><label>Progress Report</label><textarea name="report" rows="3" placeholder="e.g. 3 hotels contacted; 2 quotations received."></textarea></div>
          <div class="field full"><label>Challenges</label><textarea name="challenges" rows="2" placeholder="Anything blocking the work?"></textarea></div>
          <div class="field full"><label>Way Forward</label><textarea name="way_forward" rows="2" placeholder="Plan / next steps to complete the task"></textarea></div>
          <div class="field full"><label style="display:flex;align-items:center;gap:8px;font-weight:600;cursor:pointer"><input type="checkbox" name="ready_for_review" value="1" style="width:auto"> Submit for review (leader approval)</label></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Progress</button>
      </div>
    </form>
  </div>
</div>

{{-- Edit drawer --}}
<div class="drawer-overlay" id="taskEditDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Edit Task</h3><p>Update task details</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="editTaskForm">
      @csrf @method('PUT')
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Task Title *</label><input name="title" id="editTaskTitle" required></div>
          <div class="field"><label>Category</label><select name="category" id="editTaskCategory">
            <option value="">Select category…</option>
            @foreach($categories as $k=>$c)<option value="{{ $k }}">{{ $c }}</option>@endforeach
          </select></div>
          <div class="field"><label>Assign To</label><select name="assignee_id" id="editTaskAssignee">
            <option value="">— Unassigned —</option>
            @foreach($assignees as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role?->name ?? 'User' }})</option>@endforeach
          </select></div>
          <div class="field"><label>Deadline</label><input type="date" name="deadline" id="editTaskDeadline"></div>
          <div class="field"><label>Priority</label><select name="priority" id="editTaskPriority">
            @foreach($priorities as $k=>$p)<option value="{{ $k }}">{{ $p }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Description / Instructions</label><textarea name="description" id="editTaskDescription" rows="3"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- Reassign drawer --}}
<div class="drawer-overlay" id="taskReassignDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Reassign Task</h3><p id="reassignTaskTitle">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="reassignForm">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Assign To (Committee Member) *</label><select name="assignee_id" id="reassignAssignee" required>
            <option value="">Select member…</option>
            @foreach($assignees as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role?->name ?? 'User' }})</option>@endforeach
          </select></div>
          <div class="field full"><label>Note (optional)</label><textarea name="note" rows="2" placeholder="Reason for reassignment"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Reassign Task</button>
      </div>
    </form>
  </div>
</div>

{{-- Detail drawer --}}
<div class="drawer-overlay" id="taskDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div>
        <h3>Task Details</h3>
        <p style="color:var(--text-tertiary)"><span id="drawerNo2" class="badge badge-neutral badge-dotted">—</span> <span id="drawerCategory" class="badge badge-dotted" style="background:var(--blue-light);color:var(--blue-accent)">—</span></p>
      </div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="cell-avatar avatar-lg" id="drawerTaskAvatar" style="background:var(--info-bg);color:var(--info)">—</div>
        <div>
          <div class="cu-name" id="drawerTaskTitle" style="font-size:16px">—</div>
          <span>
            <span class="badge badge-dotted" id="drawerTaskStatus">—</span>
            <span class="badge badge-dotted" id="drawerTaskPriority">—</span>
          </span>
        </div>
      </div>

      <div class="drawer-progress">
        <div class="dp-row"><span>Progress</span><b id="drawerTaskProgress">0%</b></div>
        <div class="dp-track"><div class="dp-fill" id="drawerTaskFill" style="width:0%"></div></div>
      </div>

      <div class="info-grid">
        <div class="info-row"><span>Assigned To</span><b id="drawerTaskAssignee">—</b></div>
        <div class="info-row"><span>Assigned By</span><b id="drawerTaskAssignedBy">—</b></div>
        <div class="info-row"><span>Deadline</span><b id="drawerTaskDeadline">—</b></div>
        <div class="info-row"><span>Event</span><b id="drawerTaskEvent">—</b></div>
        <div class="info-row"><span>Created By</span><b id="drawerTaskCreated">—</b></div>
        <div class="info-row"><span>Next Review</span><b id="drawerTaskHint">—</b></div>
        <div class="info-row full" id="drawerTaskDescRow" style="display:none"><span>Description</span><b id="drawerTaskDesc" style="white-space:pre-wrap;font-weight:500">—</b></div>
        <div class="info-row full" id="drawerChallengesRow" style="display:none"><span>Challenges</span><b id="drawerTaskChallenges" style="white-space:pre-wrap;font-weight:500;color:var(--warning)">—</b></div>
        <div class="info-row full" id="drawerWayRow" style="display:none"><span>Way Forward</span><b id="drawerTaskWay" style="white-space:pre-wrap;font-weight:500;color:var(--success)">—</b></div>
      </div>

      <div class="drawer-status-actions" id="drawerTaskActions"></div>

      <div class="payments-head">
        <span>Task History</span>
        <span class="payments-count" id="drawerTaskCount">0</span>
      </div>
      <div id="drawerTaskUpdates" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-accent" id="drawerProgressBtn">Update Progress</button>
      <button type="button" class="btn" id="drawerReassignBtn" style="border:1px solid var(--border-strong)">Reassign</button>
    </div>
  </div>
</div>

<style>
.drawer-status-actions{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 4px;}
.drawer-status-actions form{display:contents;}
.drawer-status-actions button{border:1px solid var(--border-strong);background:var(--card-bg);color:var(--text-secondary);border-radius:9px;padding:8px 12px;font-size:12.5px;font-weight:700;cursor:pointer;transition:all .15s;}
.drawer-status-actions button:hover{border-color:var(--accent);color:var(--accent);}
.drawer-status-actions button.st-primary{background:var(--accent);border-color:var(--accent);color:#fff;}
.drawer-status-actions button.st-primary:hover{background:var(--accent-dark);color:#fff;}
.drawer-status-actions button.st-danger{background:var(--danger-bg);border-color:var(--danger);color:var(--danger);}
</style>
@endsection

@push('scripts')
<script>
function decodeEntities(s){
  if(!s || s.indexOf('&') === -1) return s;
  var ta = document.createElement('textarea');
  ta.innerHTML = s;
  return ta.value;
}
document.addEventListener('DOMContentLoaded', function(){
  var statusColor = {open:'neutral', in_progress:'info', pending_review:'warning', completed:'success', closed:'purple', cancelled:'danger'};
  var priorityColor = {low:'neutral', medium:'info', high:'warning', urgent:'danger'};
  var statusActions = {
    open:          [['in_progress','Start Work'],['pending_review','Submit for Review']],
    in_progress:   [['pending_review','Submit for Review'],['completed','Mark Completed']],
    pending_review:[['completed','Approve & Complete'],['in_progress','Send Back to Work'],['closed','Close Task']],
    completed:     [['in_progress','Reopen'],['closed','Close Task']],
    closed:        [['open','Reopen Task']],
    cancelled:     [['open','Reopen Task']]
  };

  function renderUpdates(listEl, options){
    listEl.innerHTML = '';
    options.forEach(function(u){
      var icons = {
        report: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>',
        status: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 3"/><circle cx="12" cy="12" r="9"/></svg>',
        assignment: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>'
      };
      var item = document.createElement('div');
      item.className = 'pay-item';
      var contentHtml = u.content ? '<div style="margin-top:4px;white-space:pre-wrap;font-size:12.5px;line-height:1.5;color:var(--text-secondary)">'+decodeEntities(u.content)+'</div>' : '';
      item.innerHTML =
        '<div class="pay-ico">' + (icons[u.type] || icons.report) + '</div>' +
        '<div class="pay-main"><div class="pm-name">' + (u.label||'Update') + (u.progress != null ? ' · ' + u.progress + '%' : '') + '</div>' +
        '<div class="pm-sub">' + (u.user||'—') + (u.date ? ' · ' + u.date : '') + '</div>' + contentHtml + '</div>';
      listEl.appendChild(item);
    });
  }

  function renderStatusActions(wrap, key, id){
    wrap.innerHTML = '';
    (statusActions[key] || []).forEach(function(pair){
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = "{{ url('/activities-tasks') }}/" + id + "/status";
      var csrf = document.createElement('input');
      csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
      var st = document.createElement('input');
      st.type = 'hidden'; st.name = 'status'; st.value = pair[0];
      var btn = document.createElement('button');
      btn.type = 'submit';
      btn.className = (pair[0] === 'completed') ? 'st-primary' : (pair[0] === 'closed' ? 'st-danger' : '');
      btn.textContent = pair[1];
      form.appendChild(csrf); form.appendChild(st); form.appendChild(btn);
      wrap.appendChild(form);
    });
  }

  document.querySelectorAll('[data-view-task]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('.action-menu-wrap') || e.target.closest('form') || e.target.closest('button') || e.target.closest('a')) return;
      var d = tr.dataset;
      var initials = (d.title||'?').trim().split(' ').map(function(w){return w.charAt(0);}).slice(0,2).join('');
      document.getElementById('drawerTaskAvatar').textContent = initials;
      document.getElementById('drawerNo2').textContent = d.no;
      document.getElementById('drawerTaskTitle').textContent = d.title || '—';
      document.getElementById('drawerCategory').textContent = d.category || '—';

      var st = document.getElementById('drawerTaskStatus');
      st.textContent = d.status || '—';
      st.className = 'badge badge-' + (statusColor[d.statusKey]||'neutral') + ' badge-dotted';
      var pr = document.getElementById('drawerTaskPriority');
      pr.textContent = d.priority || '—';
      pr.className = 'badge badge-' + (priorityColor[d.priorityKey]||'neutral') + ' badge-dotted';

      var pg = Number(d.progress||0);
      document.getElementById('drawerTaskProgress').textContent = pg + '%';
      document.getElementById('drawerTaskProgress').style.color = pg >= 100 ? 'var(--success)' : '';
      document.getElementById('drawerTaskFill').style.width = pg + '%';
      document.getElementById('drawerTaskFill').style.background = pg >= 100 ? 'var(--success)' : '';

      document.getElementById('drawerTaskAssignee').textContent = d.assignee || '—';
      document.getElementById('drawerTaskAssignedBy').textContent = d.assignedBy || '—';
      document.getElementById('drawerTaskDeadline').textContent = d.deadline || '—';
      document.getElementById('drawerTaskDeadline').style.color = d.overdue === '1' ? 'var(--danger)' : '';
      document.getElementById('drawerTaskEvent').textContent = d.event || '—';
      document.getElementById('drawerTaskCreated').textContent = d.created || '—';
      document.getElementById('drawerTaskHint').textContent = d.statusKey === 'pending_review' ? 'Awaiting leader review' : (d.overdue === '1' ? 'Overdue' : '—');

      var desc = decodeEntities(d.description || '');
      document.getElementById('drawerTaskDescRow').style.display = desc ? '' : 'none';
      document.getElementById('drawerTaskDesc').textContent = desc || '—';
      var ch = decodeEntities(d.challenges || '');
      document.getElementById('drawerChallengesRow').style.display = ch ? '' : 'none';
      document.getElementById('drawerTaskChallenges').textContent = ch || '—';
      var wf = decodeEntities(d.wayForward || '');
      document.getElementById('drawerWayRow').style.display = wf ? '' : 'none';
      document.getElementById('drawerTaskWay').textContent = wf || '—';

      renderStatusActions(document.getElementById('drawerTaskActions'), d.statusKey, d.id);

      var updates = [];
      try { updates = JSON.parse(decodeEntities(d.updates || '[]')); } catch(err){ updates = []; }
      renderUpdates(document.getElementById('drawerTaskUpdates'), updates);
      document.getElementById('drawerTaskCount').textContent = updates.length;

      document.getElementById('drawerProgressBtn').dataset.id = d.id;
      document.getElementById('drawerProgressBtn').dataset.title = d.title;
      document.getElementById('drawerProgressBtn').dataset.progress = pg;
      document.getElementById('drawerProgressBtn').dataset.status = d.statusKey;
      document.getElementById('drawerReassignBtn').dataset.id = d.id;
      document.getElementById('drawerReassignBtn').dataset.title = d.title;
      document.getElementById('drawerReassignBtn').dataset.assignee = d.assignee || '';

      openDrawerById('taskDetailDrawer');
    });
  });

  function openProgress(d){
    document.getElementById('progressTaskTitle').textContent = d.title || '—';
    var st = document.getElementById('progressTaskStatus');
    st.textContent = d.status ? 'Current: ' + (d.status.replace(/_/g,' ')) : '—';
    document.getElementById('progressValue').value = d.progress || 0;
    document.getElementById('progressForm').action = "{{ url('/activities-tasks') }}/" + d.id + "/report";
    if(document.getElementById('taskDetailDrawer').classList.contains('open')) closeDrawerById('taskDetailDrawer');
    openDrawerById('taskProgressDrawer');
  }

  document.querySelectorAll('[data-open-progress]').forEach(function(btn){
    btn.addEventListener('click', function(){ openProgress(btn.dataset); });
  });
  document.getElementById('drawerProgressBtn').addEventListener('click', function(){
    openProgress(this.dataset);
  });

  function openReassign(d){
    document.getElementById('reassignTaskTitle').textContent = d.title || '—';
    document.getElementById('reassignAssignee').value = d.assignee || '';
    document.getElementById('reassignForm').action = "{{ url('/activities-tasks') }}/" + d.id + "/reassign";
    if(document.getElementById('taskDetailDrawer').classList.contains('open')) closeDrawerById('taskDetailDrawer');
    openDrawerById('taskReassignDrawer');
  }

  document.querySelectorAll('[data-open-reassign]').forEach(function(btn){
    btn.addEventListener('click', function(){ openReassign(btn.dataset); });
  });
  document.getElementById('drawerReassignBtn').addEventListener('click', function(){
    openReassign(this.dataset);
  });

  document.querySelectorAll('[data-edit-task]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('editTaskTitle').value = d.title || '';
      document.getElementById('editTaskCategory').value = d.category || '';
      document.getElementById('editTaskAssignee').value = d.assignee || '';
      document.getElementById('editTaskDeadline').value = d.deadline || '';
      document.getElementById('editTaskPriority').value = d.priority || 'medium';
      document.getElementById('editTaskDescription').value = decodeEntities(d.description || '');
      document.getElementById('editTaskForm').action = "{{ url('/activities-tasks') }}/" + d.id;
      openDrawerById('taskEditDrawer');
    });
  });
});
</script>
@endpush