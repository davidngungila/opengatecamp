@extends('layouts.app')

@section('title', 'Fellowships & Delegations — OpenGate Camp Connect')
@section('crumb', 'Events / Fellowships & Delegations')
@section('page_title', 'Fellowships & Delegations')

@section('content')
@php
    $types = (new \App\Models\Fellowship())->types();
    $err = fn($field) => $errors->has($field) ? '<div style="color:var(--danger);font-size:11px;margin-top:4px">'.$errors->first($field).'</div>' : '';
@endphp
<div class="fade-in">
  <div class="section-head">
    <div><h2>University Fellowships &amp; Delegations</h2><div class="sub">
      Each fellowship is a delegation with one or more leaders. Leaders sign into the Member Portal to register and manage their members; payments, rooms and transport stay with the committee.
    </div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="fellowshipNewDrawer">+ New Fellowship</button>
  </div>

  <form class="toolbar" method="GET" action="{{ route('fellowships.index') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ old('q', $filters['q']) }}" placeholder="Search by name or university..."></div>
    <label class="check-line" style="padding:0 6px"><input type="checkbox" name="inactive" value="1" {{ $filters['showInactive'] ? 'checked' : '' }}> Show inactive</label>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Fellowship</th><th style="text-align:center">Leaders</th><th style="text-align:center">Delegation</th><th style="text-align:right">Paid (TZS)</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($fellowships as $f)
          <tr style="background:{{ $f->active ? '' : 'rgba(148,163,184,.06)' }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $f->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div>
                  <div class="cu-name">{{ $f->name }}</div>
                  <div class="cu-sub">
                    {{ $f->university ?: ucfirst((string) $f->getTypeLabel()) }}
                    @if(!$f->active)<span class="badge badge-neutral badge-dotted">Inactive</span>@endif
                  </div>
                </div>
              </div>
            </td>
            <td style="text-align:center">
              @if($f->leaders->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:2px;align-items:flex-start">
                  @foreach($f->leaders as $l)
                    <div style="display:flex;align-items:center;gap:6px">
                      <span class="badge badge-dotted {{ $l->pivot->is_primary ? 'badge-success' : 'badge-neutral' }}">{{ mb_substr($l->name, 0, 1) }}</span>
                      <span style="font-size:12px">{{ $l->name }}@if($l->pivot->is_primary) <span style="color:var(--success)">★</span>@endif</span>
                    </div>
                  @endforeach
                </div>
              @else
                <span class="badge badge-warning badge-dotted">No leader</span>
              @endif
            </td>
            <td style="text-align:center">
              <b>{{ $f->attendees_count ?? 0 }}</b>
              <div class="cu-sub">{{ ($f->confirmed_count ?? 0) }} confirmed · {{ ($f->attended_count ?? 0) }} attended</div>
            </td>
            <td style="text-align:right"><b style="color:var(--success)">{{ number_format($f->attendees_sum_amount_paid ?? 0) }}</b></td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-fell-{{ $f->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-fell-{{ $f->id }}">
                  <a href="{{ route('fellowships.members', $f) }}">Delegation</a>
                  <button type="button" data-fellowship-edit data-id="{{ $f->id }}" data-name="{{ $f->name }}" data-university="{{ $f->university }}" data-type="{{ $f->type }}" data-contact-name="{{ $f->contact_name }}" data-contact-phone="{{ $f->contact_phone }}" data-contact-email="{{ $f->contact_email }}" data-notes="{{ $f->notes }}" data-capacity="{{ $f->capacity }}" data-active="{{ $f->active ? 1 : 0 }}" data-leaders="{{ $f->leaders->pluck('id')->implode(',') }}" data-primary="{{ $f->primaryLeader()?->id }}">Edit</button>
                  @if($isAdmin)
                  <form method="POST" action="{{ route('fellowships.destroy', $f) }}" data-confirm data-confirm-title="Delete fellowship?" data-confirm-message="This removes '{{ $f->name }}' from the system. Fellowships with registered delegates cannot be deleted." data-confirm-label="Delete">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="5"><div class="empty-state" style="padding:40px 20px"><h3>No fellowships yet</h3><p>Create fellowships so leaders can build their delegations through the Member Portal.</p><button type="button" class="btn btn-accent" data-drawer-open="fellowshipNewDrawer">+ New Fellowship</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $fellowships->firstItem() ?? 0 }}–{{ $fellowships->lastItem() ?? 0 }} of {{ $fellowships->total() }} fellowships</span>
      <div class="pagination">{{ $fellowships->links() }}</div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="fellowshipNewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="fsTitle">New Fellowship</h3><p class="cu-sub">University fellowship / Christian union delegation</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="fsForm">
      @csrf
      <input type="hidden" name="_method" id="fsMethod" value="">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Fellowship Name *</label><input name="name" id="fsName" required placeholder="e.g. MoCU Catholic Students Union"></div>
          <div class="field"><label>Type</label><select name="type" id="fsType">
            <option value="">— Select —</option>
            @foreach($types as $k=>$t)<option value="{{ $k }}">{{ $t }}</option>@endforeach
          </select></div>
          <div class="field"><label>University / Institution</label><input name="university" id="fsUniversity" placeholder="e.g. Mwenge Catholic University"></div>
          <div class="field full"><label>Contact Person</label><input name="contact_name" id="fsContactName" placeholder="Fellowship head / representative"></div>
          <div class="field"><label>Contact Phone</label><input name="contact_phone" id="fsContactPhone" placeholder="+255 7XX XXX XXX"></div>
          <div class="field"><label>Contact Email</label><input name="contact_email" id="fsContactEmail" placeholder="email@example.com"></div>
          <div class="field"><label>Delegation Target (capacity)</label><input type="number" min="0" name="capacity" id="fsCapacity" placeholder="e.g. 20"></div>
          <div class="field">
            <label>Status</label>
            <select name="active" id="fsActive"><option value="1">Active</option><option value="0">Inactive</option></select>
          </div>
          <div class="field full">
            <label>Leaders</label>
            <select name="leader_ids[]" id="fsLeaders" multiple size="6" style="min-height:120px">
              @foreach($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
              @endforeach
            </select>
            <div class="field-hint">Hold Ctrl / Cmd to select multiple. Leaders sign into the Member Portal with their normal account.</div>
          </div>
          <div class="field full">
            <label>Primary Leader</label>
            <select name="primary_leader_id" id="fsPrimary"><option value="">— None —</option>
              @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
            </select>
            <div class="field-hint">Shown as the main contact for the fellowship.</div>
          </div>
          <div class="field full"><label>Notes</label><textarea name="notes" id="fsNotes" placeholder="Optional notes about this fellowship"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="fsSubmit">Save Fellowship</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-fellowship-edit]');
  if(!btn) return;

  var title = document.getElementById('fsTitle');
  var sub = document.querySelector('#fsTitle + p');
  var form = document.getElementById('fsForm');
  var method = document.getElementById('fsMethod');
  var submit = document.getElementById('fsSubmit');

  title.textContent = 'Edit Fellowship';
  method.value = 'PUT';
  form.action = "{{ url('/fellowships') }}/" + btn.dataset.id;
  submit.textContent = 'Update Fellowship';

  document.getElementById('fsName').value = btn.dataset.name || '';
  document.getElementById('fsType').value = btn.dataset.type || '';
  document.getElementById('fsUniversity').value = btn.dataset.university || '';
  document.getElementById('fsContactName').value = btn.dataset.contactName || '';
  document.getElementById('fsContactPhone').value = btn.dataset.contactPhone || '';
  document.getElementById('fsContactEmail').value = btn.dataset.contactEmail || '';
  document.getElementById('fsCapacity').value = btn.dataset.capacity || '';
  document.getElementById('fsActive').value = btn.dataset.active === '1' ? '1' : '0';
  document.getElementById('fsNotes').value = btn.dataset.notes || '';

  var leaderOpts = document.getElementById('fsLeaders').options;
  var leaders = (btn.dataset.leaders || '').split(',').filter(Boolean).map(Number);
  for (var i = 0; i < leaderOpts.length; i++) {
    leaderOpts[i].selected = leaders.indexOf(Number(leaderOpts[i].value)) !== -1;
  }
  document.getElementById('fsPrimary').value = btn.dataset.primary || '';

  openDrawerById('fellowshipNewDrawer');
});

document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-drawer-open="fellowshipNewDrawer"]');
  if(!btn) return;
  document.getElementById('fsTitle').textContent = 'New Fellowship';
  document.getElementById('fsMethod').value = '';
  document.getElementById('fsForm').action = "{{ route('fellowships.store') }}";
  document.getElementById('fsSubmit').textContent = 'Save Fellowship';
  document.getElementById('fsForm').reset();
  document.getElementById('fsActive').value = '1';
});
</script>
@endpush