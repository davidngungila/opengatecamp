@extends('layouts.app')

@section('title', 'Admission Desk — OpenGate Camp Connect')
@section('crumb', 'Events / Admission')
@section('page_title', 'Admission Desk')

@section('content')
<style>
  .admission-wrap{max-width:1080px;margin:0 auto;}
  .field-label{display:block;font-size:13px;font-weight:700;color:var(--text-secondary);margin-bottom:8px;}
</style>

<div class="fade-in admission-wrap">
  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">{{ session('error') }}</div>
  @endif
  @if(session('info'))
  <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#1e40af;font-size:13.5px">{{ session('info') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">{{ session('success') }}</div>
  @endif

  {{-- Gate Register: admitted / not admitted lists --}}
  <div class="glass-card">
    <div class="flex" style="align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
      <div class="flex gap-8" style="align-items:center;flex-wrap:wrap">
        <h2 style="font-size:15px;margin:0">Gate Register</h2>
        <div class="tabs-bar" style="margin:0">
          <a href="{{ route('admission.index', array_filter(['tab'=>'admitted','q'=>$q])) }}" class="tab-btn {{ $tab==='admitted' ? 'active' : '' }}">Admitted ({{ $admitted->total() }})</a>
          <a href="{{ route('admission.index', array_filter(['tab'=>'pending','q'=>$q])) }}" class="tab-btn {{ $tab==='pending' ? 'active' : '' }}">Not Admitted ({{ $notAdmitted->total() }})</a>
        </div>
      </div>
      <form method="GET" action="{{ route('admission.index') }}" class="flex gap-8" style="align-items:center">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="tfield" style="min-width:230px">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          <input name="q" value="{{ $q }}" placeholder="Search name, phone, ticket, fellowship...">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Search</button>
        @if($q !== '')
        <a href="{{ route('admission.index', ['tab'=>$tab]) }}" class="btn btn-ghost btn-sm">Clear</a>
        @endif
      </form>
    </div>

    @php $list = $tab === 'pending' ? $notAdmitted : $admitted; @endphp
    <div class="table-card" style="box-shadow:none;border:1px solid var(--border,#e5e7eb)">
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th>Attendee</th>
              <th>Ticket</th>
              <th>Contact</th>
              <th>Fellowship</th>
              <th>Region</th>
              <th>Paid (TZS)</th>
              <th>Status</th>
              @if($tab === 'admitted')<th>Admitted</th>@endif
            </tr>
          </thead>
          <tbody>
            @forelse($list as $a)
            @php
              $region = $a->pickup_location === 'arusha' ? 'Arusha' : ($a->pickup_location === 'moshi' ? 'Moshi' : '—');
            @endphp
            <tr class="admit-row" style="cursor:pointer" data-id="{{ $a->hashed_id }}"
                data-ticket="{{ $a->getTicketNo() }}"
                data-name="{{ $a->name }}"
                data-phone="{{ $a->phone }}"
                data-email="{{ $a->email }}"
                data-fellowship="{{ $a->fellowship }}"
                data-region="{{ $region }}"
                data-paid="{{ number_format((float)$a->amount_paid, 0) }}"
                data-fee="{{ number_format((float)$a->fee_amount, 0) }}"
                data-method="{{ $a->payment_method }}"
                data-status="{{ $a->getStatusLabel() }}"
                data-status-color="{{ $a->getStatusColor() }}"
                data-event="{{ $a->event?->title }}"
                data-registered="{{ $a->registered_on?->format('d M Y') }}"
                data-checkedin="@if($a->checked_in_at){{ $a->checked_in_at->format('d M Y H:i') }}@endif"
                data-checkedinby="{{ $a->checked_in_by }}"
                data-notes="{{ $a->notes }}">
              <td><div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $a->name ?? '?'))->map(fn($w)=>mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div><div class="cu-name">{{ $a->name ?? '—' }}</div><div class="cu-sub">{{ $a->event?->title }}</div></div>
              </div></td>
              <td style="font-family:monospace;font-weight:700;letter-spacing:1px">{{ $a->getTicketNo() }}</td>
              <td><div class="cu-sub">{{ $a->phone ?? '—' }}<br>{{ $a->email ?? '' }}</div></td>
              <td>{{ $a->fellowship ?: '—' }}</td>
              <td>{{ $region }}</td>
              <td>{{ number_format((float)$a->amount_paid, 0) }}</td>
              <td><span class="badge badge-{{ $a->getStatusColor() }} badge-dotted">{{ $a->getStatusLabel() }}</span></td>
              @if($tab === 'admitted')
              <td><div class="cu-sub">{{ $a->checked_in_at?->format('d M Y H:i') }}<br>{{ $a->checked_in_by }}</div></td>
              @endif
            </tr>
            @empty
            <tr><td colspan="{{ $tab==='admitted' ? 8 : 7 }}"><div class="empty-state" style="padding:36px 16px"><h3>No {{ $tab==='pending' ? 'non-admitted' : 'admitted' }} attendees</h3><p>{{ $q !== '' ? 'No results match your search.' : 'Nothing here yet.' }}</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span class="tf-info">Showing {{ $list->firstItem() ?? 0 }}–{{ $list->lastItem() ?? 0 }} of {{ $list->total() }}</span>
        <div class="pagination">{{ $list->appends(['tab'=>$tab,'q'=>$q])->links() }}</div>
      </div>
    </div>
  </div>
</div>

{{-- Admission detail drawer --}}
<div class="drawer-overlay" id="admitDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Admission Details</h3><p id="admDateMeta" class="cu-sub">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="cell-avatar avatar-lg" id="admAvatar">—</div>
        <div><div class="cu-name" id="admName" style="font-size:17px">—</div>
          <div class="flex gap-8" style="align-items:center;margin-top:4px"><span style="font-family:monospace;font-weight:800;letter-spacing:2px;color:var(--blue-accent)" id="admTicket">—</span><span class="badge badge-neutral badge-dotted" id="admStatus">—</span></div>
        </div>
      </div>
      <div class="info-grid" style="margin-top:16px">
        <div class="info-row"><span>Event</span><b id="admEvent">—</b></div>
        <div class="info-row"><span>Phone</span><b id="admPhone">—</b></div>
        <div class="info-row"><span>Email</span><b id="admEmail">—</b></div>
        <div class="info-row"><span>Fellowship</span><b id="admFellowship">—</b></div>
        <div class="info-row"><span>Coming From</span><b id="admRegion">—</b></div>
        <div class="info-row"><span>Fee / Paid</span><b id="admPaid">—</b></div>
        <div class="info-row"><span>Payment Method</span><b id="admMethod">—</b></div>
        <div class="info-row"><span>Registered</span><b id="admRegistered">—</b></div>
        <div class="info-row"><span>Admitted</span><b id="admCheckedIn">—</b></div>
        <div class="info-row"><span>Admitted By</span><b id="admCheckedInBy">—</b></div>
        <div class="info-row" style="grid-column:1/-1;border-bottom:none"><span>Notes</span><b id="admNotes" style="white-space:pre-wrap">—</b></div>
      </div>
      <hr style="border:none;border-top:1px solid var(--border);margin:14px 0">
      <a id="admTicketLink" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center" href="#" target="_blank">Open Ticket (PDF)</a>
    </div>
    <div class="drawer-foot" style="display:block">
      <form method="POST" action="{{ route('admission.admit') }}" id="admAdmitForm" style="display:none" data-confirm
            data-confirm-title="Admit this attendee?"
            data-confirm-message="This will mark the attendee as admitted and send a welcome SMS."
            data-confirm-label="Admit">
        @csrf
        <input type="hidden" name="code" id="admAdmitCode" value="">
        <button type="submit" class="btn btn-accent" style="width:100%">Admit to Event</button>
      </form>
      <button type="button" class="btn btn-secondary" data-drawer-close style="width:100%;margin-top:8px">Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  var BASE = '{{ url('/attendees') }}';
  document.querySelectorAll('.admit-row').forEach(function(row){
    row.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      openAdm(row);
    });
  });
  function openAdm(row){
    var d = row.dataset;
    document.getElementById('admAvatar').textContent = (d.name || '?').split(' ').map(function(w){return w.charAt(0);}).slice(0,2).join('').toUpperCase();
    document.getElementById('admName').textContent = d.name || '—';
    document.getElementById('admTicket').textContent = d.ticket || '—';
    var st = document.getElementById('admStatus');
    st.textContent = d.status || '—';
    st.className = 'badge badge-' + (d.statusColor || 'neutral') + ' badge-dotted';
    document.getElementById('admDateMeta').textContent = (d.registered ? 'Registered ' + d.registered : '') + (d.checkedin ? ' · Admitted ' + d.checkedin : '');
    document.getElementById('admEvent').textContent = d.event || '—';
    document.getElementById('admPhone').textContent = d.phone || '—';
    document.getElementById('admEmail').textContent = d.email || '—';
    document.getElementById('admFellowship').textContent = d.fellowship || '—';
    document.getElementById('admRegion').textContent = d.region || '—';
    document.getElementById('admPaid').textContent = 'Fee TZS ' + (d.fee || '0') + ' · Paid TZS ' + (d.paid || '0');
    document.getElementById('admMethod').textContent = d.method ? d.method.charAt(0).toUpperCase() + d.method.slice(1) : '—';
    document.getElementById('admRegistered').textContent = d.registered || '—';
    document.getElementById('admCheckedIn').textContent = d.checkedin || 'Not admitted';
    document.getElementById('admCheckedInBy').textContent = d.checkedinby || '—';
    document.getElementById('admNotes').textContent = d.notes || '—';
    var link = document.getElementById('admTicketLink');
    link.href = BASE + '/' + d.id + '/ticket';
    document.getElementById('admAdmitCode').value = d.ticket || '';
    document.getElementById('admAdmitForm').style.display = d.checkedin ? 'none' : 'block';
    openDrawerById('admitDetailDrawer');
  }
});
</script>
@endpush
