@extends('layouts.app')

@section('title', 'Plan Day Activity — OpenGate Camp Connect')
@section('crumb', 'Events / Calendar / Day Planner')
@section('page_title', 'Plan Day Activity')

@section('content')
@php
  $sessionsJson = $sessionsJson ?? '[]';
@endphp
<div class="fade-in">
  <div class="section-head">
    <div>
      <h2>Plan Day Activity</h2>
      <div class="sub">Schedule an activity for a specific day and hours</div>
    </div>
    <div class="flex gap-8" style="flex-wrap:wrap;align-items:center">
      <div class="flex gap-8" style="align-items:center;background:var(--bg-muted,#f1f5f9);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:4px">
        <a href="{{ route('calendar.planner', ['date' => $prevDate]) }}" class="btn btn-secondary btn-sm" title="Previous day">←</a>
        <a href="{{ route('calendar.planner', ['date' => $today->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm" title="Today">Today</a>
        <a href="{{ route('calendar.planner', ['date' => $nextDate]) }}" class="btn btn-secondary btn-sm" title="Next day">→</a>
      </div>
      <form method="GET" action="{{ route('calendar.planner') }}" style="margin:0">
        <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" onchange="this.form.submit()" style="height:34px;padding:0 10px;border:1px solid var(--border-strong,#cbd5e1);border-radius:10px;font:inherit;font-size:13px;background:var(--white,#fff);color:var(--text-primary,#0f172a)">
      </form>
      <a href="{{ route('calendar.index', ['month' => $date->format('Y-m')]) }}" class="btn btn-secondary btn-sm" title="Open month calendar">Month Grid</a>
      <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print</button>
      <button type="button" class="btn btn-accent btn-sm" onclick="openPlanDrawer('{{ $date->format('Y-m-d') }}')">+ Plan Activity</button>
    </div>
  </div>

  <div class="table-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border,#e5e7eb);flex-wrap:wrap">
      <div style="font-size:14.5px;font-weight:700;color:var(--text-primary,#0f172a)">{{ $date->format('l, d F Y') }}</div>
      <span class="badge badge-info badge-dotted">{{ $sessions->count() }} @choice('activity|activities', $sessions->count()) planned</span>
    </div>
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr>
          <th style="width:110px">Time</th>
          <th>Activity</th>
          <th>Venue</th>
          <th>Category</th>
          <th>Speaker / Facilitator</th>
          <th style="width:70px"></th>
        </tr></thead>
        <tbody>
          @forelse($sessions as $s)
          <tr style="cursor:pointer" data-id="{{ $s->id }}">
            <td style="font-weight:700;color:var(--blue-accent,#2563eb);white-space:nowrap">
              {{ $s->start_time ? substr($s->start_time,0,5) : '—' }}{{ $s->end_time ? '–'.substr($s->end_time,0,5) : '' }}
            </td>
            <td>
              <div style="font-weight:600;color:var(--text-primary,#0f172a)">{{ $s->title }}</div>
              @if($s->description)<div style="font-size:12px;color:var(--text-tertiary,#64748b);margin-top:2px">{{ $s->description }}</div>@endif
            </td>
            <td>{{ $s->venue ?? '—' }}</td>
            <td>@if($s->category)<span class="badge badge-neutral badge-dotted">{{ $s->category }}</span>@else <span style="color:var(--text-tertiary,#94a3b8)">—</span>@endif</td>
            <td>{{ trim(($s->speaker ?? '') . ($s->facilitator ? ' · '.$s->facilitator : '')) ?: '—' }}</td>
            <td>
              <button type="button" class="btn btn-ghost btn-sm" title="Edit this activity" onclick="event.stopPropagation();openEditDrawer({{ $s->id }})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
              </button>
            </td>
          </tr>
          @empty
          <tr><td colspan="6">
            <div class="empty-state">
              <h3>No activities planned for this day</h3>
              <p>Plan an activity with its start and end hours for {{ $date->format('d F Y') }}.</p>
              <button type="button" class="btn btn-accent" onclick="openPlanDrawer('{{ $date->format('Y-m-d') }}')">+ Plan Activity</button>
            </div>
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>var SESSIONS = {!! $sessionsJson !!};</script>
@include('partials.session-plan-drawer')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.table-card tr[data-id]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('button') || e.target.closest('a')) return;
      openEditDrawer(tr.dataset.id);
    });
  });
});
</script>
@endpush
@endsection