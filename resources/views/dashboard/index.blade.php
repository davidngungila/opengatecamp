@extends('layouts.app')

@section('title', 'Dashboard — Open Gate Camp Mission')
@section('crumb', 'Dashboard')
@section('page_title', 'Command Centre')

@section('content')
@php
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $times = now()->subMonths(5)->startOfMonth()->copy();
    $labels = [];
    $series = [];
    for ($i = 0; $i < 6; $i++) {
        $key = $times->format('M');
        $labels[] = $key;
        $series[] = $monthlySeries[$key] ?? 0;
        $times->addMonth();
    }
    $spark = '['.implode(',', array_slice($series, 0, 7)).']';
@endphp
<div class="fade-in">
  <div class="welcome-block">
    <h1>@if($today->isSameDay(now()->startOfDay())) Today @endif Hello, {{ optional(auth()->user())->name ?? 'there' }} ⛺</h1>
    <p>Welcome to Open Gate Camp Mission — event registrations, finance, pledges and communications in one place.
       @if($today->isSameDay(now()->startOfDay())) Catch up on today's events below. @endif
    </p>
  </div>

  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
      </div>
      <div class="kpi-value">{{ $stats['totalEvents'] }}</div>
      <div class="kpi-label">Total Events</div>
      <div class="spark-box"><canvas class="spark" data-spark='[4,6,5,8,7,9,10]' data-color="#2563EB"></canvas></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6v6l4 2 4-2V6"/><path d="M8 6V3h8v3"/><path d="M4 21h16"/></svg></div>
      </div>
      <div class="kpi-value">{{ $stats['upcomingEvents'] }}</div>
      <div class="kpi-label">Upcoming Events</div>
      <div class="spark-box"><canvas class="spark" data-spark='[3,5,4,6,8,7,9]' data-color="#16A34A"></canvas></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--purple-bg);color:var(--purple)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-4 3-6.5 6.5-6.5s6.5 2.5 6.5 6.5"/><circle cx="17.5" cy="8.5" r="2.5"/><path d="M15.5 13.4c2.8.3 5 2.6 5 6.6"/></svg></div>
      </div>
      <div class="kpi-value">{{ $stats['totalRegistrations'] }}</div>
      <div class="kpi-label">Total Registrations</div>
      <div class="spark-box"><canvas class="spark" data-spark='{{ $spark }}' data-color="#7C3AED"></canvas></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--info-bg);color:var(--info)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div>
        <span class="badge badge-info" style="font-size:10px">{{ $stats['pendingConfirmations'] }} pending</span>
      </div>
      <div class="kpi-value" style="font-size:var(--font-size-2xl)">{{ $stats['confirmedAttendees'] }}</div>
      <div class="kpi-label">Confirmed / Attended</div>
      <div class="spark-box"><canvas class="spark" data-spark='[8,10,9,13,15,14,18]' data-color="#2563EB"></canvas></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--warning-bg);color:var(--warning)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.3 0 1.4-1.3 2.4-3 2.4s-3-1-3-2.4"/></svg></div>
      </div>
      <div class="kpi-value">TZS&nbsp;{{ number_format($pledgeTotals->pledged) }}</div>
      <div class="kpi-label">Total Pledged</div>
      <div class="spark-box"><canvas class="spark" data-spark='[5,7,6,9,11,10,14]' data-color="#D97706"></canvas></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg></div>
      </div>
      <div class="kpi-value">TZS&nbsp;{{ number_format($pledgeTotals->paid) }}</div>
      <div class="kpi-label">Collected</div>
      <div class="spark-box"><canvas class="spark" data-spark='[4,6,8,7,9,12,11]' data-color="#16A34A"></canvas></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--red-bg);color:var(--red)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M8.5 10h7"/></svg></div>
      </div>
      <div class="kpi-value">TZS&nbsp;{{ number_format($pledgeTotals->outstanding) }}</div>
      <div class="kpi-label">Outstanding Pledges</div>
      <div class="spark-box"><canvas class="spark" data-spark='[9,8,9,7,6,7,5]' data-color="#DC2626"></canvas></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 21v-6h6v6"/></svg></div>
      </div>
      <div class="kpi-value">TZS&nbsp;{{ number_format($budgetTotal) }}</div>
      <div class="kpi-label">Total Budgets</div>
      <div class="spark-box"><canvas class="spark" data-spark='[10,12,11,14,15,17,20]' data-color="#2563EB"></canvas></div>
    </div>
  </div>

  <div class="two-col">
    <div class="glass-card">
      <div class="section-head" style="margin-bottom:14px">
        <div><h2>Registrations Trend</h2><div class="sub">Attendee registrations over the last 6 months</div></div>
      </div>
      <div class="chart-wrap"><canvas id="mainChart"></canvas></div>
    </div>

    <div class="glass-card">
      <div class="section-head" style="margin-bottom:14px"><div><h2>Pledges by Event</h2><div class="sub">Top pledged events</div></div></div>
      <div class="chart-wrap" style="height:260px"><canvas id="pledgeChart"
        data-labels='@json($pledgeByEvent->pluck("event.title"))'
        data-values='@json($pledgeByEvent->pluck("t"))'></canvas></div>
    </div>
  </div>

  <div class="two-col" style="grid-template-columns:1fr 1fr 1fr;">
    <div class="glass-card">
      <div class="section-head" style="margin-bottom:10px"><h2>Quick Actions</h2></div>
      <div class="quick-actions-grid" style="grid-template-columns:repeat(2,1fr)">
        <a class="qa-btn" href="{{ route('events.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div><span>Create Event</span></a>
        <a class="qa-btn" href="{{ route('attendees.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div><span>Registrations</span></a>
        <a class="qa-btn" href="{{ route('pledges.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.3 0 1.4-1.3 2.4-3 2.4s-3-1-3-2.4"/></svg></div><span>Record Pledge</span></a>
        <a class="qa-btn" href="{{ route('calendar.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M3 15h18"/></svg></div><span>Open Calendar</span></a>
        <a class="qa-btn" href="{{ route('messaging.sms') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v3a1 1 0 001 1h2l4 4V6L6 10H4a1 1 0 00-1 1z"/><path d="M15 8a4 4 0 010 8M18 5a8 8 0 010 14"/></svg></div><span>Send Message</span></a>
        <a class="qa-btn" href="{{ route('accounting.index') }}"><div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg></div><span>Financial Reports</span></a>
      </div>
    </div>

    <div class="glass-card list-card">
      <div class="section-head" style="margin-bottom:6px"><h2>Upcoming Events</h2><a class="link-btn" href="{{ route('calendar.index') }}">View all</a></div>
      @forelse($upcoming as $e)
      <div class="mini-row">
        <div class="m-ico" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
        <a class="m-body" href="{{ route('events.show', $e) }}"><p>{{ $e->title }}</p><span>{{ $e->start_date?->format('d M Y H:i') }} · {{ $e->venue }}</span></a>
        <span class="badge badge-{{ $e->getStatusColor() }} badge-dotted">{{ $e->getStatusLabel() }}</span>
      </div>
      @empty
      <div class="empty-state" style="padding:24px 16px"><p>No upcoming events yet.</p></div>
      @endforelse
    </div>

    <div class="glass-card list-card">
      <div class="section-head" style="margin-bottom:6px"><h2>Recent Events</h2><a class="link-btn" href="{{ route('events.index') }}">View all</a></div>
      @forelse($recent as $e)
      <div class="mini-row">
        <div class="cell-avatar">{{ collect(explode(' ', $e->title))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
        <div class="m-body"><a href="{{ route('events.show', $e) }}" class="link-btn">{{ $e->title }}</a><span>{{ $e->attendees_count }} registered</span></div>
      </div>
      @empty
      <div class="empty-state" style="padding:24px 16px"><p>No events yet.</p></div>
      @endforelse
    </div>
  </div>
</div>
@endsection

@push('scripts')
@verbatim
<script>
document.addEventListener('DOMContentLoaded', function(){
  if(typeof Chart==='undefined') return;
  document.querySelectorAll('canvas[data-spark]').forEach(function(cv){
    var data=JSON.parse(cv.dataset.spark), color=cv.dataset.color;
    new Chart(cv,{type:'line',data:{labels:data.map(function(_,i){return i;}),datasets:[{data:data,borderColor:color,borderWidth:2,tension:.4,pointRadius:0,fill:true,backgroundColor:color+'22'}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{enabled:false}},scales:{x:{display:false},y:{display:false}}}});
  });
  var mainCv=document.getElementById('mainChart');
  if(mainCv){
    new Chart(mainCv,{type:'bar',data:{labels:['Jan','Feb','Mar','Apr','May','Jun','Jul'],
      datasets:[{label:'Registrations',data:[4,6,5,8,7,9,10],backgroundColor:'rgba(124,58,237,.7)',borderRadius:6}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{grid:{color:'rgba(15,23,42,.06)'},ticks:{font:{family:'Manrope',size:11}}},x:{grid:{display:false}}}}});
  }
  var plCv=document.getElementById('pledgeChart');
  if(plCv){
    var plabels=JSON.parse(plCv.dataset.labels||'[]'), pvalues=JSON.parse(plCv.dataset.values||'[]');
    new Chart(plCv,{type:'doughnut',data:{labels:plabels,datasets:[{data:pvalues,backgroundColor:['#2563EB','#7C3AED','#16A34A','#D97706','#0B1F3A','#DC2626'],borderWidth:0}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:9,font:{family:'Manrope',size:10.5,weight:600}}}}}});
  }
});
</script>
@endverbatim
@endpush