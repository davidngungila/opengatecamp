@extends('layouts.app')

@section('title', 'Chart of Accounts — Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Chart of Accounts')
@section('page_title', 'Chart of Accounts')

@php
    $typeFilter = request('type');
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Chart of Accounts</h2><div class="sub">{{ $accounts->count() }} accounts</div></div>
    <button type="button" class="btn btn-accent" data-modal-open="accountModal" onclick="resetAccountModal()">+ Add Account</button>
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
          <tr>
            <td><b>{{ $a->code }}</b></td>
            <td>{{ $a->name }}</td>
            <td><span class="badge badge-{{ ['asset'=>'info','liability'=>'warning','equity'=>'purple','income'=>'success','expense'=>'danger'][$a->type] }} badge-dotted">{{ ucfirst($a->type) }}</span></td>
            <td>TZS {{ number_format((float) ($a->total_debit ?? 0)) }}</td>
            <td>TZS {{ number_format((float) ($a->total_credit ?? 0)) }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-acct-{{ $a->id }}')">
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

<div class="modal-overlay" id="accountModal">
  <div class="modal-box sm">
    <div class="modal-head">
      <div><h3 id="acctModalTitle">Add Account</h3></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form id="accountForm" method="POST" action="{{ route('accounting.accounts.store') }}">
      @csrf
      <div class="modal-body">
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
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
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
  form.action='{{ url('/accounting/accounts') }}';
  var m=form.querySelector('#_amethod'); if(m) m.remove();
  document.getElementById('acctModalTitle').textContent='Add Account';
}
document.addEventListener('click', function(e){
  if(!e.target.closest('[data-edit-account]')) return;
  var b=e.target.closest('[data-edit-account]');
  var form=document.getElementById('accountForm');
  form.action='{{ url('/accounting/accounts') }}/'+b.dataset.id;
  form.querySelector('[name=code]').value=b.dataset.code||'';
  form.querySelector('[name=name]').value=b.dataset.name||'';
  form.querySelector('[name=type]').value=b.dataset.type||'';
  var m=document.createElement('input');
  m.type='hidden'; m.name='_method'; m.value='PUT'; m.id='_amethod';
  form.appendChild(m);
  document.getElementById('acctModalTitle').textContent='Edit Account';
  openModalById('accountModal');
});
</script>
@endpush
