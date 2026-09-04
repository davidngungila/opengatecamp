@extends('layouts.app')

@section('title', 'Bank Reconciliation — OpenGate Camp Connect')
@section('crumb', 'Finance / Financial Accounting / Bank Reconciliation')
@section('page_title', 'Bank Reconciliation')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Bank Reconciliation</h2><div class="sub">Compare ledger balance with bank statement to ensure accuracy.</div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="recModal">+ New Reconciliation</button>
  </div>

  <div class="glass-card" style="margin-bottom:20px;padding:16px 20px">
    <form method="GET" action="{{ route('accounting.reconciliation') }}" class="form-grid" style="grid-template-columns:1fr 1fr auto;align-items:end;gap:12px">
      <div class="field"><label>Bank Account</label>
        <select name="account">
          <option value="">— Select account —</option>
          @foreach($bankAccounts as $a)
            <option value="{{ $a->id }}" {{ $selected && $selected->id===$a->id ? 'selected' : '' }}>{{ $a->code }} — {{ $a->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field"><label>As of Date</label><input type="date" name="as_of" value="{{ $asOf }}"></div>
      <button type="submit" class="btn btn-secondary" style="height:38px">View</button>
    </form>
  </div>

  @if($selected)
  <div class="stat-grid" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:24px">
    <div class="glass-card" style="text-align:center;padding:20px">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px">Ledger Balance</div>
      <div style="font-size:24px;font-weight:700;color:var(--blue-accent)">TZS {{ number_format($ledgerBalance) }}</div>
    </div>
    <div class="glass-card" style="text-align:center;padding:20px">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px">Account</div>
      <div style="font-size:16px;font-weight:600">{{ $selected->code }} — {{ $selected->name }}</div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:4px">As of {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</div>
    </div>
    <div class="glass-card" style="text-align:center;padding:20px;border:2px solid var(--green-accent)">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:6px">Status</div>
      <div style="font-size:14px;font-weight:600;color:var(--green-accent)">Ready to reconcile</div>
    </div>
  </div>
  @endif

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Account</th><th>Statement Date</th><th style="text-align:right">Statement Balance</th><th style="text-align:right">Ledger Balance</th><th style="text-align:right">Difference</th><th>Notes</th><th>Recorded By</th></tr></thead>
        <tbody>
          @forelse($reconciliations as $r)
          <tr>
            <td><b>{{ $r->account->code }}</b> — {{ $r->account->name }}</td>
            <td>{{ $r->statement_date->format('d M Y') }}</td>
            <td style="text-align:right">TZS {{ number_format((float) $r->statement_balance) }}</td>
            <td style="text-align:right">TZS {{ number_format((float) $r->ledger_balance) }}</td>
            <td style="text-align:right;color:{{ abs((float) $r->difference) < 0.01 ? 'var(--green-accent)' : 'var(--red)' }}">
              TZS {{ number_format(abs((float) $r->difference)) }}
              {{ abs((float) $r->difference) < 0.01 ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' }}
            </td>
            <td>{{ $r->notes ?? '—' }}</td>
            <td>{{ $r->created_by }}</td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state"><h3>No reconciliations yet</h3><p>Run your first bank reconciliation.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="recModal">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>New Bank Reconciliation</h3><p>Compare statement vs ledger</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('accounting.reconciliation.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Bank Account *</label>
            <select name="account_id" required>
              @foreach($bankAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label>Statement Date *</label><input type="date" name="statement_date" value="{{ $asOf }}" required></div>
          <div class="field"><label>Statement Balance (TZS) *</label><input type="number" step="0.01" name="statement_balance" required placeholder="0.00"></div>
          <div class="field full"><label>Notes</label><textarea name="notes" rows="2" placeholder="Optional notes about this reconciliation"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent"
                data-confirm data-confirm-title="Save reconciliation?"
                data-confirm-message="The system will compare your statement balance against the ledger."
                data-confirm-label="Save">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
