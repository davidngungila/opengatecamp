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
          <tr style="cursor:pointer" data-view-audit
              data-user="{{ $log->user_name }}"
              data-action="{{ $log->action }}"
              data-module="{{ $log->module ?? '—' }}"
              data-details="{{ $log->details ?? '—' }}"
              data-ip="{{ $log->ip }}"
              data-created="{{ $log->created_at?->format('d M Y H:i:s') }}"
              data-date="{{ $log->created_at?->diffForHumans() }}">
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

<div class="drawer-overlay" id="auditDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Audit Log Details</h3><p id="audDrawerAction" class="badge badge-neutral badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="avatar avatar-lg" id="audDrawerAvatar">—</div>
        <div>
          <div style="font-size:16px;font-weight:800" id="audDrawerUser">—</div>
          <div style="font-size:12.5px;color:var(--text-tertiary);font-weight:600">Recorded</div>
        </div>
      </div>
      <div class="info-grid">
        <div class="info-row"><span>Action</span><b id="audDrawerActionVal">—</b></div>
        <div class="info-row"><span>Module</span><b id="audDrawerModule">—</b></div>
        <div class="info-row"><span>IP Address</span><b id="audDrawerIp">—</b></div>
        <div class="info-row"><span>Timestamp</span><b id="audDrawerCreated">—</b></div>
        <div class="info-row"><span>When</span><b id="audDrawerDate">—</b></div>
      </div>
      <div class="payments-head" style="margin-top:18px">
        <span>Details</span>
      </div>
      <div id="audDrawerDetails" style="font-size:13.5px;color:var(--text-primary);background:var(--blue-light);border:1px solid rgba(37,99,235,.18);border-radius:12px;padding:14px;line-height:1.6;white-space:pre-wrap;word-break:break-word">—</div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-view-audit]').forEach(function(tr){
    tr.addEventListener('click', function(){
      var d = tr.dataset;
      var initials = (d.user||'?').trim().split(' ').map(function(w){return w.charAt(0);}).slice(0,2).join('');
      document.getElementById('audDrawerAvatar').textContent = initials;
      document.getElementById('audDrawerUser').textContent = d.user || '—';
      document.getElementById('audDrawerAction').textContent = d.action || '—';
      document.getElementById('audDrawerActionVal').textContent = d.action || '—';
      document.getElementById('audDrawerModule').textContent = d.module || '—';
      document.getElementById('audDrawerIp').textContent = d.ip || '—';
      document.getElementById('audDrawerCreated').textContent = d.created || '—';
      document.getElementById('audDrawerDate').textContent = d.date || '—';
      document.getElementById('audDrawerDetails').textContent = d.details || '—';
      openDrawerById('auditDrawer');
    });
  });
});
</script>
@endpush