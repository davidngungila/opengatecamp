@extends('layouts.portal')

@section('title', 'Member Portal')
@section('content')
<div class="portal-card" style="text-align:center;padding:44px 24px">
  <h2 style="margin:0 0 8px">No Member Record Linked</h2>
  <p style="color:var(--text-secondary);font-size:13.5px;margin:0 0 18px">
    Your login is not linked to a member record in the system. Contact the administrator to link your account.
  </p>
  <a href="{{ route('portal.profile') }}" class="btn btn-accent">Go to Settings</a>
</div>
@endsection