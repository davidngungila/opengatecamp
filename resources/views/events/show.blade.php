@extends('layouts.app')

@section('title', $event->title.' — Open Gate Camp Mission')
@section('crumb', 'Events / '.$event->title)
@section('page_title', $event->title)

@php
    $typeColors = [
        'camp' => ['var(--purple-bg)', 'var(--purple)'],
        'conference' => ['var(--blue-light)', 'var(--blue-accent)'],
        'mission_trip' => ['var(--success-bg)', 'var(--success)'],
        'training' => ['var(--info-bg)', 'var(--info)'],
        'worship' => ['var(--warning-bg)', 'var(--warning)'],
        'other' => ['#F1F5F9', 'var(--text-secondary)'],
    ];
    $tc = $typeColors[$event->event_type] ?? ['#F1F5F9', 'var(--text-secondary)'];
@endphp

@section('content')
<div class="fade-in">
  {{-- Header --}}
  <div class="solid-card" style="margin-bottom:18px">
    <div class="flex" style="align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div class="flex" style="align-items:center;gap:14px">
        <div class="ec-ico" style="background:{{ $tc[0] }};color:{{ $tc[1] }};width:52px;height:52px">@include('partials.event-icon', ['type' => $event->event_type, 'size' => 26])</div>
        <div>
          <div class="flex gap-8" style="align-items:center;flex-wrap:wrap">
            <h2 style="margin:0;font-size:20px">{{ $event->title }}</h2>
            <span class="badge badge-{{ $event->getStatusColor() }} badge-dotted">{{ $event->getStatusLabel() }}</span>
            <span class="badge badge-{{ $event->getTypeColor() }}">{{ $event->getTypeLabel() }}</span>
          </div>
          <div class="ec-sub" style="margin-top:6px">
            {{ $event->start_date?->format('d M Y') }}
            @if($event->end_date && $event->end_date->ne($event->start_date)) – {{ $event->end_date->format('d M Y') }}@endif
            @if($event->start_time) · {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}@endif
            · {{ $event->venue ?? 'Venue TBD' }}
            @if($event->location)· {{ $event->location }}@endif
          </div>
          <p class="text-muted" style="margin:8px 0 0;max-width:720px">{{ $event->description }}</p>
        </div>
      </div>
      <div class="flex gap-8" style="flex-wrap:wrap">
        <button type="button" class="btn btn-secondary btn-sm" data-modal-open="editEventModal">Edit</button>
        <form method="POST" action="{{ route('events.status', $event) }}" style="display:inline">
          @csrf @method('PATCH')
          <select name="status" class="filter-select" onchange="this.form.submit()" style="min-width:150px">
            @foreach($eventStatuses as $k => $s)
              <option value="{{ $k }}" {{ $event->status===$k ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
          </select>
        </form>
        <form method="POST" action="{{ route('events.destroy', $event) }}" data-confirm
              data-confirm-title="Delete this event?"
              data-confirm-message="{{ $event->title }} and all its attendees/pledges will be permanently removed."
              data-confirm-label="Delete Event" style="display:inline">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
    </div>
  </div>

  {{-- Stats --}}
  <div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:18px">
    <div class="kpi-card"><div class="kpi-label">Registered</div><div class="kpi-value" style="font-size:24px">{{ $stats['registered'] }}</div><div class="kpi-trend up">@if($event->capacity) of {{ $event->capacity }} @endif slots</div></div>
    <div class="kpi-card"><div class="kpi-label">Confirmed</div><div class="kpi-value" style="font-size:24px;color:var(--info)">{{ $stats['confirmed'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Attended / Checked-in</div><div class="kpi-value" style="font-size:24px;color:var(--success)">{{ $stats['attended'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Pledged (TZS)</div><div class="kpi-value" style="font-size:22px">{{ number_format($stats['pledged']) }}</div><div class="kpi-trend up">Paid {{ number_format($stats['pledgedPaid']) }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Registration Fee</div><div class="kpi-value" style="font-size:22px">{{ $event->registration_fee ? 'TZS '.number_format($event->registration_fee) : 'Free' }}</div><div class="kpi-trend">{{ $event->organizer ? 'By '.$event->organizer : '' }}</div></div>
  </div>

  <div class="tabs-bar" style="margin-bottom:16px" id="eventTabsBar">
    <button type="button" class="tab-btn active" data-tab-target="evtPane-attendees" data-tab-group="eventTabs">Attendees</button>
    <button type="button" class="tab-btn" data-tab-target="evtPane-pledges" data-tab-group="eventTabs">Pledges</button>
    <button type="button" class="tab-btn" data-tab-target="evtPane-sessions" data-tab-group="eventTabs">Sessions / Agenda</button>
  </div>

  {{-- Attendees pane --}}
  <div id="evtPane-attendees" data-tab-pane="eventTabs">
    <div class="section-head" style="margin-bottom:12px">
      <div><h2>Attendees &amp; Registrations</h2><div class="sub">Register, confirm and check in attendees</div></div>
      <div class="flex gap-8">
        <button type="button" class="btn btn-secondary" onclick="toast('Export started','success')">Export</button>
        <button type="button" class="btn btn-accent" data-modal-open="attendeeModal">+ Register Attendee</button>
      </div>
    </div>

    <div class="table-card">
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th>Attendee</th><th>Contact</th><th>Member</th><th>Fee Paid (TZS)</th><th>Method</th><th>Status</th><th>Checked In</th><th style="width:130px">Actions</th></tr></thead>
          <tbody>
            @forelse($attendees as $a)
            <tr>
              <td>
                <div class="cell-user">
                  <div class="cell-avatar">{{ collect(explode(' ', $a->name ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                  <div><div class="cu-name">{{ $a->name ?? '—' }}</div><div class="cu-sub">Reg {{ $a->registered_on?->format('d M') ?? '—' }}</div></div>
                </div>
              </td>
              <td><div class="cu-sub">{{ $a->phone ?? '—' }}<br>{{ $a->email ?? '' }}</div></td>
              <td>{{ $a->member?->member_no ?? '—' }}</td>
              <td>{{ number_format($a->amount_paid) }}</td>
              <td>{{ ucfirst($a->payment_method ?? '—') }}</td>
              <td>
                <form method="POST" action="{{ route('events.attendees.update', [$event, $a]) }}" id="attStatus-{{ $a->id }}">
                  @csrf @method('PUT')
                  <select name="status" class="filter-select" style="min-width:110px;padding:3px 8px;font-size:11.5px" onchange="document.getElementById('attStatus-{{ $a->id }}').submit()">
                    @foreach($attendees->count() ? \App\Models\EventAttendee::statuses() : [] as $k => $s)
                      <option value="{{ $k }}" {{ $a->status===$k ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                  </select>
                </form>
              </td>
              <td>
                @if($a->checked_in_at)
                  <span class="badge badge-success badge-dotted">Checked in</span>
                  <div class="cu-sub">{{ $a->checked_in_by }}</div>
                @else
                  <span class="badge badge-neutral badge-dotted">—</span>
                @endif
              </td>
              <td>
                <div class="action-menu-wrap">
                  <button type="button" class="action-trigger" onclick="toggleActionMenu('am-att-{{ $a->id }}')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                  </button>
                  <div class="action-menu" id="am-att-{{ $a->id }}">
                    <button type="button" data-edit-attendee data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-phone="{{ $a->phone }}" data-email="{{ $a->email }}" data-status="{{ $a->status }}" data-amount="{{ $a->amount_paid }}" data-method="{{ $a->payment_method }}" data-notes="{{ $a->notes }}">Edit Status / Payment</button>
                    <form method="POST" action="{{ route('events.attendees.destroy', [$event, $a]) }}" data-confirm
                          data-confirm-title="Remove attendee?"
                          data-confirm-message="{{ $a->name }} will be removed from this event."
                          data-confirm-label="Remove">@csrf @method('DELETE')
                      <button type="submit" class="danger">Remove</button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="8"><div class="empty-state" style="padding:40px 20px"><h3>No attendees yet</h3><p>Register the first attendee for {{ $event->title }}.</p><button type="button" class="btn btn-accent" data-modal-open="attendeeModal">+ Register Attendee</button></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span class="tf-info">Showing {{ $attendees->firstItem() ?? 0 }}–{{ $attendees->lastItem() ?? 0 }} of {{ $attendees->total() }} attendees</span>
        <div class="pagination">{{ $attendees->links() }}</div>
      </div>
    </div>
  </div>

  {{-- Pledges pane --}}
  <div id="evtPane-pledges" data-tab-pane="eventTabs" class="hidden">
    <div class="section-head" style="margin-bottom:12px">
      <div><h2>Event Pledges</h2><div class="sub">TZS {{ number_format($stats['pledged']) }} pledged · {{ number_format($stats['pledgedPaid']) }} paid</div></div>
      <button type="button" class="btn btn-accent" data-modal-open="eventPledgeModal">+ Record Pledge</button>
    </div>
    <div class="table-card">
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th>Pledge</th><th>Pledger</th><th>Amount (TZS)</th><th>Paid (TZS)</th><th>Balance</th><th>Status</th><th>Recorded</th></tr></thead>
          <tbody>
            @forelse($pledges as $pl)
            <tr>
              <td><span class="badge badge-neutral badge-dotted">{{ $pl->pledge_no }}</span></td>
              <td><div class="cell-user"><div class="cell-avatar">{{ collect(explode(' ', $pl->name ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div><div><div class="cu-name">{{ $pl->name ?? '—' }}</div><div class="cu-sub">{{ $pl->phone ?? ($pl->email ?? '') }}</div></div></div></td>
              <td>{{ number_format($pl->amount) }}</td>
              <td>{{ number_format($pl->paid_amount) }}</td>
              <td><b>{{ number_format($pl->getRemainingAttribute()) }}</b></td>
              <td><span class="badge badge-{{ $pl->getStatusColor() }} badge-dotted">{{ $pl->getStatusLabel() }}</span></td>
              <td>{{ $pl->pledge_date?->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="empty-state" style="padding:40px 20px"><h3>No pledges yet</h3><p>Record pledges to track campaign giving for this event.</p><button type="button" class="btn btn-accent" data-modal-open="eventPledgeModal">+ Record Pledge</button></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Sessions pane --}}
  <div id="evtPane-sessions" data-tab-pane="eventTabs" class="hidden">
    <div class="section-head" style="margin-bottom:12px">
      <div><h2>Sessions &amp; Agenda</h2><div class="sub">Programme for {{ $event->title }}</div></div>
      <button type="button" class="btn btn-accent" data-modal-open="sessionModal">+ Add Session</button>
    </div>
    <div class="card-grid" style="grid-template-columns:repeat(2,1fr)">
      @forelse($event->sessions as $s)
      <div class="entity-card">
        <div class="ec-top">
          <div class="ec-ico" style="background:var(--blue-light);color:var(--blue-accent)">▶</div>
          <div>
            <h4>{{ $s->title }}</h4>
            <div class="ec-sub">{{ $s->session_date?->format('d M Y') }}{{ $s->start_time ? ' · '.\Carbon\Carbon::parse($s->start_time)->format('H:i') : '' }} {{ $s->venue ? '· '.$s->venue : '' }}</div>
          </div>
        </div>
        @if($s->speaker || $s->facilitator)<div class="ec-sub" style="margin-top:8px">{{ $s->speaker ? 'Speaker: '.$s->speaker : '' }} {{ $s->facilitator ? '· Facilitator: '.$s->facilitator : '' }}</div>@endif
        @if($s->description)<p class="text-muted" style="margin:8px 0 0">{{ $s->description }}</p>@endif
        <div style="margin-top:12px">
          <form method="POST" action="{{ route('events.sessions.destroy', [$event, $s]) }}" data-confirm
                data-confirm-title="Remove session?"
                data-confirm-message="'{{ $s->title }}' will be removed from the agenda."
                data-confirm-label="Remove">@csrf @method('DELETE')
            <button type="submit" class="btn btn-ghost btn-sm danger">Remove</button>
          </form>
        </div>
      </div>
      @empty
      <div style="grid-column:1/-1"><div class="empty-state" style="padding:40px 20px"><h3>No sessions yet</h3><p>Add sessions to build the event programme.</p><button type="button" class="btn btn-accent" data-modal-open="sessionModal">+ Add Session</button></div></div>
      @endforelse
    </div>
  </div>
</div>

{{-- Modal: Register attendee --}}
<div class="modal-overlay" id="attendeeModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Register Attendee</h3><p>{{ $event->title }}</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('events.attendees.store', $event) }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field"><label>Full Name</label><input name="name" id="attendeeName" placeholder="Attendee name" value="{{ old('name') }}" required></div>
          <div class="field"><label>Phone</label><input name="phone" id="attendeePhone" placeholder="+255 7XX XXX XXX" value="{{ old('phone') }}" required></div>
          <div class="field full"><label>Email</label><input name="email" id="attendeeEmail" placeholder="email@example.com" value="{{ old('email') }}"></div>
          <div class="field"><label>Amount Paid (TZS)</label><input type="number" step="0.01" name="amount_paid" value="{{ old('amount_paid', $event->registration_fee) }}"></div>
          <div class="field"><label>Fee Amount (TZS)</label><input type="number" name="fee_amount" value="{{ old('fee_amount', ($event->registration_fee > 0 ? $event->registration_fee : 10000)) }}" readonly style="background:var(--blue-light);font-weight:700;color:var(--navy-900)"></div>
          <div class="field"><label>Payment Method</label><select name="payment_method"><option value="" disabled>Select</option><option>cash</option><option>bank</option><option>mobile</option></select></div>
          <div class="field"><label>Pickup Location</label><select name="pickup_location" required>
            <option value="">— Select —</option>
            <option value="arusha" @if(old('pickup_location')==='arusha') selected @endif>Arusha</option>
            <option value="moshi" @if(old('pickup_location')==='moshi') selected @endif>Moshi</option>
          </select></div>
          <div class="field"><label>Status</label><select name="status"><option value="pending">Pending</option><option value="confirmed">Confirmed</option><option value="attended">Attended</option><option value="no_show">No Show</option><option value="cancelled">Cancelled</option></select></div>
          <div class="field full"><label>Notes</label><textarea name="notes" placeholder="Dietary, accessibility, transport notes..."></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Register Attendee</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: Edit attendee --}}
<div class="modal-overlay" id="editAttendeeModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Update Attendee</h3><p>Change status or payment details</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="editAttendeeForm">
      @csrf @method('PUT')
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><h4 id="editAttendeeName" style="margin:0">—</h4></div>
          <div class="field"><label>Status</label><select name="status" id="editAttendeeStatus">
            @foreach(\App\Models\EventAttendee::statuses() as $k => $s)<option value="{{ $k }}">{{ $s }}</option>@endforeach
          </select></div>
          <div class="field"><label>Amount Paid (TZS)</label><input type="number" step="0.01" name="amount_paid" id="editAttendeeAmount"></div>
          <div class="field"><label>Payment Method</label><select name="payment_method" id="editAttendeeMethod"><option value="">—</option><option>cash</option><option>bank</option><option>mobile</option></select></div>
          <div class="field full"><label>Notes</label><textarea name="notes" id="editAttendeeNotes"></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: Record pledge --}}
<div class="modal-overlay" id="eventPledgeModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Record Pledge</h3><p>Against {{ $event->title }}</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('pledges.store') }}">
      @csrf
      <input type="hidden" name="event_id" value="{{ $event->id }}">
      <div class="modal-body">
        <div class="form-grid">
          <div class="field"><label>Name</label><input name="name" placeholder="Full name" value="{{ old('name') }}" required></div>
          <div class="field"><label>Phone</label><input name="phone" placeholder="+255 7XX XXX XXX" value="{{ old('phone') }}"></div>
          <div class="field full"><label>Email</label><input name="email" placeholder="email@example.com" value="{{ old('email') }}"></div>
          <div class="field"><label>Amount (TZS)</label><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required></div>
          <div class="field"><label>Frequency</label><select name="frequency"><option value="one_time">One-time</option><option value="monthly">Monthly</option><option value="weekly">Weekly</option></select></div>
          <div class="field"><label>Pledge Date</label><input type="date" name="pledge_date" value="{{ old('pledge_date', now()->format('Y-m-d')) }}"></div>
          <div class="field"><label>Due Date</label><input type="date" name="due_date" value="{{ old('due_date') }}"></div>
          <div class="field full"><label>Notes</label><textarea name="notes" placeholder="Any notes about this pledge"></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Pledge</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: Add session --}}
<div class="modal-overlay" id="sessionModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Add Session</h3><p>Add to {{ $event->title }} agenda</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('events.sessions.store', $event) }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><label>Session Title</label><input name="title" placeholder="e.g. Opening Devotion" required></div>
          <div class="field"><label>Date</label><input type="date" name="session_date" value="{{ old('session_date', $event->start_date?->format('Y-m-d')) }}"></div>
          <div class="field"><label>Start Time</label><input type="time" name="start_time"></div>
          <div class="field"><label>End Time</label><input type="time" name="end_time"></div>
          <div class="field"><label>Venue</label><input name="venue" placeholder="e.g. Main Hall"></div>
          <div class="field"><label>Speaker</label><input name="speaker"></div>
          <div class="field"><label>Facilitator</label><input name="facilitator"></div>
          <div class="field full"><label>Description</label><textarea name="description" placeholder="Session details..."></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Add Session</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: Edit event --}}
<div class="modal-overlay" id="editEventModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Edit Event</h3><p>Update event details</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('events.update', $event) }}">
      @csrf @method('PUT')
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><label>Title</label><input name="title" value="{{ $event->title }}" required></div>
          <div class="field"><label>Type</label><select name="event_type">@foreach(\App\Models\Event::types() as $k=>$t)<option value="{{ $k }}" {{ $event->event_type===$k?'selected':'' }}>{{ $t }}</option>@endforeach</select></div>
          <div class="field"><label>Start Date</label><input type="date" name="start_date" value="{{ $event->start_date?->format('Y-m-d') }}"></div>
          <div class="field"><label>End Date</label><input type="date" name="end_date" value="{{ $event->end_date?->format('Y-m-d') }}"></div>
          <div class="field"><label>Venue</label><input name="venue" value="{{ $event->venue }}"></div>
          <div class="field"><label>Location</label><input name="location" value="{{ $event->location }}"></div>
          <div class="field"><label>Capacity</label><input type="number" name="capacity" value="{{ $event->capacity }}"></div>
          <div class="field"><label>Registration Fee</label><input type="number" step="0.01" name="registration_fee" value="{{ $event->registration_fee }}"></div>
          <div class="field"><label>Organizer</label><input name="organizer" value="{{ $event->organizer }}"></div>
          <div class="field full"><label>Description</label><textarea name="description">{{ $event->description }}</textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-edit-attendee]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('editAttendeeName').textContent = d.name;
      document.getElementById('editAttendeeStatus').value = d.status;
      document.getElementById('editAttendeeAmount').value = d.amount;
      document.getElementById('editAttendeeMethod').value = d.method || '';
      document.getElementById('editAttendeeNotes').value = d.notes || '';
      var form = document.getElementById('editAttendeeForm');
      form.action = "{{ url('/events') }}/{{ $event->id }}/attendees/" + d.id;
      openModalById('editAttendeeModal');
    });
  });
});
</script>
@endpush