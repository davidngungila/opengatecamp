@extends('layouts.app')

@section('title', 'Messaging â€” Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging')
@section('page_title', 'Messaging Center')

@php
    $tab = $tab ?? 'sms';
    $tabs = [['sms','SMS'],['email','Email'],['notif','Notifications'],['templates','Templates'],['settings','Settings']];
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Messaging Center</h2>
    @if($tab === 'sms' && ! $smsConfigured)
      <span class="badge badge-danger" style="font-size:11px">SMS API not configured â€” go to Settings tab</span>
    @endif
    @if($tab === 'sms' && $smsConfigured)
      <span class="badge badge-success" style="font-size:11px">SMS API ready</span>
    @endif
  </div>

  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ session('error') }}
  </div>
  @endif

  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}
  </div>
  @endif

  <div class="tabs-bar">
    @foreach($tabs as $t)
      <a href="{{ route('messaging.index', ['tab' => $t[0]]) }}" class="tab-btn {{ $tab===$t[0] ? 'active' : '' }}">{{ $t[1] }}</a>
    @endforeach
  </div>

  @if(in_array($tab, ['sms','email']))
    @include('messaging._compose')
  @elseif($tab === 'settings')
    @include('messaging._settings')
  @elseif($tab === 'notif')
    @include('messaging._notifications')
  @else
    @include('messaging._templates')
  @endif
</div>
@endsection
