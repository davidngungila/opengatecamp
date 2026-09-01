@extends('layouts.portal')

@section('title', 'Family — Member Portal')
@section('content')
<div class="fade-in">
  <h1 style="font-size:20px;font-weight:800;margin:0 0 18px;color:var(--navy-900)">My Family</h1>

  @if($family)
  <div class="portal-card">
    <h2>{{ $family->name }}</h2>
    @php $head = $family->members?->first(); @endphp
    @if($head)
    <div class="info-row"><span class="label">Head</span><span class="value">{{ $head->name }}</span></div>
    @endif
  </div>

  <div class="portal-card" style="padding:0;overflow:hidden">
    <div style="padding:18px 24px 0"><h2 style="margin:0 0 12px">Family Members</h2></div>
    <div style="overflow-x:auto">
      <table class="portal-table">
        <thead><tr><th>Name</th><th>Relationship</th><th>Phone</th><th>Member No</th></tr></thead>
        <tbody>
          @foreach($family->members ?? [] as $m)
          <tr>
            <td>{{ $m->name }}</td>
            <td>{{ $m->id === $member->id ? 'You' : '—' }}</td>
            <td>{{ $m->phone ?? '—' }}</td>
            <td>{{ $m->member_no ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @else
  <div class="portal-card" style="text-align:center;padding:40px 20px">
    <p style="color:var(--text-secondary)">No family record found for your account.</p>
  </div>
  @endif
</div>
@endsection