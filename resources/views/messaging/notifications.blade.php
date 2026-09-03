@extends('layouts.app')
@section('title', 'Notifications — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / Notifications')
@section('page_title', 'Notifications')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Notifications</h2>
    <p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">System activity feed. Unread items are highlighted.</p>
  </div>

  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}
  </div>
  @endif

  <div class="glass-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px">
      <h2 style="font-size:14.5px;margin:0">Recent System Notifications</h2>
      <div class="flex gap-8" style="align-items:center;gap:10px">
        @php $unread = $notifications->where('is_read', false)->count(); @endphp
        @if($unread > 0)
        <span class="badge badge-warning">{{ $unread }} unread</span>
        <form method="POST" action="{{ route('messaging.notifications.mark-all-read') }}">
          @csrf
          <button type="submit" class="btn btn-secondary btn-sm">Mark all read</button>
        </form>
        @endif
      </div>
    </div>

    @if($notifications->isEmpty())
    <div class="empty-state" style="padding:30px 16px"><p>No notifications recorded yet.</p></div>
    @endif
    @foreach($notifications as $n)
    <div class="mini-row @if(!$n->is_read) mini-row-unread @endif">
      <div class="m-ico" style="background:var(--info-bg);color:var(--info)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></div>
      <div class="m-body">
        <p>{{ $n->action }}</p>
        <span>{{ $n->module }} · {{ $n->created_at ? $n->created_at->diffForHumans() : '' }}</span>
      </div>
      <div class="flex gap-8" style="align-items:center;gap:8px">
        @if(!$n->is_read)
        <span class="badge badge-warning badge-dotted" style="font-size:10px">Unread</span>
        @endif
        <span class="badge badge-info badge-dotted" style="font-size:10px">{{ $n->created_at?->format('d M Y') }}</span>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endsection

@push('styles')
<style>
  .mini-row-unread{background:rgba(245,158,11,.07);}
  .mini-row-unread .m-body p{font-weight:700;}
</style>
@endpush
