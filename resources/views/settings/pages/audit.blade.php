@extends('layouts.app')

@section('title', 'Audit Logs — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / Audit Logs')
@section('page_title', 'Audit Logs')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Audit Logs</p></div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th><th>When</th></tr></thead>
        <tbody>
          @forelse($auditLogs as $log)
          <tr style="cursor:pointer" data-view-audit-settings
              data-user="{{ $log->user_name }}"
              data-action="{{ $log->action }}"
              data-module="{{ $log->module ?? '—' }}"
              data-details="{{ $log->details ?? '—' }}"
              data-ip="{{ $log->ip }}"
              data-created="{{ $log->created_at?->format('d M Y H:i:s') }}"
              data-date="{{ $log->created_at?->diffForHumans() }}">
            <td>{{ $log->user_name }}</td>
            <td>{{ $log->action }}</td>
            <td>{{ $log->module ?? '—' }}</td>
            <td>{{ Str::limit($log->details ?? '—', 40) }}</td>
            <td>{{ $log->ip }}</td>
            <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state"><h3>No activity yet</h3><p>System actions will be recorded here.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $auditLogs->firstItem() ?? 0 }}–{{ $auditLogs->lastItem() ?? 0 }} of {{ $auditLogs->total() }} entries</span>
      <div class="flex gap-8 settings-actions-cell" style="align-items:center">
        {{ $auditLogs->links() }}
        @if(!$isCommittee)
        <form method="POST" action="{{ route('settings.audit.clear') }}"
              data-confirm data-confirm-title="Clear audit log?"
              data-confirm-message="All recorded activity will be permanently removed."
              data-confirm-label="Clear Log">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">Clear Log</button>
        </form>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="auditDrawerSettings">
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
  document.querySelectorAll('[data-view-audit-settings]').forEach(function(tr){
    tr.addEventListener('click', function(e){
      if(e.target.closest('button, form, a, input, select, textarea')) return;
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
      openDrawerById('auditDrawerSettings');
    });
  });
});
</script>
@endpush
