@extends('layouts.app')

@section('title', 'Registrations — OpenGate Camp Connect')
@section('crumb', 'Events / Registrations')
@section('page_title', 'Registrations')

@section('content')
<style>
.info-wrap{position:relative;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle}
.info-wrap .info-ico{width:16px;height:16px;border-radius:50%;background:var(--blue-light);color:var(--blue-accent);display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;border:1px solid rgba(37,99,235,.18);cursor:help;flex-shrink:0}
.info-bubble{position:absolute;left:50%;bottom:calc(100% + 8px);transform:translateX(-50%);background:#0f172a;color:#fff;font-size:11.5px;line-height:1.5;font-weight:500;padding:10px 12px;border-radius:9px;min-width:240px;max-width:320px;white-space:normal;box-shadow:0 10px 28px rgba(0,0,0,.22);opacity:0;visibility:hidden;transition:opacity .15s,visibility .15s;z-index:50;text-align:left;pointer-events:none}
.info-bubble::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:6px solid transparent;border-top-color:#0f172a}
.info-wrap:hover .info-bubble,.info-wrap:focus-within .info-bubble{opacity:1;visibility:visible}
.info-bubble code{background:rgba(255,255,255,.12);padding:1px 5px;border-radius:4px;font-family:ui-monospace,monospace;font-size:11px}
</style>
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
    <a class="btn btn-secondary btn-sm" href="{{ route('attendees.export', request()->query()) }}">Export Report (PDF)</a>
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
            data-id="{{ $a->hashed_id }}"
            data-nid="{{ $a->id }}"
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
            data-registered-by="{{ $a->registered_by ?? '—' }}"
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
              @if($a->registered_by)<div class="badge badge-neutral badge-dotted" style="margin-top:3px">by {{ $a->registered_by }}</div>@endif
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
                          data-id="{{ $a->hashed_id }}" data-nid="{{ $a->id }}" data-name="{{ $a->name }}" data-event="{{ $a->event?->title }}"
                          data-phone="{{ $a->phone }}" data-email="{{ $a->email }}"
                          data-amount="{{ $a->amount_paid }}" data-fee="{{ $a->fee_amount }}"
                          data-method="{{ $a->payment_method }}"
                          data-status="{{ $a->getStatusLabel() }}" data-status-key="{{ $a->status }}"
                          data-registered="{{ $a->registered_on?->format('d M Y') }}"
                          data-registered-by="{{ $a->registered_by ?? '—' }}"
                          data-checked-in="{{ $a->checked_in_at ? 'Yes' : 'No' }}"
                          data-fellowship="{{ $a->fellowship ?? '—' }}"
                          data-pickup="{{ $a->pickupLocation?->name ?? '—' }}"
                          data-notes="{{ $a->notes }}">View Details</button>
                  <button type="button" data-update-att-status data-id="{{ $a->hashed_id }}" data-name="{{ $a->name }}" data-status="{{ $a->status }}" data-notes="{{ $a->notes }}">Update Status</button>
                  <button type="button" data-record-att-payment data-id="{{ $a->hashed_id }}" data-name="{{ $a->name }}" data-amount="{{ $a->amount_paid }}">Record Payment</button>
                  <button type="button" data-send-att-sms data-id="{{ $a->hashed_id }}" data-name="{{ $a->name }}" data-phone="{{ $a->phone }}">Send SMS</button>
                  @if($a->hasCompletedContribution())
                  <button type="button" data-open-att-ticket data-id="{{ $a->hashed_id }}" data-name="{{ $a->name }}" style="display:block;width:100%;padding:8px 14px;font-size:12.5px;color:var(--text-primary);text-decoration:none;box-sizing:border-box;background:none;border:none;text-align:left;cursor:pointer">Open Ticket (PDF)</button>
                  <button type="button" data-send-att-ticket data-id="{{ $a->hashed_id }}" data-name="{{ $a->name }}" data-phone="{{ $a->phone }}" data-ticket="{{ $a->getTicketNo() }}">Send Ticket SMS</button>
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
      <div><h3>Register Attendee</h3><p>Register for an event — an SMS confirmation is sent automatically; a payment-received SMS follows if marked as paid</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('attendees.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Full Name</label><input name="name" id="regName" placeholder="Full name" value="{{ old('name') }}" required></div>
          <div class="field"><label>Phone</label><input name="phone" id="regPhone" placeholder="+255 7XX XXX XXX" value="{{ old('phone') }}"></div>
          <div class="field full"><label>Email</label><input name="email" id="regEmail" placeholder="email@example.com" value="{{ old('email') }}"></div>
          <div class="field"><label>Coming From @if(!empty($isFellowshipLeader) && $isFellowshipLeader)<span style="font-weight:400;color:var(--text-tertiary)">— auto-filled from your fellowship</span>@endif</label><select name="pickup_location" id="regPickup" required>
            <option value="">— Select —</option>
            @foreach($pickupLocations ?? [] as $pk => $pl)<option value="{{ $pk }}" @if(old('pickup_location')===$pk) selected @endif>{{ $pl }}</option>@endforeach
          </select></div>
          <div class="field"><label>University Fellowship @if(!empty($isFellowshipLeader) && $isFellowshipLeader)<span style="font-weight:400;color:var(--text-tertiary)">— auto-filled from your fellowship</span>@endif</label><select name="fellowship" id="regFellowship" @if(!empty($isFellowshipLeader) && $isFellowshipLeader && count($fellowships)===1) disabled @endif>
            <option value="">— Select —</option>
            @foreach($fellowships ?? [] as $f)<option value="{{ $f }}" @if(old('fellowship')===$f) selected @endif>{{ $f }}</option>@endforeach
          </select>
            @if(!empty($isFellowshipLeader) && $isFellowshipLeader && count($fellowships)===1)
              <input type="hidden" name="fellowship" id="regFellowshipHidden" value="{{ $fellowships[0] }}">
            @endif
          </div>
          <div class="field"><label>Amount to Pay (TZS)</label><input type="number" name="fee_amount" id="regFee" value="{{ old('fee_amount', $defaultFee ?? 10000) }}" readonly style="background:var(--blue-light);font-weight:700;color:var(--navy-900)"></div>
          <div class="field">
            <label>Has Paid?</label>
            <select name="is_paid" id="regIsPaid">
              <option value="0" @if(old('is_paid', 0) != 1) selected @endif>No — Not Paid</option>
              <option value="1" @if(old('is_paid') == 1) selected @endif>Yes — Paid</option>
            </select>
          </div>
          <div class="full" id="regPaymentFields" @if(old('is_paid') != 1) style="display:none" @endif>
            <div class="form-grid" style="margin:0">
              <div class="field"><label>Amount Paid (TZS)</label><input type="number" step="0.01" min="0" name="amount_paid" id="regAmount" value="{{ old('amount_paid') }}"></div>
              <div class="field"><label>Payment Method</label><select name="payment_method">
                <option value="">— Select —</option>
                <option value="cash" @if(old('payment_method')==='cash') selected @endif>Cash</option>
                <option value="bank" @if(old('payment_method')==='bank') selected @endif>Bank</option>
                <option value="mobile" @if(old('payment_method')==='mobile') selected @endif>Mobile</option>
              </select></div>
            </div>
          </div>
          <div class="field"><label>Status</label><select name="status">
            @foreach($statuses as $k=>$s)<option value="{{ $k }}" @if(old('status')==$k) selected @endif>{{ $s }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Notes</label><textarea name="notes" placeholder="Any notes about this attendee">{{ old('notes') }}</textarea></div>
          <div class="field full">
            <label class="check-line"><input type="checkbox" name="send_sms" value="1" checked> Send SMS confirmation to this attendee</label>
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
        <div class="info-row"><span>Registered By</span><b id="attDetailsRegisteredBy">—</b></div>
        <div class="info-row"><span>Checked In</span><b id="attDetailsCheckedIn">—</b></div>
        <div class="info-row full"><span>Notes</span><b id="attDetailsNotes" style="white-space:normal">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px;display:flex;align-items:center;gap:6px">
        <span>Transactions</span><span class="payments-count" id="attDetailsTxCount">0</span>
        <span class="info-wrap" tabindex="0" style="margin-left:4px"><span class="info-ico">i</span><span class="info-bubble">All payments linked to this attendee. Click <b>Receipt</b> to preview each transaction.</span></span>
      </div>
      <div id="attDetailsTxLoading" style="display:none;padding:12px;text-align:center;color:var(--text-tertiary);font-size:13px">Loading transactions…</div>
      <div id="attDetailsTxEmpty" style="display:none;padding:12px;text-align:center;color:var(--text-tertiary);font-size:13px;border:1px dashed var(--border-strong);border-radius:10px">No transactions yet. Record a payment to see receipt.</div>
      <div id="attDetailsTxList" class="payments-list" style="margin-bottom:14px"></div>

      <div class="payments-head" style="margin:18px 0 10px">
        <span>Quick Actions</span>
      </div>
      <div class="drawer-actions">
        <button type="button" class="daction" id="attActStatus">
          <span class="daction-ico" style="background:rgba(99,102,241,.12);color:#6366f1">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l3 3 3-3"/></svg>
          </span>
          <span class="daction-txt"><b>Update Status</b><small>Change registration status</small></span>
          <span class="daction-arrow">›</span>
        </button>
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
        <button type="button" class="daction" id="attActTicket" style="display:none">
          <span class="daction-ico" style="background:rgba(139,92,246,.12);color:purple">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 010 6v2a2 2 0 002 2h16a2 2 0 002-2v-2a3 3 0 000-6V7a2 2 0 00-2-2H4a2 2 0 00-2 2z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
          </span>
          <span class="daction-txt"><b>Open Ticket (PDF)</b><small>Preview this attendee's ticket</small></span>
          <span class="daction-arrow">›</span>
        </button>
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
<div class="drawer-overlay" id="attStatusDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Update Status</h3><p id="attStatusName">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="attStatusForm">
      @csrf
      @method('PATCH')
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Status</label>
            <select name="status" id="attStatusSelect" required style="width:100%">
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="attended">Attended</option>
              <option value="no_show">No Show</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <small style="color:var(--text-muted);margin-top:4px;display:block">Attended marks arrival and creates ticket if fully paid.</small>
          </div>
          <div class="field full"><label>Note <span style="font-weight:400;color:var(--text-tertiary)">— optional</span></label>
            <textarea name="notes" id="attStatusNotes" rows="3" placeholder="Add a note about this status change (e.g., reason, payment reference, follow-up)"></textarea>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Update Status</button>
      </div>
    </form>
  </div>
</div>
<div class="drawer-overlay" id="attReceiptDrawer">
  <div class="drawer-panel" style="max-width:820px">
    <div class="drawer-head">
      <div><h3>Payment Receipt</h3><p id="attReceiptMeta" style="font-size:12.5px;color:var(--text-tertiary);margin:4px 0 0">Preview</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body" style="padding:0;overflow:hidden;display:flex;flex-direction:column">
      <iframe id="attReceiptFrame" style="width:100%;height:72vh;border:none;background:#f8fafc" src="about:blank"></iframe>
      <div id="attReceiptFallback" style="display:none;padding:16px;text-align:center;font-size:13px;color:var(--text-tertiary)"><span>No receipt available.</span> <a id="attReceiptLink" href="#" target="_blank" style="color:var(--blue-accent)">Open in new tab</a></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <a id="attReceiptOpenNew" href="#" target="_blank" class="btn btn-accent">Open in new tab</a>
    </div>
  </div>
</div>
@include('partials.ticket-preview-drawer')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  var curAtt = null;

  function firstName(name){
    var s = String(name || '').trim();
    if (!s) return '';
    var t = s.split(/\s+/);
    if (/^(Dr|Fr|Rev|Mr|Mrs|Ms|Sr|Br|Prof|Hon)\.?$/i.test(t[0]) && t.length > 1) return t[1];
    return t[0] || '';
  }

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
    document.getElementById('attSmsMessage').value = 'Hello ' + firstName(curAtt.name) + ',\\nYou are registered for {{ \App\Models\Setting::get("event.name", "Open Gate Camp") }}. We look forward to seeing you!';
    document.getElementById('attSmsForm').action = "{{ url('/attendees') }}/" + curAtt.id + "/sms";
  }

  function previewAttReceipt(url, title){
    var frame = document.getElementById('attReceiptFrame');
    var meta = document.getElementById('attReceiptMeta');
    var link = document.getElementById('attReceiptLink');
    var openNew = document.getElementById('attReceiptOpenNew');
    var fallback = document.getElementById('attReceiptFallback');
    if(!url){
      if(frame) frame.style.display = 'none';
      if(fallback) fallback.style.display = 'block';
      if(meta) meta.textContent = title || 'No receipt';
      openDrawerById('attReceiptDrawer');
      return;
    }
    if(fallback) fallback.style.display = 'none';
    if(frame){ frame.style.display = 'block'; frame.src = url; }
    if(meta) meta.textContent = title || 'Receipt preview';
    if(link) link.href = url;
    if(openNew) openNew.href = url;
    openDrawerById('attReceiptDrawer');
  }
  window.previewAttReceipt = previewAttReceipt;

  function loadAttendeeTransactions(hashedId){
    var countEl = document.getElementById('attDetailsTxCount');
    var loadingEl = document.getElementById('attDetailsTxLoading');
    var emptyEl = document.getElementById('attDetailsTxEmpty');
    var listEl = document.getElementById('attDetailsTxList');
    if(!countEl || !listEl) return;
    countEl.textContent = '…';
    loadingEl.style.display = 'block';
    emptyEl.style.display = 'none';
    listEl.innerHTML = '';
    fetch('/api/attendees/' + encodeURIComponent(hashedId) + '/transactions', {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
      .then(function(data){
        loadingEl.style.display = 'none';
        var txs = data.transactions || [];
        countEl.textContent = txs.length;
        if(!txs.length){
          emptyEl.style.display = 'block';
          return;
        }
        txs.forEach(function(tx){
          var div = document.createElement('div');
          div.className = 'pay-item';
          var amount = Number(tx.amount || 0).toLocaleString();
          var date = tx.entry_date || '';
          var entryNo = tx.entry_no || '';
          var desc = tx.description || '';
          var safeUrl = (tx.receipt_url || '').replace(/'/g, "\\'");
          var btn = tx.receipt_url ? '<button type="button" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px" onclick="event.stopPropagation(); previewAttReceipt(\''+safeUrl+'\', \''+entryNo+' — TZS '+amount+'\')">Receipt</button>' : '<span class="badge badge-neutral" style="font-size:10px">No receipt</span>';
          div.style.cursor = tx.receipt_url ? 'pointer' : 'default';
          if(tx.receipt_url){
            div.title = 'Click to preview receipt';
            div.addEventListener('click', function(){ previewAttReceipt(tx.receipt_url, entryNo + ' — TZS ' + amount); });
          }
          div.innerHTML = '<div class="pay-ico" style="background:var(--success-bg);color:var(--success)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M6 10h12"/></svg></div>'
            + '<div class="pay-main"><div class="pm-name">TZS '+amount+' <span style="font-size:11px;color:var(--text-tertiary)">· '+entryNo+'</span></div><div class="pm-sub">'+date+' · '+(desc ? desc.substring(0,70) : '')+'</div></div>'
            + '<div class="pay-amt">'+btn+'</div>';
          listEl.appendChild(div);
        });
      })
      .catch(function(err){
        loadingEl.style.display = 'none';
        emptyEl.style.display = 'block';
        emptyEl.textContent = 'Failed to load transactions: ' + (err.message||'error');
        console.error(err);
      });
  }

  document.getElementById('attActStatus').addEventListener('click', function(){
    closeDrawerById('attDetailDrawer');
    document.getElementById('attStatusName').textContent = curAtt.name || 'Attendee';
    document.getElementById('attStatusSelect').value = curAtt.statusKey || 'pending';
    document.getElementById('attStatusNotes').value = curAtt.notes || '';
    document.getElementById('attStatusForm').action = "{{ url('/attendees') }}/" + curAtt.id + "/status";
    openDrawerById('attStatusDrawer');
  });
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
    var mt = document.querySelector('meta[name="csrf-token"]');
    var t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value = mt ? mt.content : '{{ csrf_token() }}';
    f.appendChild(t);
    document.body.appendChild(f);
    confirmAction(f, 'Send ticket by SMS', msg, 'Send SMS');
  });

  function openAttDrawer(d){
    curAtt = d;
    var initials = (d.name||'?').trim().split(' ').map(function(w){return w.charAt(0);}).slice(0,2).join('');
    document.getElementById('attDetailsAvatar').textContent = initials;
    document.getElementById('attDetailsId').textContent = '#' + (d.nid || d.id || '—');
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
    document.getElementById('attDetailsRegisteredBy').textContent = d.registeredBy || '—';
    document.getElementById('attDetailsCheckedIn').textContent = d.checkedIn || '—';
    document.getElementById('attDetailsNotes').textContent = d.notes || '—';

    // Load transactions with receipt preview each
    loadAttendeeTransactions(d.id);

    var canTicket = d.canTicket === '1' || d.canTicket === 1;
    var ticketUrl = "{{ url('/attendees') }}/" + d.id + "/ticket";
    var ticketBtn = document.getElementById('attActTicket');
    ticketBtn.style.display = canTicket ? 'flex' : 'none';
    ticketBtn.onclick = canTicket ? function(){ openTicketPreview(ticketUrl, (d.name || 'Attendee') + ' – ticket'); } : null;
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

  document.querySelectorAll('[data-update-att-status]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('attStatusName').textContent = d.name || 'Attendee';
      document.getElementById('attStatusSelect').value = d.status || 'pending';
      document.getElementById('attStatusNotes').value = d.notes || '';
      document.getElementById('attStatusForm').action = "{{ url('/attendees') }}/" + d.id + "/status";
      openDrawerById('attStatusDrawer');
    });
  });

  document.querySelectorAll('[data-send-att-sms]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('attSmsName').textContent = d.name || 'Attendee';
      document.getElementById('attSmsPhone').value = d.phone || '';
      document.getElementById('attSmsMessage').value = 'Hello ' + firstName(d.name) + ',\\nYou are registered for {{ \App\Models\Setting::get("event.name", "Open Gate Camp") }}. We look forward to seeing you!';
      document.getElementById('attSmsForm').action = "{{ url('/attendees') }}/" + d.id + "/sms";
      openDrawerById('attSmsDrawer');
    });
  });

  document.querySelectorAll('[data-open-att-ticket]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      openTicketPreview("{{ url('/attendees') }}/" + d.id + "/ticket", (d.name || 'Attendee') + ' – ticket');
    });
  });

  document.querySelectorAll('[data-send-att-ticket]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      var msg = 'Send ticket (' + (d.ticket||'') + ') to ' + (d.name||'this attendee') + ' by SMS?';
      var f = document.createElement('form');
      f.method = 'POST';
      f.action = "{{ url('/attendees') }}/" + d.id + "/ticket/sms";
      var mt = document.querySelector('meta[name="csrf-token"]');
      var t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value = mt ? mt.content : '{{ csrf_token() }}';
      f.appendChild(t);
      document.body.appendChild(f);
      confirmAction(f, 'Send ticket by SMS', msg, 'Send SMS');
    });
  });

  var regIsPaid = document.getElementById('regIsPaid');
  var regPayFields = document.getElementById('regPaymentFields');
  if (regIsPaid && regPayFields) {
    function toggleRegPay(){
      var paid = regIsPaid.value === '1';
      regPayFields.style.display = paid ? '' : 'none';
      if (!paid) {
        var amt = document.getElementById('regAmount');
        if (amt) amt.value = '';
      }
    }
    regIsPaid.addEventListener('change', toggleRegPay);
    toggleRegPay();
  }

  // Auto-fill fellowship & pickup for leaders (University Fellowship — Select — / Coming From — Select —)
  var isLeader = @json(!empty($isFellowshipLeader) && $isFellowshipLeader);
  var leaderFellowships = @json($fellowships ?? []);
  var pickupMap = @json($pickupMap ?? []);
  var regFell = document.getElementById('regFellowship');
  var regFellHidden = document.getElementById('regFellowshipHidden');
  var regPickup = document.getElementById('regPickup');
  function filterFellowshipsByPickup(){
    if(!regFell || !regPickup) return;
    var pickup = regPickup.value;
    Array.from(regFell.options).forEach(function(opt){
      if(opt.value === '') return;
      var shouldShow = !pickup || (pickupMap[opt.value] === pickup);
      // If fellowship not in map, hide unless no pickup selected
      opt.hidden = !shouldShow;
      opt.disabled = !shouldShow;
      if(!shouldShow && opt.selected){
        opt.selected = false;
        if(regFellHidden) regFellHidden.value = '';
      }
    });
    if(regFellHidden && regFell.value) regFellHidden.value = regFell.value;
  }
  function applyInitialLeaderState(){
    if(isLeader && leaderFellowships.length === 1){
      var single = leaderFellowships[0];
      var singlePickup = pickupMap[single];
      if(singlePickup && regPickup) regPickup.value = singlePickup;
      if(regFell){
        regFell.value = single;
        if(regFellHidden) regFellHidden.value = single;
      }
    }
    filterFellowshipsByPickup();
  }
  if(regFell && regPickup){
    applyInitialLeaderState();
    regPickup.addEventListener('change', function(){
      filterFellowshipsByPickup();
    });
    regFell.addEventListener('change', function(){
      if(regFellHidden) regFellHidden.value = regFell.value;
    });
    document.querySelectorAll('[data-drawer-open="attRegisterDrawer"]').forEach(function(btn){
      btn.addEventListener('click', function(){ setTimeout(function(){ applyInitialLeaderState(); filterFellowshipsByPickup(); }, 80); });
    });
  }
});
</script>
@endpush