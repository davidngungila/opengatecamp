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
          <tr style="cursor:pointer;background:{{ $f->active ? '' : 'rgba(148,163,184,.06)' }}"
              data-view-fellowship
              data-id="{{ $f->id }}"
              data-name="{{ $f->name }}"
              data-university="{{ $f->university }}"
              data-type="{{ $f->type }}"
              data-type-label="{{ $f->getTypeLabel() }}"
              data-contact-name="{{ $f->contact_name }}"
              data-contact-phone="{{ $f->contact_phone }}"
              data-contact-email="{{ $f->contact_email }}"
              data-capacity="{{ $f->capacity }}"
              data-active="{{ $f->active ? 1 : 0 }}"
              data-notes="{{ $f->notes }}"
              data-leaders="{{ $f->leaders->pluck('id')->implode(',') }}"
              data-leaders-json="{{ $f->leaders->map(fn($u)=>['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'is_primary'=> (bool)$u->pivot->is_primary])->values()->toJson(JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP) }}"
              data-attendees-count="{{ $f->attendees_count ?? 0 }}"
              data-confirmed-count="{{ $f->confirmed_count ?? 0 }}"
              data-attended-count="{{ $f->attended_count ?? 0 }}"
              data-paid="{{ $f->attendees_sum_amount_paid ?? 0 }}"
              data-delegation-url="{{ route('fellowships.members', $f) }}"
              data-edit-payload="{{ json_encode(['id'=>$f->id,'name'=>$f->name,'university'=>$f->university,'type'=>$f->type,'contact_name'=>$f->contact_name,'contact_phone'=>$f->contact_phone,'contact_email'=>$f->contact_email,'capacity'=>$f->capacity,'active'=>$f->active,'notes'=>$f->notes,'leaders'=>$f->leaders->pluck('id')->values(),'primary'=>$f->primaryLeader()?->id], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP) }}"
              data-delete-url="{{ route('fellowships.destroy', $f) }}"
              data-delete-name="{{ $f->name }}">
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
                  <button type="button" data-view-delegation data-id="{{ $f->id }}">Delegation</button>
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

