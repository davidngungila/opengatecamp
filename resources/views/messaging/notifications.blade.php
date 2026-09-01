@extends('layouts.app')
@section('title', 'Notifications â€” Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / Notifications')
@section('page_title', 'Notifications')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Notifications</h2>
  </div>
  @include('messaging._tabs', ['active' => 'notifications'])

  <div class="glass-card">
    <h2 style="font-size:14.5px;margin:0 0 14px">Recent System Notifications</h2>
    @if($notifications->isEmpty())
    <div class="empty-state" style="padding:30px 16px"><p>No notifications recorded yet.</p></div>
    @endif
    @foreach($notifications as $n)
    <div class="mini-row">
      <div class="m-ico" style="background:var(--info-bg);color:var(--info)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></div>
      <div class="m-body">
        <p>{{ $n->action }}</p>
        <span>{{ $n->section }} Â· {{ $n->created_at ? $n->created_at->diffForHumans() : '' }}</span>
      </div>
      <span class="badge badge-info badge-dotted" style="font-size:10px">{{ $n->created_at?->format('d M Y') }}</span>
    </div>
    @endforeach
  </div>
</div>
@endsection
