@extends('layouts.app')

@section('title', 'Members â€” Open Gate Camp Mission')
@section('crumb', 'People / Members')
@section('page_title', 'Members')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Members</h2><div class="sub">{{ $members->total() }} of {{ $total }} member records @if(request()->boolean('all_time')) Â· all periods @endif</div></div>
    <div class="flex gap-8">
      @if($fy && $unactivatedCount > 0)
      <form method="POST" action="{{ route('members.activateAll') }}"
            data-confirm data-confirm-title="Activate all student accounts?"
            data-confirm-message="{{ $unactivatedCount }} student(s) will be activated for {{ $fy->name }}."
            data-confirm-label="Activate All">
        @csrf
        <button type="submit" class="btn btn-accent">Activate Students ({{ $unactivatedCount }})</button>
      </form>
      @endif
      <button type="button" class="btn btn-secondary" data-modal-open="memberModal" onclick="resetMemberType()">+ Add Member</button>
    </div>
  </div>

  <form class="toolbar" method="GET" action="{{ url('/members') }}">
    {{ csrf_field() }}
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ request('q') }}" placeholder="Search by name, member no, or phone..."></div>
    <select class="filter-select" name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      @foreach(['Active','Inactive','New'] as $opt)
        <option value="{{ $opt }}" {{ request('status')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
      @endforeach
    </select>
    <select class="filter-select" name="member_type" onchange="this.form.submit()">
      <option value="">All Types</option>
      <option value="student" {{ request('member_type')==='student' ? 'selected' : '' }}>Student</option>
      <option value="non_student" {{ request('member_type')==='non_student' ? 'selected' : '' }}>Non-Student</option>
      @if(request('staff_type'))
        <option value="non_student" selected hidden></option>
      @endif
    </select>
    <select class="filter-select" name="staff_type" onchange="this.form.submit()">
      <option value="">Staff / Non-Staff</option>
      <option value="staff" {{ request('staff_type')==='staff' ? 'selected' : '' }}>Staff</option>
      <option value="non_staff" {{ request('staff_type')==='non_staff' ? 'selected' : '' }}>Non-Staff</option>
    </select>
    <select class="filter-select" name="group_id" onchange="this.form.submit()">
      <option value="">All Groups</option>
      @foreach($groups as $g)
        <option value="{{ $g->id }}" {{ request('group_id')==($g->id) ? 'selected' : '' }}>{{ $g->name }}</option>
      @endforeach
    </select>
    <button type="button" class="btn btn-secondary btn-sm" onclick="toast('Export started','success')">Export</button>
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Member</th><th>Phone</th><th>Group</th><th>Ministry</th><th>Status</th><th>Joined</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($members as $i => $m)
          @php
              $needsActivation = $m->isStudent() && $fy && ! $activatedIds->contains($m->id);
              $typeLabel = $m->member_type === 'student' ? 'Student' : ucfirst(str_replace('_', '-', (string) $m->staff_type));
          @endphp
          <tr>
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $m->name))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div><div class="cu-name">{{ $m->name }}</div><div class="cu-sub">{{ $m->member_no }} Â· {{ $typeLabel }}</div></div>
              </div>
            </td>
            <td>{{ $m->phone }}</td>
            <td>{{ $m->group?->name ?? 'â€”' }}</td>
            <td>{{ $m->ministry?->name ?? 'â€”' }}</td>
            <td><span class="badge badge-{{ $m->status==='Active' ? 'success' : ($m->status==='New' ? 'info' : 'neutral') }} badge-dotted">{{ $m->status }}</span>
              @if($needsActivation)<span class="badge badge-warning badge-dotted" style="margin-left:6px">Not Activated</span>@endif
            </td>
            <td>{{ $m->joined_on?->format('d M Y') }}</td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-members-{{ $m->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-members-{{ $m->id }}">
                  <a href="{{ route('members.profile', urlencode(Crypt::encryptString($m->id))) }}">View Profile</a>
                  <a href="{{ route('members.edit', $m) }}">Edit</a>
                  @if($needsActivation)
                  <form method="POST" action="{{ route('members.activate', $m) }}">@csrf
                    <button type="submit">Activate for {{ $fy?->name }}</button>
                  </form>
                  @endif
                  <form method="POST" action="{{ route('members.status', $m) }}">@csrf @method('PATCH')
                    <button type="submit">{{ $m->status==='Active' ? 'Deactivate' : 'Activate' }}</button>
                  </form>
                  <form method="POST" action="{{ route('members.destroy', $m) }}" data-confirm
                        data-confirm-title="Delete this member?"
                        data-confirm-message="{{ $m->name }} will be permanently removed from the system."
                        data-confirm-label="Delete Member">@csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state" style="padding:40px 20px"><h3>No members found</h3><p>No records match your filters for this period.</p><button type="button" class="btn btn-accent" data-modal-open="memberModal">+ Add Member</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $members->firstItem() ?? 0 }}â€“{{ $members->lastItem() ?? 0 }} of {{ $members->total() }} records</span>
      <div class="pagination">
        {{ $members->links() }}
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay {{ $editMember ? 'open' : '' }}" id="memberModal">
  <div class="modal-box lg">
    <div class="modal-head">
      <div><h3>{{ $editMember ? 'Edit Member' : 'Add New Member' }}</h3><p>{{ $editMember ? 'Update '.$editMember->name : 'Fill in the sections below to register a member' }}</p></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ $editMember ? route('members.update', $editMember) : route('members.store') }}">
      @csrf
      @if($editMember) @method('PUT') @endif
      <div class="modal-body">
        <div class="tabs-bar">
          <button type="button" class="tab-btn active" data-tab-target="memPane-personal" data-tab-group="memberModal">Personal Information</button>
          <button type="button" class="tab-btn" data-tab-target="memPane-contact" data-tab-group="memberModal">Contact Information</button>
          <button type="button" class="tab-btn" data-tab-target="memPane-church" data-tab-group="memberModal">Church Information</button>
          <button type="button" class="tab-btn" data-tab-target="memPane-emergency" data-tab-group="memberModal">Emergency Contact</button>
        </div>

        @php
            $v = fn($field) => old($field, $editMember?->{$field});
            $err = fn($field) => $errors->has($field) ? '<div style="color:var(--danger);font-size:11px;margin-top:4px">'.$errors->first($field).'</div>' : '';
        @endphp

        <div id="memPane-personal" data-tab-pane="memberModal">
          <div class="form-grid">
            <div class="field"><label>Full Name *</label><input name="name" value="{{ $v('name') }}" placeholder="e.g. James Mushi">{!! $err('name') !!}</div>
            <div class="field"><label>Member Type *</label>
              <select name="member_type" id="memberTypeSelect" onchange="toggleStaffType()">
                @foreach(['student' => 'Student', 'non_student' => 'Non-Student'] as $mtKey => $mtLabel)
                  <option value="{{ $mtKey }}" {{ ($v('member_type') ?: 'non_student')===$mtKey ? 'selected' : '' }}>{{ $mtLabel }}</option>
                @endforeach
              </select>
            </div>
            <div class="field" id="staffTypeField">
              <label>Category (Non-Student) *</label>
              <select name="staff_type">
                <option value="">â€” Select â€”</option>
                @foreach(['staff' => 'Staff', 'non_staff' => 'Non-Staff'] as $stKey => $stLabel)
                  <option value="{{ $stKey }}" {{ $v('staff_type')===$stKey ? 'selected' : '' }}>{{ $stLabel }}</option>
                @endforeach
              </select>
              {!! $err('staff_type') !!}
            </div>
            <div class="field"><label>Gender</label>
              <select name="gender">
                @foreach(['Male','Female'] as $g)<option value="{{ $g }}" {{ $v('gender')===$g ? 'selected' : '' }}>{{ $g }}</option>@endforeach
              </select>
            </div>
            <div class="field"><label>Date of Birth</label><input type="date" name="date_of_birth" value="{{ $v('date_of_birth') }}"></div>
            <div class="field"><label>Marital Status</label>
              <select name="marital_status">
                @foreach(['Single','Married','Widowed'] as $s)<option value="{{ $s }}" {{ $v('marital_status')===$s ? 'selected' : '' }}>{{ $s }}</option>@endforeach
              </select>
            </div>
          </div>
        </div>

        <div id="memPane-contact" data-tab-pane="memberModal" class="hidden">
          <div class="form-grid">
            <div class="field"><label>Phone Number *</label><input name="phone" value="{{ $v('phone') }}" placeholder="+255 7XX XXX XXX">{!! $err('phone') !!}</div>
            <div class="field"><label>Email Address</label><input name="email" value="{{ $v('email') }}" placeholder="email@example.com">{!! $err('email') !!}</div>
            <div class="field full"><label>Residential Address</label><input name="address" value="{{ $v('address') }}" placeholder="Street, Ward, District"></div>
          </div>
        </div>

        <div id="memPane-church" data-tab-pane="memberModal" class="hidden">
          <div class="form-grid">
            <div class="field"><label>Family</label>
              <select name="family_id"><option value="">â€” None â€”</option>
                @foreach($families as $f)<option value="{{ $f->id }}" {{ $v('family_id')==$f->id ? 'selected' : '' }}>{{ $f->name }}</option>@endforeach
              </select>
            </div>
            <div class="field"><label>Status</label>
              <select name="status">
                @foreach(['Active','New','Inactive'] as $s)<option value="{{ $s }}" {{ ($v('status') ?: 'New')===$s ? 'selected' : '' }}>{{ $s }}</option>@endforeach
              </select>
            </div>
            <div class="field"><label>Group</label>
              <select name="group_id"><option value="">â€” None â€”</option>
                @foreach($groups as $g)<option value="{{ $g->id }}" {{ $v('group_id')===($g->id) ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
              </select>
            </div>
            <div class="field"><label>Ministry</label>
              <select name="ministry_id"><option value="">â€” None â€”</option>
                @foreach($ministries as $m2)<option value="{{ $m2->id }}" {{ $v('ministry_id')==$m2->id ? 'selected' : '' }}>{{ $m2->name }}</option>@endforeach
              </select>
            </div>
            <div class="field"><label>Date Joined</label><input type="date" name="joined_on" value="{{ $v('joined_on') }}"></div>
          </div>
        </div>

        <div id="memPane-emergency" data-tab-pane="memberModal" class="hidden">
          <div class="form-grid">
            <div class="field"><label>Contact Name</label><input name="emergency_name" value="{{ $v('emergency_name') }}" placeholder="Emergency contact full name"></div>
            <div class="field"><label>Relationship</label><input name="emergency_relationship" value="{{ $v('emergency_relationship') }}" placeholder="e.g. Spouse, Parent"></div>
            <div class="field full"><label>Phone Number</label><input name="emergency_phone" value="{{ $v('emergency_phone') }}" placeholder="+255 7XX XXX XXX"></div>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <span class="foot-left">Step <span>1</span> of 4</span>
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        @if(! $editMember)
          <button type="submit" name="action" value="again" class="btn btn-secondary">Save &amp; Add Another</button>
        @endif
        <button type="submit" name="action" value="save" class="btn btn-accent">{{ $editMember ? 'Update Member' : 'Save Member' }}</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function toggleStaffType(){
  var sel=document.getElementById('memberTypeSelect');
  var wrap=document.getElementById('staffTypeField');
  if(!sel || !wrap) return;
  wrap.style.display = sel.value === 'non_student' ? '' : 'none';
  if(sel.value === 'student'){ var st=wrap.querySelector('select'); if(st) st.value=''; }
}
document.addEventListener('DOMContentLoaded', toggleStaffType);
function resetMemberType(){
  var sel=document.getElementById('memberTypeSelect');
  if(sel){ sel.value='non_student'; }
  var form=document.getElementById('memberModal');
  form.reset();
  toggleStaffType();
}
@if(request()->boolean('add') || $errors->any())
document.addEventListener('DOMContentLoaded', function(){
  openModalById('memberModal');
});
@endif
</script>
@endpush
@endsection