<div class="drawer-overlay" id="fellowshipDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="fellDrawerName">Fellowship Details</h3><p id="fellDrawerType" class="badge badge-purple badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="avatar avatar-lg" id="fellDrawerAvatar" style="background:linear-gradient(135deg,var(--navy-800),var(--blue-accent));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">—</div>
        <div>
          <div style="font-size:16px;font-weight:800" id="fellDrawerFullName">—</div>
          <div style="font-size:12.5px;color:var(--text-tertiary);font-weight:600" id="fellDrawerUniversity">—</div>
        </div>
      </div>
      <div class="info-grid">
        <div class="info-row"><span>Type</span><b id="fellDrawerTypeVal">—</b></div>
        <div class="info-row"><span>University</span><b id="fellDrawerUniversityVal">—</b></div>
        <div class="info-row"><span>Contact</span><b id="fellDrawerContact">—</b></div>
        <div class="info-row"><span>Phone</span><b id="fellDrawerPhone">—</b></div>
        <div class="info-row"><span>Email</span><b id="fellDrawerEmail">—</b></div>
        <div class="info-row"><span>Capacity</span><b id="fellDrawerCapacity">—</b></div>
        <div class="info-row"><span>Status</span><b id="fellDrawerStatus">—</b></div>
        <div class="info-row"><span>Notes</span><b id="fellDrawerNotes" style="font-weight:500;color:var(--text-secondary)">—</b></div>
      </div>

      <div class="payments-head" style="margin-top:18px">
        <span>Delegation</span><span class="payments-count" id="fellDrawerDelCount">0</span>
      </div>
      <div class="info-grid" style="margin-bottom:12px">
        <div class="info-row"><span>Registered</span><b id="fellDrawerRegistered">0</b></div>
        <div class="info-row"><span>Confirmed</span><b id="fellDrawerConfirmed">0</b></div>
        <div class="info-row"><span>Attended</span><b id="fellDrawerAttended">0</b></div>
        <div class="info-row"><span>Paid (TZS)</span><b id="fellDrawerPaid" style="color:var(--success)">0</b></div>
      </div>

      <div class="payments-head">
        <span>Leaders</span><span class="payments-count" id="fellDrawerLeaderCount">0</span>
      </div>
      <div id="fellDrawerLeaders" class="payments-list" style="margin-bottom:14px"></div>
      <div id="fellDrawerNoLeaders" style="display:none;padding:12px;text-align:center;color:var(--text-tertiary);font-size:13px;border:1px dashed var(--border-strong);border-radius:10px">No leaders assigned.</div>

      <div class="drawer-actions" style="display:flex;flex-direction:column;gap:8px;margin-top:18px">
        <button type="button" id="fellDrawerDelegationBtn" class="daction"><div class="daction-ico" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div><div class="daction-txt"><b>View Delegation</b><small>See all registered members — drawer</small></div><span class="daction-arrow">›</span></button>
        <button type="button" id="fellDrawerEditBtn" class="daction"><div class="daction-ico" style="background:var(--purple-bg);color:var(--purple)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div><div class="daction-txt"><b>Edit Fellowship</b><small>Update details & leaders</small></div><span class="daction-arrow">›</span></button>
        @if($isAdmin)
        <form id="fellDrawerDeleteForm" method="POST" action="" data-confirm data-confirm-title="Delete fellowship?" data-confirm-message="This removes the fellowship. Fellowships with delegates cannot be deleted." data-confirm-label="Delete" style="margin:0">
          @csrf @method('DELETE')
          <button type="submit" class="daction" style="width:100%;border-color:var(--danger-bg)"><div class="daction-ico" style="background:var(--danger-bg);color:var(--danger)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></div><div class="daction-txt"><b style="color:var(--danger)">Delete Fellowship</b><small>Remove permanently</small></div><span class="daction-arrow">›</span></button>
        </form>
        @endif
      </div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="fellowshipDelegationDrawer">
  <div class="drawer-panel" style="max-width:720px">
    <div class="drawer-head">
      <div><h3 id="delegDrawerTitle">Delegation</h3><p id="delegDrawerSub" style="font-size:12.5px;color:var(--text-tertiary);margin:4px 0 0">Members registered under this fellowship</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="kpi-grid cols-3" style="margin-bottom:16px;gap:12px">
        <div class="kpi-card" style="padding:14px"><div class="kpi-label" style="margin:0">Registered</div><div class="kpi-value" style="font-size:18px" id="delegStatRegistered">0</div></div>
        <div class="kpi-card" style="padding:14px"><div class="kpi-label" style="margin:0">Confirmed</div><div class="kpi-value" style="font-size:18px" id="delegStatConfirmed">0</div></div>
        <div class="kpi-card" style="padding:14px"><div class="kpi-label" style="margin:0">Paid (TZS)</div><div class="kpi-value" style="font-size:18px;color:var(--success)" id="delegStatPaid">0</div></div>
      </div>
      <div id="delegLoading" style="display:none;padding:18px;text-align:center;color:var(--text-tertiary);font-size:13px">Loading delegation…</div>
      <div id="delegEmpty" style="display:none;padding:22px;text-align:center;color:var(--text-tertiary);font-size:13px;border:1px dashed var(--border-strong);border-radius:10px">No members registered yet for this fellowship.</div>
      <div class="table-scroll" id="delegTableWrap" style="display:none;border:1px solid var(--border);border-radius:12px;overflow:hidden">
        <table class="data-table compact" style="min-width:600px">
          <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Status</th><th style="text-align:right">Paid</th><th>Event</th></tr></thead>
          <tbody id="delegTableBody"></tbody>
        </table>
      </div>
    </div>
    <div class="drawer-foot">
      <span id="delegCountHint" style="margin-right:auto;font-size:12px;font-weight:700;color:var(--text-tertiary)"></span>
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
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

