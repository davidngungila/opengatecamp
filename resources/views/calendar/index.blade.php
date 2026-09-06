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
        'event_title' => $s->event?->title,
    ],
    ])->values()->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
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
      <a href="{{ route('calendar.planner', ['date' => $today->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm">Day Planner</a>
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

<script>var SESSIONS = {!! $sessionsJson !!};</script>
@include('partials.session-plan-drawer')
@endsection

@push('styles')
<style>
.cal-add{position:absolute;right:6px;bottom:6px;width:20px;height:20px;border-radius:6px;border:1px dashed var(--border-strong);background:var(--white);color:var(--text-tertiary);font-size:13px;line-height:1;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s;padding:0;}
.cal-add:hover{border-color:var(--blue-accent);color:var(--blue-accent);background:var(--blue-light);}
.cal-slot:hover{border-color:rgba(37,99,235,.5);background:var(--info);color:#fff;}
</style>
@endpush