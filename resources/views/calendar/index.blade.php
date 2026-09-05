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
@endphp
<div class="fade-in">
  <div class="section-head">
    <div><h2>Open Gate Camp Calendar</h2><div class="sub">{{ $monthDate->format('F Y') }} · {{ count($eventsByDay) }} event days · {{ collect($sessionsByDay)->flatten()->count() }} planned activities</div></div>
    <div class="flex gap-8" style="flex-wrap:wrap">
      <a href="{{ route('calendar.timetable', ['scope' => 'month', 'month' => $monthDate->format('Y-m')]) }}" target="_blank" class="btn btn-secondary btn-sm"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:5px"><path d="M8 7H3a1 1 0 00-1 1v13a1 1 0 001 1h13a1 1 0 001-1v-5"/><path d="M21 3H8a1 1 0 00-1 1v13a1 1 0 001 1h13a1 1 0 001-1V4a1 1 0 00-1-1z"/><path d="M12 8v4l3 2"/></svg>Print Timetable</a>
      <button type="button" class="btn btn-accent btn-sm" data-modal-open="planModal">+ Plan Activity</button>
      <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}" class="btn btn-secondary btn-sm">← Prev</a>
      <a href="{{ route('calendar.index', ['month' => $today->format('Y-m')]) }}" class="btn btn-secondary btn-sm">Today</a>
      <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="btn btn-secondary btn-sm">Next →</a>
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
            <div class="cal-slot" title="{{ $s->title }}">
              <span class="cal-time">{{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}{{ $s->end_time ? '-'.substr($s->end_time,0,5) : '' }}</span>
              <span class="cal-slot-t">{{ $s->title }}</span>
            </div>
          @endforeach
        </div>
      @endfor
      @for($i=0;$i<$trailingPads;$i++)<div class="cal-cell pad"></div>@endfor
    </div>
    </div>
  </div>
</div>

{{-- Modal: Plan a day activity with hours --}}
<div class="modal-overlay" id="planModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Plan Day Activity</h3><p>Schedule an activity for a specific day and hours</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('calendar.sessions.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field"><label>Date</label><input type="date" name="session_date" required value="{{ request()->query('plan_date', $today->format('Y-m-d')) }}"></div>
          <div class="field"><label>Event</label>
            <select name="event_id">
              <option value="">{{ $campEvents->first()?->title ?? '— '.\App\Models\Setting::get('event.name', 'Open Gate Camp Season 3').' —' }}</option>
              @foreach($campEvents as $ce)<option value="{{ $ce->id }}">{{ $ce->title }}</option>@endforeach
            </select>
          </div>
          <div class="field full"><label>Activity / Title</label><input name="title" placeholder="e.g. Opening Devotion, Group Games" required></div>
          <div class="field"><label>Start Time</label><input type="time" name="start_time" required></div>
          <div class="field"><label>End Time</label><input type="time" name="end_time" required></div>
          <div class="field"><label>Venue</label><input name="venue" placeholder="e.g. Main Hall"></div>
          <div class="field"><label>Category</label><input name="category" placeholder="e.g. Worship, Food, Committee"></div>
          <div class="field"><label>Speaker</label><input name="speaker" placeholder="e.g. Fr. Daniel"></div>
          <div class="field"><label>Facilitator</label><input name="facilitator" placeholder="e.g. Grace Kileo"></div>
          <div class="field full"><label>Notes</label><textarea name="description" placeholder="Details..."></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Plan Activity</button>
      </div>
    </form>
  </div>
</div>
@endsection