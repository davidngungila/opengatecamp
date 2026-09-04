@extends('layouts.app')

@section('title', 'Chart of Accounts — OpenGate Camp Connect')
@section('crumb', 'Finance / Financial Accounting / Chart of Accounts')
@section('page_title', 'Chart of Accounts')

@php
    $typeFilter = request('type');
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Chart of Accounts</h2><div class="sub">{{ $accounts->count() }} accounts</div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="accountModal" onclick="resetAccountModal()">+ Add Account</button>
  </div>

  <form class="toolbar" method="GET" action="{{ url('/accounting/accounts') }}">
    <select class="filter-select" name="type" onchange="this.form.submit()">
      <option value="">All Types</option>
      @foreach($types as $key => $label)
        <option value="{{ $key }}" {{ $typeFilter===$key ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
    </select>
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th style="width:90px">Code</th><th>Account Name</th><th>Type</th><th>Total Debits</th><th>Total Credits</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @foreach($accounts as $i => $a)
          <tr style="cursor:pointer" data-view-account data-id="{{ $a->id }}">
            <td><b>{{ $a->code }}</b></td>
            <td>{{ $a->name }}</td>
            <td><span class="badge badge-{{ ['asset'=>'info','liability'=>'warning','equity'=>'purple','income'=>'success','expense'=>'danger'][$a->type] }} badge-dotted">{{ ucfirst($a->type) }}</span></td>
            <td>TZS {{ number_format((float) ($a->total_debit ?? 0)) }}</td>
            <td>TZS {{ number_format((float) ($a->total_credit ?? 0)) }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="event.stopPropagation();toggleActionMenu('am-acct-{{ $a->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-acct-{{ $a->id }}">
                  <a href="{{ route('accounting.ledger', ['account' => $a->id]) }}">View Ledger</a>
                  <button type="button" data-edit-account
                          data-id="{{ $a->id }}" data-code="{{ $a->code }}" data-name="{{ $a->name }}" data-type="{{ $a->type }}">Edit</button>
                  @if(!$isCommittee)
                  <form method="POST" action="{{ route('accounting.accounts.destroy', $a) }}"
                        data-confirm data-confirm-title="Delete this account?"
                        data-confirm-message="{{ $a->code }} — {{ $a->name }} will be removed."
                        data-confirm-label="Delete Account">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="acctDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="acctDrawerTitle">Account Details</h3><p id="acctDrawerType" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="info-grid">
        <div class="info-row"><span>Code</span><b id="acctDrawerCode">—</b></div>
        <div class="info-row"><span>Name</span><b id="acctDrawerName">—</b></div>
        <div class="info-row"><span>Type</span><b id="acctDrawerTypeVal">—</b></div>
        <div class="info-row"><span>Total Debits</span><b id="acctDrawerDr">—</b></div>
        <div class="info-row"><span>Total Credits</span><b id="acctDrawerCr">—</b></div>
        <div class="info-row"><span>Net Balance</span><b id="acctDrawerNet">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Recent Activity</span><span class="payments-count" id="acctLinesCount">0</span>
      </div>
      <div id="acctLines" class="payments-list"></div>
      <div style="margin-top:14px">
        <a id="acctLedgerLink" href="#" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">View Full Ledger</a>
      </div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="accountModal">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="acctModalTitle">Add Account</h3></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form id="accountForm" method="POST" action="{{ route('accounting.accounts.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Code *</label><input name="code" required placeholder="e.g. 4050"></div>
          <div class="field"><label>Type *</label>
            <select name="type">
              @foreach($types as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="field full"><label>Name *</label><input name="name" required placeholder="e.g. Bookshop Income"></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Account</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function resetAccountModal(){
  var form=document.getElementById('accountForm');
  form.reset();
  form.action='{{ url("/accounting/accounts") }}';
  var m=form.querySelector('#_amethod'); if(m) m.remove();
  document.getElementById('acctModalTitle').textContent='Add Account';
}
document.addEventListener('click', function(e){
  if(!e.target.closest('[data-edit-account]')) return;
  var b=e.target.closest('[data-edit-account]');
  var form=document.getElementById('accountForm');
  form.action='{{ url("/accounting/accounts") }}/'+b.dataset.id;
  form.querySelector('[name=code]').value=b.dataset.code||'';
  form.querySelector('[name=name]').value=b.dataset.name||'';
  form.querySelector('[name=type]').value=b.dataset.type||'';
  var m=document.createElement('input');
  m.type='hidden'; m.name='_method'; m.value='PUT'; m.id='_amethod';
  form.appendChild(m);
  document.getElementById('acctModalTitle').textContent='Edit Account';
  openDrawerById('accountModal');
});

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-account]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      var id = tr.dataset.id;
      fetch('{{ url("/accounting/api/accounts") }}/' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
          var a = d.account;
          var typeColors = {asset:'info',liability:'warning',equity:'purple',income:'success',expense:'danger'};
          document.getElementById('acctDrawerTitle').textContent = a.code + ' — ' + a.name;
          document.getElementById('acctDrawerType').textContent = a.type.charAt(0).toUpperCase() + a.type.slice(1);
          document.getElementById('acctDrawerType').className = 'badge badge-' + (typeColors[a.type]||'neutral') + ' badge-dotted';
          document.getElementById('acctDrawerCode').textContent = a.code;
          document.getElementById('acctDrawerName').textContent = a.name;
          document.getElementById('acctDrawerTypeVal').textContent = a.type.charAt(0).toUpperCase() + a.type.slice(1);
          document.getElementById('acctDrawerDr').textContent = 'TZS ' + d.debit.toLocaleString();
          document.getElementById('acctDrawerCr').textContent = 'TZS ' + d.credit.toLocaleString();
          document.getElementById('acctDrawerNet').textContent = 'TZS ' + Math.abs(d.net).toLocaleString();
          document.getElementById('acctDrawerNet').style.color = d.net >= 0 ? 'var(--success)' : 'var(--danger)';

          document.getElementById('acctLinesCount').textContent = d.recentLines.length;
          var list = document.getElementById('acctLines');
          list.innerHTML = '';
          if(d.recentLines.length === 0){
            list.innerHTML = '<div class="pay-empty">No recent activity</div>';
          } else {
            d.recentLines.forEach(function(l){
              var isDr = Number(l.debit) > 0;
              var item = document.createElement('div');
              item.className = 'pay-item';
              item.innerHTML =
                '<div class="pay-ico" style="background:' + (isDr ? 'var(--success-bg)' : 'var(--danger-bg)') + ';color:' + (isDr ? 'var(--success)' : 'var(--red)') + '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>' +
                '<div class="pay-main"><div class="pm-name">' + l.entry_no + '</div><div class="pm-sub">' + l.date + ' · ' + (l.description || '') + '</div></div>' +
                '<div class="pay-amt" style="text-align:right"><div>' + (isDr ? 'Dr TZS ' + Number(l.debit).toLocaleString() : '') + '</div><div style="color:var(--red);font-size:11px">' + (!isDr && Number(l.credit) > 0 ? 'Cr TZS ' + Number(l.credit).toLocaleString() : '') + '</div></div>';
              list.appendChild(item);
            });
          }

          document.getElementById('acctLedgerLink').href = '{{ url("/accounting/ledger") }}?account=' + a.id;
          openDrawerById('acctDetailDrawer');
        });
    });
  });
});
</script>
@endpush
