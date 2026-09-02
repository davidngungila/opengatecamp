@extends('layouts.app')

@section('title', 'Pledges — Open Gate Camp Mission')
@section('crumb', 'Giving / Pledges')
@section('page_title', 'Pledges')

@section('content')
@php
    $v = fn($f) => old($f, $filters[$f] ?? null);
@endphp
<div class="fade-in">
  <div class="section-head">
    <div><h2>Pledges</h2><div class="sub">
      TZS {{ number_format($totals['pledged']) }} pledged · {{ number_format($totals['paid']) }} collected · TZS {{ number_format($totals['outstanding']) }} outstanding
    </div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="pledgeNewDrawer">+ Record Pledge</button>
  </div>

  <form class="toolbar" method="GET" action="{{ route('pledges.index') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ $v('q') }}" placeholder="Search by name, pledge no, phone..."></div>
    <select class="filter-select" name="event" onchange="this.form.submit()">
      <option value="">All Events</option>
      @foreach($events as $e)<option value="{{ $e->slug }}" {{ $v('event')==$e->slug ? 'selected' : '' }}>{{ $e->title }} · {{ $e->start_date?->format('d M Y') }} @if($e->end_date && $e->end_date->ne($e->start_date))–{{ $e->end_date->format('d M Y') }}@endif</option>@endforeach
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
        <thead><tr><th>Pledge No</th><th>Pledger</th><th>Amount (TZS)</th><th>Paid (TZS)</th><th>Balance</th><th>Status</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($pledges as $pl)
          @php
            $paymentsJson = $pl->payments->map(fn($p) => [
                'date'   => $p->pay_date?->format('d M Y'),
                'amount' => $p->amount,
                'method' => $p->method,
                'ref'    => $p->reference,
                'by'     => $p->recorded_by,
            ])->toJson();
          @endphp
          <tr style="cursor:pointer" data-view-pledge
            data-id="{{ $pl->id }}"
            data-no="{{ $pl->pledge_no }}"
            data-name="{{ $pl->name }}"
            data-phone="{{ $pl->phone }}"
            data-email="{{ $pl->email }}"
            data-event="{{ $pl->event?->title ?? '—' }}"
            data-amount="{{ $pl->amount }}"
            data-paid="{{ $pl->paid_amount }}"
            data-remaining="{{ $pl->getRemainingAttribute() }}"
            data-frequency="{{ ucfirst(str_replace('_',' ',$pl->frequency)) }}"
            data-pledge-date="{{ $pl->pledge_date?->format('d M Y') }}"
            data-due-date="{{ $pl->due_date?->format('d M Y') ?? '—' }}"
            data-status="{{ $pl->getStatusLabel() }}"
            data-status-key="{{ $pl->status }}"
            data-notes="{{ $pl->notes }}"
            data-created="{{ $pl->created_by ?? '—' }}"
            data-payments="{{ e($paymentsJson) }}">
            <td><span class="badge badge-neutral badge-dotted">{{ $pl->pledge_no }}</span></td>
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $pl->name ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div><div class="cu-name">{{ $pl->name ?? '—' }}</div><div class="cu-sub">{{ $pl->email ?? ($pl->phone ?? '') }}</div></div>
              </div>
            </td>
            <td><b>{{ number_format($pl->amount) }}</b></td>
            <td>{{ number_format($pl->paid_amount) }}</td>
            <td><b style="color:{{ $pl->getRemainingAttribute() > 0 ? 'var(--warning)' : 'var(--success)' }}">{{ number_format($pl->getRemainingAttribute()) }}</b></td>
            <td><span class="badge badge-{{ $pl->getStatusColor() }} badge-dotted">{{ $pl->getStatusLabel() }}</span></td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-pledge-{{ $pl->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-pledge-{{ $pl->id }}">
                  <button type="button" data-record-payment data-id="{{ $pl->id }}" data-name="{{ $pl->name }}" data-amount="{{ $pl->amount }}" data-remaining="{{ $pl->getRemainingAttribute() }}">Record Payment</button>
                  <form method="POST" action="{{ route('pledges.remind', $pl) }}" style="display:contents">@csrf<button type="submit">Remind (SMS)</button></form>
                  <form method="POST" action="{{ route('pledges.thanks', $pl) }}" style="display:contents">@csrf<button type="submit">Send Thanks (SMS)</button></form>
                  <button type="button" data-edit-pledge data-id="{{ $pl->id }}" data-amount="{{ $pl->amount }}" data-status="{{ $pl->status }}" data-notes="{{ $pl->notes }}">Edit</button>
                  <form method="POST" action="{{ route('pledges.destroy', $pl) }}" data-confirm
                        data-confirm-title="Delete this pledge?"
                        data-confirm-message="{{ $pl->pledge_no }} — {{ $pl->name }} will be permanently removed."
                        data-confirm-label="Delete Pledge">@csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state" style="padding:40px 20px"><h3>No pledges recorded</h3><p>Record your first event pledge.</p><button type="button" class="btn btn-accent" data-drawer-open="pledgeNewDrawer">+ Record Pledge</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $pledges->firstItem() ?? 0 }}–{{ $pledges->lastItem() ?? 0 }} of {{ $pledges->total() }} pledges</span>
      <div class="pagination">{{ $pledges->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="pledgeNewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Record Pledge</h3><p>Captured automatically against {{ $campEvent?->title ?? 'Open Gate Camp' }}</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('pledges.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Event / Campaign</label>
            <input type="hidden" name="event_id" value="{{ $campEvent?->id }}">
            <div style="background:var(--blue-light);color:var(--blue-accent);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:700">{{ $campEvent?->title ?? 'Open Gate Camp' }} · {{ $campEvent?->start_date?->format('d M Y') }}</div>
          </div>
          <div class="field"><label>Name</label><input name="name" placeholder="Full name" value="{{ old('name') }}" required></div>
          <div class="field"><label>Phone</label><input name="phone" placeholder="+255 7XX XXX XXX" value="{{ old('phone') }}"></div>
          <div class="field full"><label>Email</label><input name="email" placeholder="email@example.com" value="{{ old('email') }}"></div>
          <div class="field"><label>Amount (TZS)</label><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required></div>
          <div class="field"><label>Frequency</label><select name="frequency">
            @foreach($frequencies as $k=>$f)<option value="{{ $k }}" @if(old('frequency')==$k) selected @endif>{{ $f }}</option>@endforeach
          </select></div>
          <div class="field"><label>Pledge Date</label><input type="date" name="pledge_date" value="{{ old('pledge_date', now()->format('Y-m-d')) }}"></div>
          <div class="field"><label>Due Date</label><input type="date" name="due_date" value="{{ old('due_date') }}"></div>
          <div class="field full"><label>Notes</label><textarea name="notes" placeholder="Any notes about this pledge"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Pledge</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="pledgePaymentDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Record Pledge Payment</h3><p id="paymentPledgeName">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="paymentForm">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><div class="info-row"><span>Amount Pledged</span><b id="paymentPledged">—</b></div><div class="info-row"><span>Balance Outstanding</span><b id="paymentRemaining" style="color:var(--warning)">—</b></div></div>
          <div class="field"><label>Amount (TZS)</label><input type="number" step="0.01" name="amount" id="paymentAmount" required min="1"></div>
          <div class="field"><label>Payment Method</label><select name="method"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile">Mobile</option></select></div>
          <div class="field"><label>Reference</label><input name="reference" placeholder="Txn / receipt reference"></div>
          <div class="field"><label>Payment Date</label><input type="date" name="pay_date" value="{{ now()->format('Y-m-d') }}"></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Record Payment</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="pledgeEditDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Edit Pledge</h3><p>Update amount or status</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="editPledgeForm">
      @csrf @method('PUT')
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Amount (TZS)</label><input type="number" step="0.01" name="amount" id="editPledgeAmount" required></div>
          <div class="field"><label>Status</label><select name="status" id="editPledgeStatus">
            @foreach($statuses as $k=>$s)<option value="{{ $k }}">{{ $s }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Notes</label><textarea name="notes" id="editPledgeNotes"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="pledgeDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div>
        <h3>Pledge Details</h3>
        <p id="drawerNo" class="badge badge-neutral badge-dotted">—</p>
      </div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="cell-avatar avatar-lg" id="drawerAvatar">—</div>
        <div>
          <div class="cu-name" id="drawerName" style="font-size:17px">—</div>
          <span class="badge badge-dotted" id="drawerStatus">—</span>
        </div>
      </div>

      <div class="drawer-progress">
        <div class="dp-row"><span>Pledged</span><b id="drawerAmount">—</b></div>
        <div class="dp-row"><span>Paid</span><b id="drawerPaid" style="color:var(--success)">—</b></div>
        <div class="dp-row"><span>Balance</span><b id="drawerRemaining" style="color:var(--warning)">—</b></div>
        <div class="dp-track"><div class="dp-fill" id="drawerFill" style="width:0%"></div></div>
      </div>

      <div class="info-grid">
        <div class="info-row"><span>Event</span><b id="drawerEvent">—</b></div>
        <div class="info-row"><span>Phone</span><b id="drawerPhone">—</b></div>
        <div class="info-row"><span>Email</span><b id="drawerEmail">—</b></div>
        <div class="info-row"><span>Frequency</span><b id="drawerFrequency">—</b></div>
        <div class="info-row"><span>Pledge Date</span><b id="drawerPledgeDate">—</b></div>
        <div class="info-row"><span>Due Date</span><b id="drawerDueDate">—</b></div>
        <div class="info-row"><span>Recorded By</span><b id="drawerCreated">—</b></div>
        <div class="info-row full"><span>Notes</span><b id="drawerNotes" style="white-space:normal">—</b></div>
      </div>

      <div class="payments-head">
        <span>Payment History</span>
        <span class="payments-count" id="drawerPayCount">0</span>
      </div>
      <div id="drawerPayments" class="payments-list"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-accent" id="drawerPaymentBtn">Record Payment</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-pledge]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('.action-menu-wrap') || e.target.closest('form') || e.target.closest('button') || e.target.closest('a')) return;
      var d = tr.dataset;
      var initials = (d.name||'?').trim().split(' ').map(function(w){return w.charAt(0);}).slice(0,2).join('');
      document.getElementById('drawerAvatar').textContent = initials;
      document.getElementById('drawerNo').textContent = d.no;
      document.getElementById('drawerName').textContent = d.name || '—';
      var st = document.getElementById('drawerStatus');
      var colors = {pending:'warning',partial:'info',fulfilled:'success',cancelled:'neutral'};
      var key = d.statusKey || '';
      st.textContent = d.status || '—';
      st.className = 'badge badge-' + (colors[key]||'neutral') + ' badge-dotted';

      var amount = Number(d.amount||0), paid = Number(d.paid||0), remaining = Number(d.remaining||0);
      var fmt = function(n){ return 'TZS ' + n.toLocaleString(); };
      document.getElementById('drawerAmount').textContent = fmt(amount);
      document.getElementById('drawerPaid').textContent = fmt(paid);
      document.getElementById('drawerRemaining').textContent = fmt(remaining);
      document.getElementById('drawerRemaining').style.color = remaining > 0 ? 'var(--warning)' : 'var(--success)';
      document.getElementById('drawerFill').style.width = (amount > 0 ? Math.min(100, (paid/amount)*100) : 0) + '%';

      document.getElementById('drawerEvent').textContent = d.event || '—';
      document.getElementById('drawerPhone').textContent = d.phone || '—';
      document.getElementById('drawerEmail').textContent = d.email || '—';
      document.getElementById('drawerFrequency').textContent = d.frequency || '—';
      document.getElementById('drawerPledgeDate').textContent = d.pledgeDate || '—';
      document.getElementById('drawerDueDate').textContent = d.dueDate || '—';
      document.getElementById('drawerCreated').textContent = d.created || '—';
      document.getElementById('drawerNotes').textContent = d.notes || '—';

      var payments = [];
      try { payments = JSON.parse(d.payments || '[]'); } catch(e){ payments = []; }
      var list = document.getElementById('drawerPayments');
      list.innerHTML = '';
      var totalCount = payments.length;
      if(payments.length === 0 && paid > 0){
        totalCount = 1;
        list.innerHTML = '<div class="pay-item"><div class="pay-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div><div class="pay-main"><div class="pm-name">Direct record</div><div class="pm-sub">Amount recorded against this pledge</div></div><div class="pay-amt">' + fmt(paid) + '</div></div>';
      } else if(payments.length === 0){
        list.innerHTML = '<div class="pay-empty">No payments recorded yet</div>';
      } else {
        payments.forEach(function(p, i){
          var item = document.createElement('div');
          item.className = 'pay-item';
          item.innerHTML =
            '<div class="pay-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
            '<div class="pay-main"><div class="pm-name">Payment ' + (i+1) + (p.ref ? ' · ' + p.ref : '') + '</div>' +
            '<div class="pm-sub">' + (p.date||'—') + (p.method ? ' · ' + p.method.charAt(0).toUpperCase() + p.method.slice(1) : '') + (p.by ? ' · Recorded by ' + p.by : '') + '</div></div>' +
            '<div class="pay-amt">' + fmt(Number(p.amount||0)) + '</div>';
          list.appendChild(item);
        });
      }
      document.getElementById('drawerPayCount').textContent = totalCount;

      document.getElementById('drawerPaymentBtn').dataset.id = d.id;
      document.getElementById('drawerPaymentBtn').dataset.name = d.name;
      document.getElementById('drawerPaymentBtn').dataset.amount = d.amount;
      document.getElementById('drawerPaymentBtn').dataset.remaining = d.remaining;

      openDrawerById('pledgeDetailDrawer');
    });
  });

  document.querySelectorAll('[data-record-payment]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('paymentPledgeName').textContent = d.name;
      document.getElementById('paymentPledged').textContent = 'TZS ' + Number(d.amount).toLocaleString();
      document.getElementById('paymentRemaining').textContent = 'TZS ' + Number(d.remaining).toLocaleString();
      document.getElementById('paymentAmount').value = d.remaining;
      document.getElementById('paymentAmount').max = d.remaining;
      document.getElementById('paymentForm').action = "{{ url('/pledges') }}/" + d.id + "/payments";
      openDrawerById('pledgePaymentDrawer');
    });
  });
  document.querySelectorAll('[data-edit-pledge]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('editPledgeAmount').value = d.amount;
      document.getElementById('editPledgeStatus').value = d.status;
      document.getElementById('editPledgeNotes').value = d.notes || '';
      document.getElementById('editPledgeForm').action = "{{ url('/pledges') }}/" + d.id;
      openDrawerById('pledgeEditDrawer');
    });
  });

  document.getElementById('drawerPaymentBtn').addEventListener('click', function(){
    var d = this.dataset;
    var remaining = Number(d.remaining||0);
    if(remaining <= 0){ toast('This pledge is fully paid','warning'); return; }
    document.getElementById('paymentPledgeName').textContent = d.name;
    document.getElementById('paymentPledged').textContent = 'TZS ' + Number(d.amount||0).toLocaleString();
    document.getElementById('paymentRemaining').textContent = 'TZS ' + remaining.toLocaleString();
    document.getElementById('paymentAmount').value = remaining;
    document.getElementById('paymentAmount').max = remaining;
    document.getElementById('paymentForm').action = "{{ url('/pledges') }}/" + d.id + "/payments";
    closeDrawerById('pledgeDetailDrawer');
    openDrawerById('pledgePaymentDrawer');
  });
});
</script>
@endpush