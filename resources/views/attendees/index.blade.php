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
    <button type="button" class="btn btn-accent" data-modal-open="registerModal">+ Register Attendee</button>
  </div>

  <form class="toolbar" method="GET" action="{{ route('attendees.index') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ $v('q') }}" placeholder="Search by name, phone, email..."></div>
    <select class="filter-select" name="event_id" onchange="this.form.submit()">
      <option value="">All Events</option>
      @foreach($events as $e)<option value="{{ $e->id }}" {{ $v('eventId')==$e->id ? 'selected' : '' }}>{{ $e->title }}</option>@endforeach
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
          <tr>
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
              @php
                  $bal = ($a->fee_amount !== null) ? max(0, $a->fee_amount - ($a->amount_paid ?? 0)) : null;
              @endphp
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
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-att-{{ $a->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-att-{{ $a->id }}">
                  <button type="button" data-view-attendee
                          data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-event="{{ $a->event?->title }}"
                          data-phone="{{ $a->phone }}" data-email="{{ $a->email }}"
                          data-amount="{{ $a->amount_paid }}" data-fee="{{ $a->fee_amount }}"
                          data-method="{{ $a->payment_method }}"
                          data-status="{{ $a->getStatusLabel() }}" data-registered="{{ $a->registered_on?->format('d M Y') }}"
                          data-notes="{{ $a->notes }}">View Details</button>
                  <button type="button" data-record-payment data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-amount="{{ $a->amount_paid }}">Record Payment</button>
                  <button type="button" data-send-sms data-id="{{ $a->id }}" data-name="{{ $a->name }}" data-phone="{{ $a->phone }}">Send SMS</button>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state" style="padding:40px 20px"><h3>No registrations yet</h3><p>Register your first attendee now — an SMS confirmation is sent automatically.</p><button type="button" class="btn btn-accent" data-modal-open="registerModal">+ Register Attendee</button></div></td></tr>
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

<div class="modal-overlay" id="registerModal">
  <div class="modal-box lg">
    <div class="modal-head">
      <div><h3>Register Attendee</h3><p>Register for an event — an SMS confirmation is sent automatically</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('attendees.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field"><label>Full Name</label><input name="name" id="regName" placeholder="Full name" value="{{ old('name') }}" required></div>
          <div class="field"><label>Phone</label><input name="phone" id="regPhone" placeholder="+255 7XX XXX XXX" value="{{ old('phone') }}"></div>
          <div class="field full"><label>Email</label><input name="email" id="regEmail" placeholder="email@example.com" value="{{ old('email') }}"></div>
          <div class="field"><label>Amount to Pay (TZS)</label><input type="number" name="fee_amount" id="regFee" value="{{ old('fee_amount', $defaultFee ?? 10000) }}" readonly style="background:var(--blue-light);font-weight:700;color:var(--navy-900)"></div>
          <div class="field"><label>Pickup Location</label><select name="pickup_location" required>
            <option value="">— Select —</option>
            @foreach($pickupLocations ?? [] as $pk => $pl)<option value="{{ $pk }}" @if(old('pickup_location')===$pk) selected @endif>{{ $pl }}</option>@endforeach
          </select></div>
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
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Register &amp; Notify</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="detailsModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Attendee Details</h3><p id="detailsId" class="cu-sub">—</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <div class="profile-detail">
        <div class="cell-avatar avatar-lg" id="detailsAvatar">—</div>
        <div><div class="cu-name" id="detailsName" style="font-size:17px">—</div><span class="badge badge-neutral badge-dotted" id="detailsStatus">—</span></div>
      </div>
      <div class="info-grid">
        <div class="info-row"><span>Event</span><b id="detailsEvent">—</b></div>
        <div class="info-row"><span>Phone</span><b id="detailsPhone">—</b></div>
        <div class="info-row"><span>Email</span><b id="detailsEmail">—</b></div>
        <div class="info-row"><span>Fee Amount</span><b id="detailsFee">—</b></div>
        <div class="info-row"><span>Fee Paid</span><b id="detailsAmount">—</b></div>
        <div class="info-row"><span>Balance</span><b id="detailsBalance">—</b></div>
        <div class="info-row"><span>Payment Method</span><b id="detailsMethod">—</b></div>
        <div class="info-row"><span>Registered</span><b id="detailsRegistered">—</b></div>
        <div class="info-row full"><span>Notes</span><b id="detailsNotes">—</b></div>
      </div>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="paymentModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Record Attendee Payment</h3><p id="paymentName">—</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="paymentForm">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><div class="info-row"><span>Amount Paid So Far</span><b id="paymentPaid">—</b></div></div>
          <div class="field"><label>Amount (TZS)</label><input type="number" step="0.01" name="amount" id="paymentAmount" required min="1"></div>
          <div class="field"><label>Payment Method</label><select name="method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile</option></select></div>
          <div class="field"><label>Reference</label><input name="reference" placeholder="Txn / receipt reference"></div>
          <div class="field"><label>Payment Date</label><input type="date" name="pay_date" value="{{ now()->format('Y-m-d') }}"></div>
          <div class="field full">
            <label class="check-line"><input type="checkbox" name="notify_sms" value="1" checked> Send thank-you SMS to this attendee</label>
            <div class="field-hint">Automatically notifies the attendee with a payment thank-you message.</div>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Record Payment &amp; Notify</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="smsModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Send SMS</h3><p id="smsName">—</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="smsForm">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><label>Phone Number</label><input name="phone" id="smsPhone" placeholder="+255 7XX XXX XXX" required></div>
          <div class="field full"><label>Message</label><textarea name="message" id="smsMessage" rows="5" placeholder="Type your SMS message..." required></textarea></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Send SMS</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-attendee]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      var initials = (d.name||'?').trim().split(' ').map(function(w){return w.charAt(0);}).slice(0,2).join('');
      document.getElementById('detailsAvatar').textContent = initials;
      document.getElementById('detailsId').textContent = '#' + (d.id || '—');
      document.getElementById('detailsName').textContent = d.name || '—';
      document.getElementById('detailsStatus').textContent = d.status || '—';
      document.getElementById('detailsStatus').className = 'badge badge-neutral badge-dotted';
      document.getElementById('detailsEvent').textContent = d.event || '—';
      document.getElementById('detailsPhone').textContent = d.phone || '—';
      document.getElementById('detailsEmail').textContent = d.email || '—';
      var fee = Number(d.fee || 0);
      var paid = Number(d.amount || 0);
      var balance = fee > 0 ? Math.max(0, fee - paid) : 0;
      document.getElementById('detailsFee').textContent = fee > 0 ? 'TZS ' + fee.toLocaleString() : '—';
      document.getElementById('detailsAmount').textContent = paid > 0 ? 'TZS ' + paid.toLocaleString() : '—';
      document.getElementById('detailsBalance').textContent = fee > 0 ? 'TZS ' + balance.toLocaleString() : '—';
      document.getElementById('detailsBalance').style.color = fee > 0 && balance > 0 ? 'var(--warning)' : 'inherit';
      document.getElementById('detailsMethod').textContent = d.method ? d.method.charAt(0).toUpperCase() + d.method.slice(1) : '—';
      document.getElementById('detailsRegistered').textContent = d.registered || '—';
      document.getElementById('detailsNotes').textContent = d.notes || '—';
      openModalById('detailsModal');
    });
  });

  document.querySelectorAll('[data-record-payment]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('paymentName').textContent = d.name || 'Attendee';
      document.getElementById('paymentPaid').textContent = 'TZS ' + Number(d.amount || 0).toLocaleString();
      document.getElementById('paymentForm').action = "{{ url('/attendees') }}/" + d.id + "/payments";
      document.getElementById('paymentForm').reset();
      document.getElementById('paymentAmount').value = '';
      openModalById('paymentModal');
    });
  });

  document.querySelectorAll('[data-send-sms]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('smsName').textContent = d.name || 'Attendee';
      document.getElementById('smsPhone').value = d.phone || '';
      document.getElementById('smsMessage').value = 'Hello ' + (d.name||'') + ',\\nYou are registered for Open Gate Camp. We look forward to seeing you!';
      document.getElementById('smsForm').action = "{{ url('/attendees') }}/" + d.id + "/sms";
      openModalById('smsModal');
    });
  });
});
</script>
@endpush