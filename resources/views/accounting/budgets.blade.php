@extends('layouts.app')

@section('title', 'Budgets â€” Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / Budgets')
@section('page_title', 'Budget Management')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Budget Management</h2><div class="sub">@if($fy) Period: {{ $fy->name }} — Income TZS {{ number_format($incomeTotal) }}. @else Select a financial year. @endif</div></div>
    <button type="button" class="btn btn-accent" data-modal-open="budgetModal">+ Add Budget</button>
  </div>

  <form class="toolbar" method="GET" action="{{ route('accounting.budgets') }}">
    <select class="filter-select" name="event_id" onchange="this.form.submit()">
      <option value="">All Events</option>
      @foreach($allEvents as $e)<option value="{{ $e->id }}" {{ request('event_id')==$e->id ? 'selected' : '' }}>{{ $e->title }}</option>@endforeach
    </select>
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Account</th><th>Event</th><th style="text-align:right">Budget (TZS)</th><th style="text-align:right">Actual (TZS)</th><th style="text-align:right">Variance</th><th style="width:140px">Progress</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($budgets as $b)
          @php
            $actual = $b->actual ?? 0;
            $variance = $b->amount - $actual;
            $pct = $b->amount > 0 ? min(100, round(($actual / $b->amount) * 100)) : 0;
          @endphp
          <tr>
            <td><b>{{ $b->account->code }}</b> — {{ $b->account->name }}</td>
            <td>@if($b->event)<a href="{{ route('events.show', $b->event) }}" class="link-btn">{{ $b->event->title }}</a>@else<span class="badge badge-neutral badge-dotted">General</span>@endif</td>
            <td style="text-align:right">TZS {{ number_format($b->amount) }}</td>
            <td style="text-align:right">TZS {{ number_format($actual) }}</td>
            <td style="text-align:right;color:{{ $variance >= 0 ? 'var(--green-accent)' : 'var(--red)' }}">
              {{ $variance >= 0 ? '+' : '' }}TZS {{ number_format($variance) }}
            </td>
            <td>
              <div style="background:#e5e7eb;border-radius:20px;height:10px;overflow:hidden">
                <div style="background:{{ $pct > 90 ? 'var(--red)' : ($pct > 70 ? '#f59e0b' : 'var(--green-accent)') }};height:100%;width:{{ $pct }}%;border-radius:20px"></div>
              </div>
              <div style="font-size:11px;color:var(--text-muted);margin-top:3px">{{ $pct }}% spent</div>
            </td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-bud-{{ $b->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-bud-{{ $b->id }}">
                  <a href="{{ route('accounting.ledger', ['account' => $b->account_id]) }}">View Ledger</a>
                  <form method="POST" action="{{ route('accounting.budgets.destroy', $b) }}"
                        data-confirm data-confirm-title="Delete this budget line?"
                        data-confirm-label="Delete Budget">
                    @csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state"><h3>No budget lines yet</h3><p>Add expense budgets to track spending.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal-overlay" id="budgetModal">
  <div class="modal-box md">
    <div class="modal-head">
      <div><h3>Add Budget Line</h3><p>Set spending limit for an expense account</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('accounting.budgets.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full"><label>Financial Year *</label>
            <select name="fy_id" required>
              @foreach($allYears as $y)<option value="{{ $y->id }}" {{ $fy && $fy->id===$y->id ? 'selected' : '' }}>{{ $y->name }}</option>@endforeach
            </select>
          </div>
          <div class="field full"><label>Expense Account *</label>
            <select name="account_id" required>
              @foreach($expenseAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach
            </select>
          </div>
          <div class="field full"><label>Event (optional)</label>
            <select name="event_id">
              <option value="">— General budget (organisation-wide) —</option>
              @foreach($allEvents as $e)<option value="{{ $e->id }}">{{ $e->title }}</option>@endforeach
            </select>
          </div>
          <div class="field full"><label>Budget Amount (TZS) *</label><input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent"
                data-confirm data-confirm-title="Save this budget?"
                data-confirm-label="Save Budget">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
