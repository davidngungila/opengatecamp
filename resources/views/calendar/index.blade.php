@extends('layouts.app')

@section('title', 'Camp Calendar — OpenGate Camp Connect')
@section('crumb', 'Events / Calendar')
@section('page_title', $monthDate->format('F Y'))

@section('content')
@php
    $daysInMonth = (int) $monthDate->format('t');
    $startOffset = ((int) $monthDate->format('w') + 6) % 7;
    $totalCells = ($startOffset + $daysInMonth);
    $trailingPads = $totalCells % 7 === 0 ? 0 : 7 - ($totalCells % 7);
    $allSessions = collect($sessionsByDay)->flatten(-1)->values();
    $sessionsJson = $allSessions->mapWithKeys(fn ($s) => [$s->id => [
        'id'          => $s->id,
        'session_date'=> $s->session_date?->format('Y-m-d'),
        'title'       => $s->title,
        'start_time'  => $s->start_time ? substr($s->start_time, 0, 5) : '',
        'end_time'    => $s->end_time ? substr($s->end_time, 0, 5) : '',
        'venue'       => $s->venue,
        'category'    => $s->category,
        'speaker'     => $s->speaker,
        'facilitator' => $s->facilitator,
        'description' => $s->description,
        'event_id'    => $s->event_id,
        'event_title' => $s->event?->title,
    ],
    ])->values()->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
    $eventOptions = collect($campEvents)->mapWithKeys(fn ($e) => [$e->id => $e->title])->all();
    $todayLabel = $today->format('jS F Y');
    $weekNumber = (int) $monthDate->format('W');
@endphp
<div class="fade-in">
  <div class="section-head">
    <div>
      <h2>Open Gate Camp Calendar</h2>
      <div class="sub">{{ $monthDate->format('F Y') }} &middot; Week {{ $weekNumber }} &middot; {{ count($eventsByDay) }} event days &middot; {{ $allSessions->count() }} activities</div>
    </div>
    <div class="flex gap-8" style="flex-wrap:wrap;align-items:center">
      <div class="flex gap-8" style="align-items:center;background:var(--bg-muted,#f1f5f9);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:4px">
        <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}" class="btn btn-secondary btn-sm" title="Previous month">←</a>
        <a href="{{ route('calendar.index', ['month' => $today->format('Y-m')]) }}" class="btn btn-secondary btn-sm" title="Today">Today</a>
        <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="btn btn-secondary btn-sm" title="Next month">→</a>
      </div>
      <a href="{{ route('calendar.timetable', ['scope' => 'month', 'month' => $monthDate->format('Y-m')]) }}" target="_blank" class="btn btn-secondary btn-sm">Print Timetable</a>
      <button type="button" class="btn btn-accent btn-sm" onclick="openPlanDrawer()">+ Plan Activity</button>
    </div>
  </div>

  <div class="glass-card">
    <div class="cal-scroll">
    <div class="calendar-grid" style="margin-bottom:8px">
      @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)<div class="cal-dow">{{ $d }}</div>@endforeach
    </div>
    <div class="calendar-grid">
      @for($i=0;$i<$startOffset;$i++)<div class="cal-cell pad"></div>@endfor
      @for($d=1;$d<=$daysInMonth;$d++)
        @php
            $key = $monthDate->format('Y-m').'-'.str_pad((string)$d,2,'0',STR_PAD_LEFT);
            $isToday = $today->format('Y-m-d') === $key;
            $dayEvents = $eventsByDay[$key] ?? [];
            $daySessions = $sessionsByDay[$key] ?? [];
        @endphp
        <div class="cal-cell {{ $isToday ? 'today' : '' }}">
          <div class="cal-head">{{ $d }}</div>
          @foreach($dayEvents as $e)
            <a href="{{ route('events.show', $e) }}" class="cal-evt type-evt" title="{{ $e->title }}">
              {{ $e->title }} <span class="cal-count">({{ $e->registered_count }})</span>
            </a>
          @endforeach
          @foreach($daySessions as $s)
            <div class="cal-slot" style="cursor:pointer" title="Edit: {{ $s->title }}" onclick="openEditDrawer({{ $s->id }})">
              <span class="cal-time">{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}{{ $s->end_time ? '-'.substr($s->end_time,0,5) : '' }}</span>
              <span class="cal-slot-t">{{ $s->title }}</span>
            </div>
          @endforeach
          <button type="button" class="cal-add" title="Add activity on this day" onclick="openPlanDrawer('{{ $key }}')">+</button>
        </div>
      @endfor
      @for($i=0;$i<$trailingPads;$i++)<div class="cal-cell pad"></div>@endfor
    </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:14px;padding:12px 4px 2px;font-size:11.5px;color:var(--text-tertiary);font-weight:600">
      <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:var(--info-bg);display:inline-block"></span> Planned activity (click to edit)</span>
      <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:var(--blue-accent);display:inline-block"></span> Camp event</span>
      <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:var(--blue-light);border:1px solid var(--blue-accent);display:inline-block"></span> Today</span>
    </div>
  </div>
</div>