var __currentFell = null;
function openFellowshipDetailFromRow(tr){
  __currentFell = tr;
  var name = tr.dataset.name || '—';
  var university = tr.dataset.university || '—';
  var typeLabel = tr.dataset.typeLabel || tr.dataset.type || '—';
  var contactName = tr.dataset.contactName || '';
  var contactPhone = tr.dataset.contactPhone || '';
  var contactEmail = tr.dataset.contactEmail || '';
  var capacity = tr.dataset.capacity || '';
  var active = tr.dataset.active === '1';
  var notes = tr.dataset.notes || '';
  var leadersJson = tr.dataset.leadersJson || '[]';
  var attendees = tr.dataset.attendeesCount || '0';
  var confirmed = tr.dataset.confirmedCount || '0';
  var attended = tr.dataset.attendedCount || '0';
  var paid = tr.dataset.paid || '0';
  var delegationUrl = tr.dataset.delegationUrl || '#';
  var deleteUrl = tr.dataset.deleteUrl || '#';

  document.getElementById('fellDrawerName').textContent = name;
  document.getElementById('fellDrawerFullName').textContent = name;
  document.getElementById('fellDrawerUniversity').textContent = university || typeLabel;
  document.getElementById('fellDrawerType').textContent = typeLabel;
  document.getElementById('fellDrawerTypeVal').textContent = typeLabel;
  document.getElementById('fellDrawerUniversityVal').textContent = university || '—';
  document.getElementById('fellDrawerContact').textContent = contactName || '—';
  document.getElementById('fellDrawerPhone').textContent = contactPhone || '—';
  document.getElementById('fellDrawerEmail').textContent = contactEmail || '—';
  document.getElementById('fellDrawerCapacity').textContent = capacity ? capacity : '—';
  var statusEl = document.getElementById('fellDrawerStatus');
  statusEl.textContent = active ? 'Active' : 'Inactive';
  statusEl.style.color = active ? 'var(--success)' : 'var(--text-tertiary)';
  document.getElementById('fellDrawerNotes').textContent = notes || '—';

  document.getElementById('fellDrawerRegistered').textContent = attendees;
  document.getElementById('fellDrawerConfirmed').textContent = confirmed;
  document.getElementById('fellDrawerAttended').textContent = attended;
  document.getElementById('fellDrawerPaid').textContent = Number(paid).toLocaleString();
  document.getElementById('fellDrawerDelCount').textContent = attendees;

  var av = document.getElementById('fellDrawerAvatar');
  av.textContent = (name||'?').split(' ').filter(function(w){return w;}).slice(0,2).map(function(w){return w.charAt(0).toUpperCase();}).join('').slice(0,2) || '?';

  var leaders = [];
  try{ leaders = JSON.parse(leadersJson); } catch(e){ leaders=[]; }
  var list = document.getElementById('fellDrawerLeaders');
  var noLeaders = document.getElementById('fellDrawerNoLeaders');
  list.innerHTML = '';
  document.getElementById('fellDrawerLeaderCount').textContent = leaders.length;
  if(!leaders.length){
    noLeaders.style.display = 'block';
  } else {
    noLeaders.style.display = 'none';
    leaders.forEach(function(u){
      var div = document.createElement('div');
      div.className = 'pay-item';
      div.innerHTML = '<div class="pay-ico" style="background:'+(u.is_primary ? 'var(--success-bg)' : 'var(--purple-bg)')+';color:'+(u.is_primary ? 'var(--success)' : 'var(--purple)')+'">'+(u.name||'?').charAt(0).toUpperCase()+'</div>'
        + '<div class="pay-main"><div class="pm-name">'+u.name+(u.is_primary ? ' <span style="color:var(--success);font-size:11px">★ Primary</span>' : '')+'</div><div class="pm-sub">'+(u.email||'')+'</div></div>'
        + '<div class="pay-amt" style="font-size:11px;font-weight:700;color:var(--text-tertiary)">'+(u.is_primary ? 'Primary' : 'Leader')+'</div>';
      list.appendChild(div);
    });
  }

  // delegation button now opens drawer via API, not navigation
  var delegBtn = document.getElementById('fellDrawerDelegationBtn');
  if(delegBtn) delegBtn.dataset.fellowshipId = tr.dataset.id;

  var delForm = document.getElementById('fellDrawerDeleteForm');
  if(delForm) delForm.action = deleteUrl;

  openDrawerById('fellowshipDetailDrawer');
}

