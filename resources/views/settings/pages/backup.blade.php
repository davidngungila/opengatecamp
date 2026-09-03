@extends('layouts.app')

@section('title', 'Backup — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / Backup')
@section('page_title', 'Backup')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Backup</p></div>

  <div class="solid-card">
    <h2 style="font-size:14.5px;margin:0 0 6px">Backup &amp; Restore</h2>
    <div class="settings-row">
      <div class="sr-text"><p>Download Full Backup</p><span>All members, families, users, roles, settings and audit logs as JSON</span></div>
      <a class="btn btn-accent btn-sm" href="{{ route('settings.backup') }}">Backup Now</a>
    </div>
    <div class="settings-row">
      <div class="sr-text"><p>Last Action</p><span>Backups are downloaded instantly; keep them in a safe location</span></div>
      <span class="badge badge-info badge-dotted">JSON Export</span>
    </div>
  </div>
</div>
@endsection
