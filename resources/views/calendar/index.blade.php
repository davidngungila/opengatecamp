@extends('layouts.app')

@section('title', 'Camp Calendar — Open Gate Camp Mission')
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
    <div><h2>Open Gate Camp Calendar</h2><div class="sub">{{ $monthDate->format('F Y') }} · {{ count($eventsByDay) }} event days</div></div>
    <div class="flex gap-8">
      <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}" class="btn btn-secondary btn-sm">← Prev</a>
      <a href="{{ route('calendar.index', ['month' => $today->format('Y-m')]) }}" class="btn btn-secondary btn-sm">Today</a>
      <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="btn btn-secondary btn-sm">Next →</a>
    </div>
  </div>

  <div class="glass-card">
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
        @endphp
        <div class="cal-cell {{ $isToday ? 'today' : '' }}">
          {{ $d }}
          @foreach($dayEvents as $e)
            <a href="{{ route('events.show', $e) }}" class="cal-evt type-evt" title="{{ $e->title }}">
              {{ $e->title }} <span class="cal-count">({{ $e->registered_count }})</span>
            </a>
          @endforeach
        </div>
      @endfor
      @for($i=0;$i<$trailingPads;$i++)<div class="cal-cell pad"></div>@endfor
    </div>
  </div>
</div>
@endsection