function openDelegationDrawer(fellowshipId){
  var titleEl = document.getElementById('delegDrawerTitle');
  var subEl = document.getElementById('delegDrawerSub');
  var body = document.getElementById('delegTableBody');
  var wrap = document.getElementById('delegTableWrap');
  var empty = document.getElementById('delegEmpty');
  var loading = document.getElementById('delegLoading');
  var hint = document.getElementById('delegCountHint');
  if(__currentFell){
    titleEl.textContent = (__currentFell.dataset.name || 'Delegation') + ' — Delegation';
    subEl.textContent = __currentFell.dataset.university ? __currentFell.dataset.university + ' · ' + (__currentFell.dataset.typeLabel || '') : (__currentFell.dataset.typeLabel || 'Fellowship delegation');
  }
  body.innerHTML = '';
  wrap.style.display = 'none';
  empty.style.display = 'none';
  loading.style.display = 'block';
  hint.textContent = '';
  openDrawerById('fellowshipDelegationDrawer');
  fetch('/api/fellowships/' + encodeURIComponent(fellowshipId) + '/delegation', {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
    .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(function(data){
      loading.style.display = 'none';
      var attendees = data.attendees || [];
      var stats = data.stats || {};
      document.getElementById('delegStatRegistered').textContent = stats.registered ?? attendees.length;
      document.getElementById('delegStatConfirmed').textContent = stats.confirmed ?? 0;
      document.getElementById('delegStatPaid').textContent = Number(stats.paid ?? 0).toLocaleString();
      hint.textContent = attendees.length + ' member(s)';
      if(!attendees.length){
        empty.style.display = 'block';
        return;
      }
      wrap.style.display = 'block';
      attendees.forEach(function(a, idx){
        var tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid var(--border)';
        var statusColor = (a.status_raw === 'attended' || a.status === 'Attended') ? 'success' : (a.status_raw === 'confirmed' ? 'info' : (a.status_raw === 'pending' ? 'warning' : 'neutral'));
        tr.innerHTML = '<td style="padding:8px 10px;color:var(--text-tertiary);font-weight:700;font-size:12px">'+(idx+1)+'</td>'
          + '<td style="padding:8px 10px;font-weight:700">'+a.name+'</td>'
          + '<td style="padding:8px 10px;font-family:ui-monospace,monospace;font-size:12.5px">'+(a.phone||'—')+'</td>'
          + '<td style="padding:8px 10px"><span class="badge badge-'+statusColor+' badge-dotted" style="font-size:10.5px">'+a.status+'</span></td>'
          + '<td style="padding:8px 10px;text-align:right;font-weight:700;color:var(--success)">'+Number(a.paid||0).toLocaleString()+'</td>'
          + '<td style="padding:8px 10px;font-size:12px;color:var(--text-tertiary)">'+(a.event||'—')+'</td>';
        body.appendChild(tr);
      });
    })
    .catch(function(err){
      loading.style.display = 'none';
      empty.style.display = 'block';
      empty.textContent = 'Failed to load delegation: ' + (err.message||'error');
      console.error(err);
      toast('Could not load delegation: ' + (err.message||''), 'error');
    });
}

document.addEventListener('click', function(e){
  var tr = e.target.closest('[data-view-fellowship]');
  if(!tr) return;
  if(e.target.closest('.action-menu-wrap') || e.target.closest('a') || e.target.closest('button')) return;
  // also ignore if clicking the action trigger itself (handled above)
  openFellowshipDetailFromRow(tr);
});

  document.getElementById('fellDrawerEditBtn').addEventListener('click', function(){
  if(!__currentFell) return;
  closeDrawerById('fellowshipDetailDrawer');
  // trigger existing edit flow using the same data-attributes as the row's edit button
  var btn = document.createElement('button');
  btn.dataset.id = __currentFell.dataset.id;
  btn.dataset.name = __currentFell.dataset.name;
  btn.dataset.university = __currentFell.dataset.university;
  btn.dataset.type = __currentFell.dataset.type;
  btn.dataset.contactName = __currentFell.dataset.contactName;
  btn.dataset.contactPhone = __currentFell.dataset.contactPhone;
  btn.dataset.contactEmail = __currentFell.dataset.contactEmail;
  btn.dataset.capacity = __currentFell.dataset.capacity;
  btn.dataset.active = __currentFell.dataset.active;
  btn.dataset.notes = __currentFell.dataset.notes;
  btn.dataset.leaders = __currentFell.dataset.leaders;
  btn.dataset.primary = (function(){
    try{
      var arr = JSON.parse(__currentFell.dataset.leadersJson||'[]');
      var p = arr.find(function(x){return x.is_primary;});
      return p ? p.id : '';
    }catch(e){ return ''; }
  })();
  // reuse existing edit handler by dispatching click on a temp element with data-fellowship-edit
  // directly call the same logic
  var title = document.getElementById('fsTitle');
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
  setTimeout(function(){ openDrawerById('fellowshipNewDrawer'); }, 180);
});

document.getElementById('fellDrawerDelegationBtn').addEventListener('click', function(){
  if(!__currentFell) return;
  var fid = __currentFell.dataset.id;
  closeDrawerById('fellowshipDetailDrawer');
  setTimeout(function(){ openDelegationDrawer(fid); }, 180);
});

document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-view-delegation]');
  if(!btn) return;
  var fid = btn.dataset.id;
  document.querySelectorAll('.action-menu.open').forEach(function(m){m.classList.remove('open');});
  // find row to set __currentFell for header consistency if needed
  var tr = document.querySelector('[data-view-fellowship][data-id="'+fid+'"]');
  if(tr) __currentFell = tr;
  openDelegationDrawer(fid);
});
</script>
@endpush