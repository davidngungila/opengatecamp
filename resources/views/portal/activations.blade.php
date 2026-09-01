@extends('layouts.portal')

@section('title', 'Activations — Member Portal')
@section('content')
<div class="fade-in">
  <h1 style="font-size:20px;font-weight:800;margin:0 0 18px;color:var(--navy-900)">My Activations</h1>

  @if($activated)
  <div class="portal-card" style="text-align:center;padding:28px 20px">
    <span class="portal-badge active">Activated</span>
    <p style="margin:12px 0 0;color:var(--text-secondary);font-size:13px">You have an active activation for the current financial year.</p>
  </div>
  @else
  <div class="portal-card" style="text-align:center;padding:28px 20px">
    <span class="portal-badge pending">Not Activated</span>
    <p style="margin:12px 0 0;color:var(--text-secondary);font-size:13px">Your activation for the current financial year is pending.</p>
  </div>
  @endif

  <div class="portal-card" style="padding:0;overflow:hidden">
    <div style="padding:18px 24px 0"><h2 style="margin:0 0 12px">Activation History</h2></div>
    <div style="overflow-x:auto">
      <table class="portal-table">
        <thead><tr><th>Financial Year</th><th>Status</th><th>Activated On</th></tr></thead>
        <tbody>
          @forelse($activations as $a)
          <tr>
            <td>{{ $a->financialYear?->name ?? '—' }}</td>
            <td><span class="portal-badge active">Activated</span></td>
            <td>{{ $a->activated_on?->format('d M Y') ?? \Carbon\Carbon::parse($a->created_at)->format('d M Y') }}</td>
          </tr>
          @empty
          <tr><td colspan="3" style="text-align:center;padding:36px 20px">No activation records found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection