@extends('layouts.app')

@section('title', 'Audit Logs — Open Gate Camp Mission')
@section('crumb', 'Account / Audit Logs')
@section('page_title', 'Audit Logs')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Audit Logs</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Track who did what, when, and from where.</p></div>

  <div class="filter-bar" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
    <form method="GET" action="{{ route('account.audit-logs') }}" style="display:flex;gap:10px;flex-wrap:wrap;flex:1">
      <div class="field" style="margin:0;flex:1;min-width:200px">
        <input name="q" placeholder="Search user, action or details..." value="{{ $filters['q'] }}">
      </div>
      <div class="field" style="margin:0;min-width:160px">
        <select name="module">
          <option value="all">All modules</option>
          @foreach($modules as $m)
            <option value="{{ $m }}" {{ ($filters['module'] ?? 'all') === $m ? 'selected' : '' }}>{{ $m }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      @if($filters['q'] !== '' || ($filters['module'] ?? 'all') !== 'all')
      <a class="btn btn-ghost btn-sm" href="{{ route('account.audit-logs') }}">Reset</a>
      @endif
    </form>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th><th>When</th></tr></thead>
        <tbody>
          @forelse($logs as $log)
          <tr>
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $log->user_name ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div><div class="cu-name">{{ $log->user_name }}</div></div>
              </div>
            </td>
            <td>{{ $log->action }}</td>
            <td>{{ $log->module ?? '—' }}</td>
            <td>{{ Str::limit($log->details ?? '—', 50) }}</td>
            <td>{{ $log->ip }}</td>
            <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state"><h3>No activity found</h3><p>Try adjusting your filter or search.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries</span>
      <div class="flex gap-8 settings-actions-cell" style="align-items:center">{{ $logs->links() }}</div>
    </div>
  </div>
</div>
@endsection