{{-- Right drawer: plan / edit a day activity --}}
<div class="drawer-overlay" id="sessionDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div>
        <h3 id="sessTitle">Plan Day Activity</h3>
        <p id="sessSub">Schedule an activity for a specific day and hours</p>
      </div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" id="sessionForm" action="{{ route('calendar.sessions.store') }}">
      @csrf
      <input type="hidden" name="_method" id="sessMethod" value="POST">
      <input type="hidden" name="id" id="sessId" value="">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Date *</label><input type="date" name="session_date" id="sessDate" required></div>
          <div class="field full"><label>Event</label>
            <select name="event_id" id="sessEvent">
              @foreach($campEvents as $ce)<option value="{{ $ce->id }}">{{ $ce->title }}</option>@endforeach
            </select>
          </div>
          <div class="field full"><label>Activity / Title *</label><input name="title" id="sessTitleInput" placeholder="e.g. Opening Devotion, Group Games" required></div>
          <div class="field"><label>Start Time *</label><input type="time" name="start_time" id="sessStart" required></div>
          <div class="field"><label>End Time *</label><input type="time" name="end_time" id="sessEnd" required></div>
          <div class="field full"><label>Venue</label><input name="venue" id="sessVenue" placeholder="e.g. Main Hall"></div>
          <div class="field"><label>Category</label><input name="category" id="sessCategory" placeholder="e.g. Worship, Food, Committee"></div>
          <div class="field"><label>Speaker</label><input name="speaker" id="sessSpeaker" placeholder="e.g. Fr. Daniel"></div>
          <div class="field full"><label>Facilitator</label><input name="facilitator" id="sessFacilitator" placeholder="e.g. Grace Kileo"></div>
          <div class="field full"><label>Notes</label><textarea name="description" id="sessDescription" placeholder="Details..."></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-danger" id="sessDelete" style="margin-right:auto;display:none" onclick="deleteSession()">Delete</button>
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="sessSubmit">Plan Activity</button>
      </div>
    </form>
  </div>
</div>

<form method="POST" id="sessionDeleteForm" action="">
  @csrf
  @method('DELETE')
</form>
@endsection

@push('styles')
<style>
.cal-add{position:absolute;right:6px;bottom:6px;width:20px;height:20px;border-radius:6px;border:1px dashed var(--border-strong);background:var(--white);color:var(--text-tertiary);font-size:13px;line-height:1;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s;padding:0;}
.cal-add:hover{border-color:var(--blue-accent);color:var(--blue-accent);background:var(--blue-light);}
.cal-slot:hover{border-color:rgba(37,99,235,.5);background:var(--info);color:#fff;}
</style>
@endpush

@push('scripts')
<script>
var SESSIONS = {!! $sessionsJson !!};
var EVENT_OPTIONS = @json($eventOptions);
var todayLabel = @json($todayLabel);
var planDate = '';

function fillSessionForm(s){
  document.getElementById('sessId').value = s.id;
  document.getElementById('sessDate').value = s.session_date || planDate;
  document.getElementById('sessTitleInput').value = s.title || '';
  document.getElementById('sessStart').value = s.start_time || '';
  document.getElementById('sessEnd').value = s.end_time || '';
  document.getElementById('sessVenue').value = s.venue || '';
  document.getElementById('sessCategory').value = s.category || '';
  document.getElementById('sessSpeaker').value = s.speaker || '';
  document.getElementById('sessFacilitator').value = s.facilitator || '';
  document.getElementById('sessDescription').value = s.description || '';
  var ev = document.getElementById('sessEvent');
  if(EVENT_OPTIONS[s.event_id]){ ev.value = s.event_id; }
  else if(!ev.value && s.event_id){ ev.value = s.event_id; }
}

function openPlanDrawer(dateStr){
  planDate = dateStr || todayLabel || '';
  document.getElementById('sessTitle').textContent = 'Plan Day Activity';
  document.getElementById('sessSub').textContent = dateStr ? 'Scheduling activity for ' + dateStr : 'Schedule an activity for a specific day and hours';
  document.getElementById('sessMethod').value = 'POST';
  document.getElementById('sessionForm').action = @json(route('calendar.sessions.store'));
  document.getElementById('sessId').value = '';
  document.getElementById('sessDate').value = dateStr || @json($today->format('Y-m-d'));
  document.getElementById('sessTitleInput').value = '';
  document.getElementById('sessStart').value = '';
  document.getElementById('sessEnd').value = '';
  document.getElementById('sessVenue').value = '';
  document.getElementById('sessCategory').value = '';
  document.getElementById('sessSpeaker').value = '';
  document.getElementById('sessFacilitator').value = '';
  document.getElementById('sessDescription').value = '';
  document.getElementById('sessEvent').value = EVENT_OPTIONS ? Object.keys(EVENT_OPTIONS)[0] || '' : '';
  document.getElementById('sessDelete').style.display = 'none';
  document.getElementById('sessSubmit').textContent = 'Plan Activity';
  openDrawerById('sessionDrawer');
}

function openEditDrawer(id){
  var s = SESSIONS.find(function(x){ return Number(x.id) === Number(id); });
  if(!s) return;
  planDate = s.session_date;
  fillSessionForm(s);
  document.getElementById('sessTitle').textContent = 'Edit Activity';
  document.getElementById('sessSub').textContent = (s.event_title || 'Calendar') + ' · ' + s.session_date;
  document.getElementById('sessMethod').value = 'PUT';
  document.getElementById('sessionForm').action = @json(url('/calendar/sessions')) + '/' + id;
  document.getElementById('sessDelete').style.display = '';
  document.getElementById('sessSubmit').textContent = 'Save Changes';
  openDrawerById('sessionDrawer');
}

function deleteSession(){
  var id = document.getElementById('sessId').value;
  if(!id) return;
  var f = document.getElementById('sessionDeleteForm');
  f.action = @json(url('/calendar/sessions')) + '/' + id;
  confirmAction(f, 'Delete this activity?', 'This activity will be removed from the calendar permanently.', 'Delete');
}
</script>
@endpush