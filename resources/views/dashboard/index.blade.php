@extends('layouts.app')

@section('title', 'Dashboard — Open Gate Camp')
@section('crumb', 'Dashboard')
@section('page_title', 'Event Command Centre')

@section('content')
@if(!$event)
<div class="fade-in">
  <div class="empty-state" style="padding:48px 24px">
    <h3 style="margin-bottom:8px">No event yet</h3>
    <p>Create the Open Gate Camp event to start running your dashboard.</p>
    <a href="{{ route('events.create') }}" class="btn btn-accent" style="margin-top:14px">Create Event</a>
  </div>
</div>
@else
@php
    $feeOutstanding  = max(0, $feesExpected - $feesCollected);
    $pledgeOutstanding = max(0, $pledged - $pledgePaid);
    $totalOutstanding = $feeOutstanding + $pledgeOutstanding;
    $labelMap = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'attended' => 'Attended', 'no_show' => 'No Show', 'cancelled' => 'Cancelled'];
    $colorMap = ['pending' => '#D97706', 'confirmed' => '#2563EB', 'attended' => '#16A34A', 'no_show' => '#DC2626', 'cancelled' => '#64748B'];
    $sKeys = ['pending', 'confirmed', 'attended', 'no_show', 'cancelled'];
    $sLabels = array_map(fn($k) => $labelMap[$k], $sKeys);
    $sValues = array_map(fn($k) => (int) ($statusBreakdown[$k] ?? 0), $sKeys);
    $sColors = array_map(fn($k) => $colorMap[$k], $sKeys);
    $trendLabels = json_encode(array_keys($trend));
    $trendValues = json_encode(array_values($trend));
    $tzs = fn($n) => 'TZS&nbsp;' . number_format((float) $n);
