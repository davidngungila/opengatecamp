@extends('layouts.portal')

@section('title', $fellowship->name.' — Delegation Members — Member Portal')
@section('content')
<div class="fade-in">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px">
    <div>
      <h1 style="font-size:20px;font-weight:800;margin:0 0 4px;color:var(--navy-900)">{{ $fellowship->name }}</h1>
      <p style="margin:0;font-size:13px;color:var(--text-secondary)">
        <a href="{{ route('portal.fellowship.dashboard') }}" style="color:var(--blue-accent);text-decoration:none">← My Fellowship</a>
        @if($fellowship->university) · {{ $fellowship->university }}@endif
      </p>
    </div>
    <button type="button" class="btn btn-accent" data-drawer-open="leaderAddMemberDrawer">+ Add Member</button>
  </div>

  <div class="stat-grid">
    <div class="stat-card blue"><div class="stat-value">{{ $attendees->total() }}</div><div class="stat-label">Delegates</div></div>
    <div class="stat-card green"><div class="stat-value">{{ number_format($attendees->getCollection()->whereIn('status', ['confirmed','attended'])->count()) }}</div><div class="stat-label">Confirmed</div></div>
    <div class="stat-card orange"><div class="stat-value">TZS {{ number_format($attendees->getCollection()->sum('amount_paid')) }}</div><div class="stat-label">Paid</div></div>
  </div>

  <div class="portal-card" style="padding:0;overflow:hidden">
    <div style="padding:16px 22px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
      <h2 style="margin:0 0 12px">Delegation Members</h2>
      <span style="font-size:12px;color:var(--text-tertiary)">Payments, rooms &amp; transport are managed by the committee.</span>
    </div>
    <div style="overflow-x:auto">
      <table class="portal-table">
        <thead><tr><th>Member</th><th>Pickup</th><th>Fee</th><th>Paid</th><th>Balance</th><th>Status</th><th>Arrived</th><th>Actions</th></tr></thead>
        <tbody>
          @forelse($attendees as $a)
          @php
            $bal = ($a->fee_amount !== null) ? max(0, $a->fee_amount - ($a->amount_paid ?? 0)) : 0;
          @endphp
          <tr>
            <td>
              <div><b>{{ $a->name ?? '—' }}</b></div>
              <div style="font-size:11.5px;color:var(--text-tertiary)">{{ $a->phone }}{{ $a->email ? ' · '.$a->email : '' }}</div>
            </td>
            <td>{{ $a->getRegionLabel() }}</td>
            <td>{{ $a->fee_amount !== null ? number_format($a->fee_amount) : '—' }}</td>
            <td style="color:{{ ($a->amount_paid ?? 0) > 0 ? 'var(--success)' : 'var(--text-tertiary)' }};font-weight:700">{{ number_format($a->amount_paid ?? 0) }}</td>
            <td style="color:{{ $bal > 0 ? 'var(--warning)' : 'var(--success)' }};font-weight:700">{{ number_format($bal) }}</td>
            <td><span class="portal-badge {{ $a->getStatusColor() === 'success' ? 'active' : ($a->getStatusColor() === 'danger' ? 'pending' : ($a->getStatusColor() === 'info' ? 'info' : 'pending')) }}">{{ $a->getStatusLabel() }}</span></td>
            <td>@if($a->checked_in_at)<span class="portal-badge active">Yes</span>@else<span class="portal-badge pending">No</span>@endif</td>
            <td>
              <div style="display:flex;gap:6px;align-items:center">
                <button type="button" class="btn btn-ghost btn-sm"
                  data-leader-edit
                  data-id="{{ $a->hashed_id }}"
                  data-name="{{ $a->name }}"
                  data-phone="{{ $a->phone }}"
                  data-email="{{ $a->email }}"
                  data-pickup="{{ $a->pickup_location }}"
                  data-notes="{{ $a->notes }}">Edit</button>
                <form method="POST" action="{{ route('portal.fellowship.members.destroy', $a->hashed_id) }}"
                      data-confirm data-confirm-title="Remove {{ $a->name }}?" data-confirm-message="This removes {{ $a->name }} from the {{ $fellowship->name }} delegation. Members with payments or arrival records cannot be removed." data-confirm-label="Remove">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">Remove</button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8" style="text-align:center;padding:40px 20px">
            No members in this delegation yet.<br>
            <button type="button" class="btn btn-accent" style="margin-top:12px" data-drawer-open="leaderAddMemberDrawer">+ Add Member</button>
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div style="padding:14px 22px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text-secondary)">
      Showing {{ $attendees->firstItem() ?? 0 }}–{{ $attendees->lastItem() ?? 0 }} of {{ $attendees->total() }}
      <div style="float:right">{{ $attendees->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="leaderAddMemberDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Add Member</h3><p class="cu-sub">{{ $fellowship->name }} — {{ $currentCamp?->title ?? 'Open Gate Camp' }}</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('portal.fellowship.members.store', $fellowship) }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Full Name *</label><input name="name" value="{{ old('name') }}" placeholder="Full name" required></div>
          <div class="field full"><label>Phone *</label><input name="phone" value="{{ old('phone') }}" placeholder="+255 7XX XXX XXX" required></div>
          <div class="field full"><label>Email</label><input name="email" value="{{ old('email') }}" placeholder="email@example.com"></div>
          <div class="field full"><label>Coming From *</label><select name="pickup_location" id="leaderPickup" required>
            <option value="">— Select —</option>
            <option value="arusha" @if((old('pickup_location') ?? $defaultPickup ?? '')==='arusha') selected @endif>Arusha</option>
            <option value="moshi" @if((old('pickup_location') ?? $defaultPickup ?? '')==='moshi') selected @endif>Moshi</option>
          </select>
            <div class="field-hint" style="font-size:11px">Auto-filled from your fellowship ({{ $fellowship->name }}) — University Fellowship is filtered from this.</div>
          </div>
          <div class="field full"><label>University Fellowship</label><input value="{{ $fellowship->name }}@if($fellowship->university) — {{ $fellowship->university }}@endif" disabled style="background:var(--blue-light);font-weight:700;color:var(--navy-900)"><div class="field-hint" style="font-size:11px">Auto-filled — shows only fellowships that belong to selected Coming From ({{ $defaultPickup === 'moshi' ? 'Moshi' : 'Arusha' }}).</div></div>
          <div class="field full"><label>Notes</label><textarea name="notes" placeholder="Dietary, transport, special needs...">{{ old('notes') }}</textarea></div>
          <div class="field full" style="color:var(--text-tertiary);font-size:12px">
            Camp fee is set automatically (TZS {{ number_format($fee) }}). Payment will be recorded by the committee.
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Add Member</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="leaderEditMemberDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Edit Member</h3><p class="cu-sub">{{ $fellowship->name }}</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="leaderEditForm">
      @csrf
      @method('PUT')
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Full Name *</label><input name="name" id="leName" required></div>
          <div class="field full"><label>Phone *</label><input name="phone" id="lePhone" required></div>
          <div class="field full"><label>Email</label><input name="email" id="leEmail"></div>
          <div class="field full"><label>Pickup Location *</label><select name="pickup_location" id="lePickup" required>
            <option value="arusha">Arusha</option>
            <option value="moshi">Moshi</option>
          </select></div>
          <div class="field full"><label>Notes</label><textarea name="notes" id="leNotes"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-leader-edit]');
  if(!btn) return;
  document.getElementById('leName').value = btn.dataset.name || '';
  document.getElementById('lePhone').value = btn.dataset.phone || '';
  document.getElementById('leEmail').value = btn.dataset.email || '';
  document.getElementById('lePickup').value = btn.dataset.pickup || 'arusha';
  document.getElementById('leNotes').value = btn.dataset.notes || '';
  document.getElementById('leaderEditForm').action = "{{ url('/portal/fellowship/members') }}/" + btn.dataset.id;
  openDrawerById('leaderEditMemberDrawer');
});
</script>
@endpush