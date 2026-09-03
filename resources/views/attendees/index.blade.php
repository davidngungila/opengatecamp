@extends('layouts.app')

@section('title', 'Registrations — Open Gate Camp Mission')
@section('crumb', 'Events / Registrations')
@section('page_title', 'Registrations')

@section('content')
@php
    $v = fn($f) => old($f, $filters[$f] ?? null);
@endphp
<div class="fade-in">
  <div class="section-head">
    <div><h2>Attendee Registrations</h2><div class="sub">
      {{ $totals['registered'] }} registered · {{ $totals['confirmed'] }} confirmed · {{ $totals['attended'] }} attended
    </div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="attRegisterDrawer">+ Register Attendee</button>
  </div>

  <form class="toolbar" method="GET" action="{{ route('attendees.index') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ $v('q') }}" placeholder="Search by name, phone, email..."></div>
    <select class="filter-select" name="event" onchange="this.form.submit()">
      <option value="">All Events</option>
      @foreach($events as $e)<option value="{{ $e->slug }}" {{ $v('event')==$e->slug ? 'selected' : '' }}>{{ $e->title }}</option>@endforeach
    </select>
    <select class="filter-select" name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      @foreach($statuses as $k=>$s)<option value="{{ $k }}" {{ $v('status')===$k ? 'selected' : '' }}>{{ $s }}</option>@endforeach
    </select>
    <button type="button" class="btn btn-secondary btn-sm" onclick="toast('Export started','success')">Export</button>
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Attendee</th><th>Paid (TZS)</th><th>Balance</th><th>Status</th><th>Registered</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($attendees as $a)
          @php
            $bal = ($a->fee_amount !== null) ? max(0, $a->fee_amount - ($a->amount_paid ?? 0)) : null;
          @endphp
          <tr style="cursor:pointer" data-view-attendee
            data-id="{{ $a->id }}"
            data-name="{{ $a->name }}"
            data-event="{{ $a->event?->title }}"
            data-phone="{{ $a->phone }}"
            data-email="{{ $a->email }}"
            data-amount="{{ $a->amount_paid }}"
            data-fee="{{ $a->fee_amount }}"
            data-method="{{ $a->payment_method }}"
            data-status="{{ $a->getStatusLabel() }}"
            data-status-key="{{ $a->status }}"
            data-registered="{{ $a->registered_on?->format('d M Y') }}"
            data-checked-in="{{ $a->checked_in_at ? 'Yes' : 'No' }}"
            data-fellowship="{{ $a->fellowship ?? '—' }}"
            data-pickup="{{ $a->pickupLocation?->name ?? '—' }}"
            data-notes="{{ $a->notes }}"
            data-can-ticket="{{ $a->hasCompletedContribution() ? '1' : '0' }}"
            data-ticket="{{ $a->hasCompletedContribution() ? $a->getTicketNo() : '' }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $a->name ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div><div class="cu-name">{{ $a->name ?? '—' }}</div><div class="cu-sub">{{ $a->member?->member_no ?? 'Non-member' }}</div></div>
              </div>
            </td>
            <td>
              <b style="color:{{ ($a->amount_paid ?? 0) > 0 ? 'var(--success)' : 'var(--text-tertiary)' }}">{{ number_format($a->amount_paid) }}</b>
              @if($a->payment_method)<span class="badge badge-neutral badge-dotted" style="margin-left:4px">{{ ucfirst($a->payment_method) }}</span>@endif
            </td>
            <td>
              @if($bal !== null)
                <b style="color:{{ $bal > 0 ? 'var(--warning)' : 'var(--success)' }}">{{ number_format($bal) }}</b>
              @else
                <span class="badge badge-neutral badge-dotted">—</span>
              @endif
            </td>
            <td>
              <span class="badge badge-{{ $a->getStatusColor() }} badge-dotted">{{ $a->getStatusLabel() }}</span>
              @if($a->status==='pending')<span class="badge badge-warning badge-dotted" style="margin-left:4px">Needs confirmation</span>@endif
            </td>
            <td>{{ $a->registered_on?->format('d M Y') }}
              @if($a->checked_in_at)
                <div class="badge badge-success badge-dotted">Checked in</div>
              @endif
            </td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-att-{{ $a->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-att-{{ $a->id }}">
                  <button type="button" data-view-attendee-trigger
                          data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-event="{{ $a->event?->title }}"
                          data-phone="{{ $a->phone }}" data-email="{{ $a->email }}"
                          data-amount="{{ $a->amount_paid }}" data-fee="{{ $a->fee_amount }}"
                          data-method="{{ $a->payment_method }}"
                          data-status="{{ $a->getStatusLabel() }}" data-status-key="{{ $a->status }}"
                          data-registered="{{ $a->registered_on?->format('d M Y') }}"
                          data-checked-in="{{ $a->checked_in_at ? 'Yes' : 'No' }}"
                          data-fellowship="{{ $a->fellowship ?? '—' }}"
                          data-pickup="{{ $a->pickupLocation?->name ?? '—' }}"
                          data-notes="{{ $a->notes }}">View Details</button>
                  <button type="button" data-record-att-payment data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-amount="{{ $a->amount_paid }}">Record Payment</button>
                  <button type="button" data-send-att-sms data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-phone="{{ $a->phone }}">Send SMS</button>
                  @if($a->hasCompletedContribution())
                  <a href="{{ route('attendees.ticket.pdf', $a) }}" target="_blank" class="action-link" style="display:block;width:100%;padding:8px 14px;font-size:12.5px;color:var(--text-primary);text-decoration:none;box-sizing:border-box">Print Ticket (PDF)</a>
                  <button type="button" data-send-att-ticket data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-phone="{{ $a->phone }}" data-ticket="{{ $a->getTicketNo() }}">Send Ticket SMS</button>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state" style="padding:40px 20px"><h3>No registrations yet</h3><p>Register your first attendee now — an SMS confirmation is sent automatically.</p><button type="button" class="btn btn-accent" data-drawer-open="attRegisterDrawer">+ Register Attendee</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $attendees->firstItem() ?? 0 }}–{{ $attendees->lastItem() ?? 0 }} of {{ $attendees->total() }} records</span>
      <div class="pagination">{{ $attendees->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="attRegisterDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Register Attendee</h3><p>Register for an event — an SMS confirmation is sent automatically</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('attendees.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Full Name</label><input name="name" id="regName" placeholder="Full name" value="{{ old('name') }}" required></div>
          <div class="field"><label>Phone</label><input name="phone" id="regPhone" placeholder="+255 7XX XXX XXX" value="{{ old('phone') }}"></div>
          <div class="field full"><label>Email</label><input name="email" id="regEmail" placeholder="email@example.com" value="{{ old('email') }}"></div>
          <div class="field"><label>University Fellowship</label><select name="fellowship">
            <option value="">— Select —</option>
            @foreach($fellowships ?? [] as $f)<option value="{{ $f }}" @if(old('fellowship')===$f) selected @endif>{{ $f }}</option>@endforeach
          </select></div>
          <div class="field"><label>Coming From</label><select name="pickup_location" required>
            <option value="">— Select —</option>
            @foreach($pickupLocations ?? [] as $pk => $pl)<option value="{{ $pk }}" @if(old('pickup_location')===$pk) selected @endif>{{ $pl }}</option>@endforeach
          </select></div>
          <div class="field"><label>Amount to Pay (TZS)</label><input type="number" name="fee_amount" id="regFee" value="{{ old('fee_amount', $defaultFee ?? 10000) }}" readonly style="background:var(--blue-light);font-weight:700;color:var(--navy-900)"></div>
          <div class="field"><label>Amount Paid (TZS)</label><input type="number" step="0.01" min="0" name="amount_paid" value="{{ old('amount_paid', 0) }}"></div>
          <div class="field"><label>Payment Method</label><select name="payment_method">
            <option value="">— Select —</option>
            <option value="cash" @if(old('payment_method')==='cash') selected @endif>Cash</option>
            <option value="bank" @if(old('payment_method')==='bank') selected @endif>Bank</option>
            <option value="mobile" @if(old('payment_method')==='mobile') selected @endif>Mobile</option>
          </select></div>
          <div class="field"><label>Status</label><select name="status">
            @foreach($statuses as $k=>$s)<option value="{{ $k }}" @if(old('status')==$k) selected @endif>{{ $s }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Notes</label><textarea name="notes" placeholder="Any notes about this attendee">{{ old('notes') }}</textarea></div>
          <div class="field full">
            <label class="check-line"><input type="checkbox" name="send_sms" value="1" checked> Send SMS confirmation to this attendee</label>
            <div class="field-hint">Uses {{ $attendeePhoneHint ?? 'the phone number above' }} — requires SMS API token.</div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Register &amp; Notify</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="attDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Attendee Details</h3><p id="attDetailsId" class="cu-sub">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="cell-avatar avatar-lg" id="attDetailsAvatar">—</div>
        <div><div class="cu-name" id="attDetailsName" style="font-size:17px">—</div><span class="badge badge-neutral badge-dotted" id="attDetailsStatus">—</span></div>
      </div>
      <div class="info-grid">
        <div class="info-row"><span>Event</span><b id="attDetailsEvent">—</b></div>
        <div class="info-row"><span>Phone</span><b id="attDetailsPhone">—</b></div>
        <div class="info-row"><span>Email</span><b id="attDetailsEmail">—</b></div>
        <div class="info-row"><span>Fee Amount</span><b id="attDetailsFee">—</b></div>
        <div class="info-row"><span>Fee Paid</span><b id="attDetailsAmount">—</b></div>
        <div class="info-row"><span>Balance</span><b id="attDetailsBalance">—</b></div>
        <div class="info-row"><span>Payment Method</span><b id="attDetailsMethod">—</b></div>
        <div class="info-row"><span>Fellowship</span><b id="attDetailsFellowship">—</b></div>
        <div class="info-row"><span>Coming From</span><b id="attDetailsPickup">—</b></div>
        <div class="info-row"><span>Registered</span><b id="attDetailsRegistered">—</b></div>
        <div class="info-row"><span>Checked In</span><b id="attDetailsCheckedIn">—</b></div>
        <div class="info-row full"><span>Notes</span><b id="attDetailsNotes" style="white-space:normal">—</b></div>
      </div>

      <div class="payments-head" style="margin:18px 0 10px">
        <span>Quick Actions</span>
      </div>
      <div class="drawer-actions">
        <button type="button" class="daction" id="attActPayment">
          <span class="daction-ico" style="background:rgba(16,185,129,.12);color:var(--success)">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg>
          </span>
          <span class="daction-txt"><b>Record Payment</b><small>Log a fee payment for this attendee</small></span>
          <span class="daction-arrow">›</span>
        </button>
        <button type="button" class="daction" id="attActSms">
          <span class="daction-ico" style="background:rgba(59,130,246,.12);color:var(--blue-accent)">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
          </span>
          <span class="daction-txt"><b>Send SMS</b><small>Send a text message to this attendee</small></span>
          <span class="daction-arrow">›</span>
        </button>
        <a href="javascript:void(0)" class="daction" id="attActTicket" style="display:none">
          <span class="daction-ico" style="background:rgba(139,92,246,.12);color:purple">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 010 6v2a2 2 0 002 2h16a2 2 0 002-2v-2a3 3 0 000-6V7a2 2 0 00-2-2H4a2 2 0 00-2 2z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
          </span>
          <span class="daction-txt"><b>Print Ticket (PDF)</b><small>Download this attendee's ticket</small></span>
          <span class="daction-arrow">›</span>
        </a>
        <button type="button" class="daction" id="attActTicketSms" style="display:none">
          <span class="daction-ico" style="background:rgba(16,185,129,.12);color:var(--success)">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          </span>
          <span class="daction-txt"><b>Send Ticket SMS</b><small>Send the ticket number by SMS</small></span>
          <span class="daction-arrow">›</span>
        </button>
      </div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="attPaymentDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Record Attendee Payment</h3><p id="attPaymentName">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="attPaymentForm">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><div class="info-row"><span>Amount Paid So Far</span><b id="attPaymentPaid">—</b></div></div>
          <div class="field"><label>Amount (TZS)</label><input type="number" step="0.01" name="amount" id="attPaymentAmount" required min="1"></div>
          <div class="field"><label>Payment Method</label><select name="method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile</option></select></div>
          <div class="field"><label>Reference</label><input name="reference" placeholder="Txn / receipt reference"></div>
          <div class="field"><label>Payment Date</label><input type="date" name="pay_date" value="{{ now()->format('Y-m-d') }}"></div>
          <div class="field full">
            <label class="check-line"><input type="checkbox" name="notify_sms" value="1" checked> Send thank-you SMS to this attendee</label>
            <div class="field-hint">Automatically notifies the attendee with a payment thank-you message.</div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Record Payment &amp; Notify</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="attSmsDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Send SMS</h3><p id="attSmsName">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="attSmsForm">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Phone Number</label><input name="phone" id="attSmsPhone" placeholder="+255 7XX XXX XXX" required></div>
          <div class="field full"><label>Message</label><textarea name="message" id="attSmsMessage" rows="5" placeholder="Type your SMS message..." required></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Send SMS</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  var curAtt = null;

  function setDetailForm(){
    document.getElementById('attPaymentName').textContent = curAtt.name || 'Attendee';
    document.getElementById('attPaymentPaid').textContent = 'TZS ' + Number(curAtt.amount || 0).toLocaleString();
    document.getElementById('attPaymentForm').action = "{{ url('/attendees') }}/" + curAtt.id + "/payments";
    document.getElementById('attPaymentForm').reset();
    document.getElementById('attPaymentAmount').value = '';
  }
  function setSmsForm(){
    document.getElementById('attSmsName').textContent = curAtt.name || 'Attendee';
    document.getElementById('attSmsPhone').value = curAtt.phone || '';
    document.getElementById('attSmsMessage').value = 'Hello ' + (curAtt.name||'') + ',\\nYou are registered for Open Gate Camp. We look forward to seeing you!';
    document.getElementById('attSmsForm').action = "{{ url('/attendees') }}/" + curAtt.id + "/sms";
  }

  document.getElementById('attActPayment').addEventListener('click', function(){
    closeDrawerById('attDetailDrawer');
    setDetailForm();
    openDrawerById('attPaymentDrawer');
  });
  document.getElementById('attActSms').addEventListener('click', function(){
    closeDrawerById('attDetailDrawer');
    setSmsForm();
    openDrawerById('attSmsDrawer');
  });
  document.getElementById('attActTicketSms').addEventListener('click', function(){
    var msg = 'Send ticket (' + (curAtt.ticket||'') + ') to ' + (curAtt.name||'this attendee') + ' by SMS?';
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = "{{ url('/attendees') }}/" + curAtt.id + "/ticket/sms";
    var t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value = document.querySelector('meta[name="csrf-token"]').content;
    f.appendChild(t);
    document.body.appendChild(f);
    confirmAction(f, 'Send ticket by SMS', msg, 'Send SMS');
  });

  function openAttDrawer(d){
    curAtt = d;
    var initials = (d.name||'?').trim().split(' ').map(function(w){return w.charAt(0);}).slice(0,2).join('');
    document.getElementById('attDetailsAvatar').textContent = initials;
    document.getElementById('attDetailsId').textContent = '#' + (d.id || '—');
    document.getElementById('attDetailsName').textContent = d.name || '—';
    var st = document.getElementById('attDetailsStatus');
    var colors = {registered:'success',pending:'warning',attended:'accent',cancelled:'neutral'};
    st.textContent = d.status || '—';
    st.className = 'badge badge-' + (colors[d.statusKey||''] || 'neutral') + ' badge-dotted';
    document.getElementById('attDetailsEvent').textContent = d.event || '—';
    document.getElementById('attDetailsPhone').textContent = d.phone || '—';
    document.getElementById('attDetailsEmail').textContent = d.email || '—';
    var fee = Number(d.fee || 0);
    var paid = Number(d.amount || 0);
    var balance = fee > 0 ? Math.max(0, fee - paid) : 0;
    document.getElementById('attDetailsFee').textContent = fee > 0 ? 'TZS ' + fee.toLocaleString() : '—';
    document.getElementById('attDetailsAmount').textContent = paid > 0 ? 'TZS ' + paid.toLocaleString() : '—';
    document.getElementById('attDetailsBalance').textContent = fee > 0 ? 'TZS ' + balance.toLocaleString() : '—';
    document.getElementById('attDetailsBalance').style.color = fee > 0 && balance > 0 ? 'var(--warning)' : 'inherit';
    document.getElementById('attDetailsMethod').textContent = d.method ? d.method.charAt(0).toUpperCase() + d.method.slice(1) : '—';
    document.getElementById('attDetailsFellowship').textContent = d.fellowship || '—';
    document.getElementById('attDetailsPickup').textContent = d.pickup || '—';
    document.getElementById('attDetailsRegistered').textContent = d.registered || '—';
    document.getElementById('attDetailsCheckedIn').textContent = d.checkedIn || '—';
    document.getElementById('attDetailsNotes').textContent = d.notes || '—';

    var canTicket = d.canTicket === '1' || d.canTicket === 1;
    var ticketUrl = "{{ url('/attendees') }}/" + d.id + "/ticket";
    var ticketBtn = document.getElementById('attActTicket');
    ticketBtn.style.display = canTicket ? 'flex' : 'none';
    ticketBtn.href = canTicket ? ticketUrl : 'javascript:void(0)';
    ticketBtn.target = canTicket ? '_blank' : '';
    document.getElementById('attActTicketSms').style.display = canTicket ? 'flex' : 'none';

    openDrawerById('attDetailDrawer');
  }

  document.querySelectorAll('[data-view-attendee]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('.action-menu-wrap') || e.target.closest('form') || e.target.closest('button') || e.target.closest('a')) return;
      openAttDrawer(tr.dataset);
    });
  });

  document.querySelectorAll('[data-view-attendee-trigger]').forEach(function(btn){
    btn.addEventListener('click', function(){ openAttDrawer(btn.dataset); });
  });

  document.querySelectorAll('[data-record-att-payment]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('attPaymentName').textContent = d.name || 'Attendee';
      document.getElementById('attPaymentPaid').textContent = 'TZS ' + Number(d.amount || 0).toLocaleString();
      document.getElementById('attPaymentForm').action = "{{ url('/attendees') }}/" + d.id + "/payments";
      document.getElementById('attPaymentForm').reset();
      document.getElementById('attPaymentAmount').value = '';
      openDrawerById('attPaymentDrawer');
    });
  });

  document.querySelectorAll('[data-send-att-sms]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('attSmsName').textContent = d.name || 'Attendee';
      document.getElementById('attSmsPhone').value = d.phone || '';
      document.getElementById('attSmsMessage').value = 'Hello ' + (d.name||'') + ',\\nYou are registered for Open Gate Camp. We look forward to seeing you!';
      document.getElementById('attSmsForm').action = "{{ url('/attendees') }}/" + d.id + "/sms";
      openDrawerById('attSmsDrawer');
    });
  });

  document.querySelectorAll('[data-send-att-ticket]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      var msg = 'Send ticket (' + (d.ticket||'') + ') to ' + (d.name||'this attendee') + ' by SMS?';
      var f = document.createElement('form');
      f.method = 'POST';
      f.action = "{{ url('/attendees') }}/" + d.id + "/ticket/sms";
      var t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value = document.querySelector('meta[name="csrf-token"]').content;
      f.appendChild(t);
      document.body.appendChild(f);
      confirmAction(f, 'Send ticket by SMS', msg, 'Send SMS');
    });
  });
});
</script>
@endpush