@endphp
<div class="fade-in">

  <div class="welcome-block">
    <h1>@if($event->start_date?->isToday()) Today is the day — @endif{{ $event->title }}</h1>
    <p>Single-event command centre for Open Gate Camp. Registrations, fees, pledges, budget and sessions at a glance.</p>
  </div>

  {{-- Event hero --}}
  <div class="glass-card" style="margin-bottom:18px">
    <div style="display:flex;flex-wrap:wrap;gap:18px;align-items:center;justify-content:space-between">
      <div style="min-width:280px;flex:1">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px">
          <span class="badge badge-{{ $event->getTypeColor() }}"><span style="font-weight:700">{{ $event->getTypeLabel() }}</span></span>
          <span class="badge badge-{{ $event->getStatusColor() }} badge-dotted">{{ $event->getStatusLabel() }}</span>
          @if($event->featured)<span class="badge badge-purple">Featured</span>@endif
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:8px 18px;font-size:13px;color:var(--text-secondary)">
          <div><strong style="color:var(--text-primary)">Dates</strong><br>{{ $event->start_date?->format('D, d M Y') }}{{ $event->end_date ? ' – '.$event->end_date->format('D, d M Y') : '' }}</div>
          <div><strong style="color:var(--text-primary)">Venue</strong><br>{{ $event->venue ?: '—' }}</div>
          <div><strong style="color:var(--text-primary)">Organizer</strong><br>{{ $event->organizer ?: '—' }}</div>
          <div><strong style="color:var(--text-primary)">Capacity</strong><br>{{ $capacity > 0 ? $capacity.' seats' : 'Unlimited' }}</div>
        </div>
        @if($event->description)
        <p style="font-size:13px;color:var(--text-secondary);margin-top:12px;line-height:1.55">{{ $event->description }}</p>
        @endif
      </div>
      <div style="width:min(320px,100%)">
        @if($capacity > 0)
        <div style="font-size:12px;color:var(--text-secondary);margin-bottom:6px">Seats taken — {{ $stats['total'] }} of {{ $capacity }} ({{ $fillPercent }}% full)</div>
        <div class="progress-track"><div class="progress-fill" style="width:{{ $fillPercent }}%"></div></div>
        <div style="font-size:12px;color:var(--text-secondary);margin-top:6px">{{ $seatsLeft }} seat{{ $seatsLeft == 1 ? '' : 's' }} remaining</div>
        @else
        <div style="font-size:12px;color:var(--text-secondary);margin-bottom:6px">Registered so far</div>
        <div class="progress-track"><div class="progress-fill" style="width:{{ min(100, $stats['total']) }}%"></div></div>
        @endif
        <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
          <a href="{{ route('events.show', $event) }}" class="btn btn-accent">Open Event</a>
          <a href="{{ route('attendees.index') }}" class="btn btn-secondary">Registrations</a>
          <a href="{{ route('pledges.index', ['event_id' => $event->id]) }}" class="btn btn-secondary">Pledges</a>
        </div>
      </div>
    </div>
  </div>

  {{-- KPI grid --}}
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg></div></div>
      <div class="kpi-value">{{ $stats['total'] }}</div>
      <div class="kpi-label">Total Registrations</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div></div>
      <div class="kpi-value">{{ $stats['confirmed'] }}</div>
      <div class="kpi-label">Confirmed / Attended</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--purple-bg);color:var(--purple)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div></div>
      <div class="kpi-value">{{ $stats['attended'] }}</div>
      <div class="kpi-label">Checked In</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--warning-bg);color:var(--warning)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg></div></div>
      <div class="kpi-value">{{ $stats['pending'] }}</div>
      <div class="kpi-label">Pending</div>
      <div style="font-size:11px;color:var(--info)">{{ $stats['no_show'] }} no-show · {{ $stats['cancelled'] }} cancelled</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div></div>
      <div class="kpi-value" style="font-size:var(--font-size-xl)">{{ $tzs($feesExpected) }}</div>
      <div class="kpi-label">Registration Fees (expected)</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg></div></div>
      <div class="kpi-value" style="font-size:var(--font-size-xl)">{{ $tzs($feesCollected) }}</div>
      <div class="kpi-label">Fees Collected</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--warning-bg);color:var(--warning)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.3 0 1.4-1.3 2.4-3 2.4s-3-1-3-2.4"/></svg></div></div>
      <div class="kpi-value" style="font-size:var(--font-size-xl)">{{ $tzs($pledged) }}</div>
      <div class="kpi-label">Pledged</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--info-bg);color:var(--info)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 10h20M6 14h4"/></svg></div></div>
      <div class="kpi-value" style="font-size:var(--font-size-xl)">{{ $tzs($pledgePaid) }}</div>
      <div class="kpi-label">Pledge Collected</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--danger-bg);color:var(--danger)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M8.5 10h7"/></svg></div></div>
      <div class="kpi-value" style="font-size:var(--font-size-xl)">{{ $tzs($totalOutstanding) }}</div>
      <div class="kpi-label">Outstanding (fees + pledges)</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 21v-6h6v6"/></svg></div></div>
      <div class="kpi-value" style="font-size:var(--font-size-xl)">{{ $tzs($budgetTotal) }}</div>
      <div class="kpi-label">Event Budget</div>
    </div>
  </div>

  <div class="two-col">
    <div class="glass-card">
      <div class="section-head" style="margin-bottom:14px">
        <div><h2>Registration Trend</h2><div class="sub">Daily registrations — last 14 days</div></div>
      </div>
      <div class="chart-wrap"><canvas id="trendChart" data-labels='{{ $trendLabels }}' data-values='{{ $trendValues }}'></canvas></div>
    </div>

    <div class="glass-card">
      <div class="section-head" style="margin-bottom:14px"><div><h2>Registrations by Status</h2><div class="sub">{{ $event->title }}</div></div></div>
      <div class="chart-wrap" style="height:260px"><canvas id="statusChart"
        data-labels='@json($sLabels)' data-values='@json($sValues)' data-colors='@json($sColors)'></canvas></div>
    </div>
  </div>

  <div class="two-col" style="grid-template-columns:1fr 1fr 1fr;">
    <div class="glass-card">
      <div class="section-head" style="margin-bottom:10px"><h2>Quick Actions</h2></div>
      <div class="quick-actions-grid" style="grid-template-columns:repeat(2,1fr)">
        <a class="qa-btn" href="{{ route('events.show', $event) }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div><span>Event Details</span></a>
        <a class="qa-btn" href="{{ route('attendees.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div><span>Registrations</span></a>
        <a class="qa-btn" href="{{ route('pledges.index', ['event_id' => $event->id]) }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.3 0 1.4-1.3 2.4-3 2.4s-3-1-3-2.4"/></svg></div><span>Record Pledge</span></a>
        <a class="qa-btn" href="{{ route('calendar.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M3 15h18"/></svg></div><span>Open Calendar</span></a>
        <a class="qa-btn" href="{{ route('messaging.sms') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v3a1 1 0 001 1h2l4 4V6L6 10H4a1 1 0 00-1 1z"/><path d="M15 8a4 4 0 010 8M18 5a8 8 0 010 14"/></svg></div><span>Send Message</span></a>
        <a class="qa-btn" href="{{ route('accounting.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg></div><span>Financial Reports</span></a>
      </div>
    </div>

    <div class="glass-card list-card">
      <div class="section-head" style="margin-bottom:6px"><h2>Latest Registrations</h2><a class="link-btn" href="{{ route('attendees.index') }}">View all</a></div>
      @forelse($latestRegistrations as $a)
      <div class="mini-row">
        <div class="cell-avatar">{{ collect(explode(' ', $a->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
        <div class="m-body"><p>{{ $a->name }}</p><span>{{ $a->registered_on?->format('d M Y') }} · {{ $a->member?->member_no ?? 'Guest' }}</span></div>
        <span class="badge badge-{{ $a->getStatusColor() }} badge-dotted">{{ $a->getStatusLabel() }}</span>
      </div>
      @empty
      <div class="empty-state" style="padding:24px 16px"><p>No registrations yet.</p></div>
      @endforelse
    </div>

    <div class="glass-card list-card">
      <div class="section-head" style="margin-bottom:6px"><h2>Top Pledges</h2><a class="link-btn" href="{{ route('pledges.index', ['event_id' => $event->id]) }}">View all</a></div>
      @forelse($latestPledges as $pl)
      <div class="mini-row">
        <div class="cell-avatar" style="background:var(--warning-bg);color:var(--warning)">{{ collect(explode(' ', $pl->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
        <div class="m-body"><p>{{ $pl->name }}</p><span>{{ $pl->pledge_no }} · {{ $pl->getStatusLabel() }}</span></div>
        <div style="text-align:right"><strong style="font-size:13px">{{ $tzs($pl->amount) }}</strong><div style="font-size:11px;color:var(--text-secondary)">balance {{ $tzs($pl->remaining ?? 0) }}</div></div>
      </div>
      @empty
      <div class="empty-state" style="padding:24px 16px"><p>No pledges yet.</p></div>
      @endforelse
    </div>
  </div>

  @if($sessions->count())
  <div class="glass-card list-card" style="margin-top:18px">
    <div class="section-head" style="margin-bottom:8px"><h2>Sessions / Agenda</h2><a class="link-btn" href="{{ route('events.show', $event) }}">Manage</a></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px">
      @foreach($sessions as $s)
      <div style="background:rgba(15,23,42,.035);border:1px solid rgba(15,23,42,.07);border-radius:12px;padding:12px 14px">
        <div class="mini-row" style="gap:10px">
          <div class="m-ico" style="background:var(--info-bg);color:var(--info);width:34px;height:34px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
          <div class="m-body"><p>{{ $s->title }}</p><span>{{ $s->session_date?->format('d M Y') }} @if($s->start_time) · {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}@endif</span></div>
        </div>
        @if($s->facilitator || $s->speaker)<div style="font-size:11.5px;color:var(--text-secondary);margin-top:6px">{{ $s->speaker ? 'Speaker: '.$s->speaker : '' }}{{ $s->facilitator ? ' · Facilitator: '.$s->facilitator : '' }}</div>@endif
      </div>
      @endforeach
    </div>
  </div>
  @endif

</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  if(typeof Chart==='undefined') return;
  var trendCv=document.getElementById('trendChart');
  if(trendCv){
    new Chart(trendCv,{type:'bar',data:{labels:JSON.parse(trendCv.dataset.labels||'[]'),
      datasets:[{label:'Registrations',data:JSON.parse(trendCv.dataset.values||'[]'),backgroundColor:'rgba(124,58,237,.7)',borderRadius:6}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
        scales:{y:{beginAtZero:true,ticks:{precision:0,font:{family:'Manrope',size:11}},grid:{color:'rgba(15,23,42,.06)'}},x:{grid:{display:false},ticks:{maxRotation:45,font:{family:'Manrope',size:10}}}}}});
  }
  var stCv=document.getElementById('statusChart');
  if(stCv){
    new Chart(stCv,{type:'doughnut',data:{labels:JSON.parse(stCv.dataset.labels||'[]'),
      datasets:[{data:JSON.parse(stCv.dataset.values||'[]'),backgroundColor:JSON.parse(stCv.dataset.colors||'[]'),borderWidth:0}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:9,font:{family:'Manrope',size:10.5,weight:600}}}}}});
  }
});
</script>
@endpush