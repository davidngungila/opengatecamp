@extends('layouts.app')

@section('title', 'Roles — OpenGate Camp Connect')
@section('crumb', 'System / Roles')
@section('page_title', 'Roles')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Roles</h2><div class="sub">{{ $roles->count() }} roles</div></div>
    <div class="flex gap-8">
      <a href="{{ route('users.index') }}" class="btn btn-secondary" style="text-decoration:none">Users</a>
      <a href="{{ route('users.permissions') }}" class="btn btn-secondary" style="text-decoration:none">Permissions</a>
    </div>
  </div>

  <div class="card-grid">
    @foreach($roles as $r)
    <div class="entity-card">
      <div class="ec-top">
        <div class="ec-ico" style="background:var(--purple-bg);color:var(--purple)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><h4>{{ $r->name }}</h4><div class="ec-sub">{{ is_array($r->permissions) ? count($r->permissions) : 0 }} permissions granted</div></div>
      </div>
      <div class="ec-stats">
        <div class="ec-stat"><b>{{ $r->users_count }}</b><span>Users</span></div>
        <div class="ec-stat"><b>{{ $r->is_super ? 'All access' : count($r->permissions ?? []).' / '.count($permissions) }}</b><span>Access</span></div>
      </div>
      <div class="flex gap-8" style="margin-top:14px">
        <a class="btn btn-secondary btn-sm" style="flex:1;text-decoration:none" href="{{ route('users.permissions') }}">Edit Permissions</a>
      </div>
    </div>
    @endforeach
  </div>
</div>
@endsection