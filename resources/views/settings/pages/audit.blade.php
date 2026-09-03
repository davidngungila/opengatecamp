@extends('layouts.app')

@section('title', 'Audit Logs — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / Audit Logs')
@section('page_title', 'Audit Logs')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Audit Logs</p></div>

  <div class="settings-layout">
    @include('settings.partials.nav', ['active' => 'audit'])

    <div>
      <div class="table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th><th>When</th></tr></thead>
            <tbody>
              @forelse($auditLogs as $log)
              <tr>
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
  </div>
</div>
@endsection
