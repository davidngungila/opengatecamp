@extends('layouts.app')

@section('title', 'University Fellowships — Settings — OpenGate Camp Connect')
@section('crumb', 'System / Settings / University Fellowships')
@section('page_title', 'University Fellowships')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">University Fellowships</p></div>

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

  <div class="solid-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px">
      <div>
        <h2 style="font-size:14.5px;margin:0 0 4px">University Fellowships</h2>
        <p style="font-size:12.5px;color:var(--text-tertiary);margin:0">These fellowships are available as a selectable list at event registration. Click a row to view or edit.</p>
      </div>
      <button type="button" class="btn btn-accent" data-fellowship-open-btn data-mode="add">Add Fellowship</button>
    </div>

    <div class="table-card" style="box-shadow:none;border:1px solid var(--border,#e5e7eb)">
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th style="width:50px">#</th><th>Fellowship</th><th style="text-align:right">Actions</th></tr></thead>
          <tbody>
            @forelse($fellowships as $index => $f)
            <tr style="cursor:pointer" data-fellowship-open-row data-mode="edit" data-index="{{ $index }}" data-name="{{ $f }}">
              <td style="color:var(--text-tertiary);font-size:13px">{{ $index + 1 }}</td>
              <td><span class="cu-name">{{ $f }}</span></td>
              <td style="text-align:right">
                <div class="flex gap-8" style="align-items:center;justify-content:flex-end;gap:8px">
                  <button type="button" class="btn btn-ghost btn-sm" data-fellowship-open-btn data-mode="edit" data-index="{{ $index }}" data-name="{{ $f }}">View / Edit</button>
                  <form method="POST" action="{{ route('settings.fellowships.destroy', $index) }}"
                        data-confirm data-confirm-title="Remove fellowship?" data-confirm-message="This will remove '{{ $f }}' from the registration fellowship list.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="3"><div class="empty-state"><h3>No fellowships yet</h3><p>Click <b>Add Fellowship</b> to add your first university.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="fellowshipDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="fsTitle">Add Fellowship</h3><p class="cu-sub" id="fsSub">University fellowship name</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="fsForm">
      @csrf
      <input type="hidden" name="_method" id="fsMethod" value="">
      <input type="hidden" name="index" id="fsIndex">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full">
            <label>Fellowship Name *</label>
            <input name="name" id="fsName" required placeholder="e.g. MoCU" style="font-size:14px">
            <small style="color:var(--text-muted);margin-top:4px;display:block">This will appear as an option in the registration form's fellowship dropdown.</small>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-ghost" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="fsSubmit">Save Fellowship</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function spawnFellowshipFrom(el){
  var isEdit = el.getAttribute('data-mode') === 'edit';
  var form = document.getElementById('fsForm');

  document.getElementById('fsTitle').textContent = isEdit ? 'Edit Fellowship' : 'Add Fellowship';
  document.getElementById('fsSub').textContent = isEdit ? 'University fellowship name' : 'New university fellowship name';
  document.getElementById('fsSubmit').textContent = isEdit ? 'Update Fellowship' : 'Save Fellowship';
  document.getElementById('fsIndex').value = isEdit ? (el.getAttribute('data-index') || '') : '';
  document.getElementById('fsName').value = isEdit ? (el.getAttribute('data-name') || '') : '';

  if(isEdit){
    document.getElementById('fsMethod').value = 'PUT';
    form.action = '{{ route("settings.fellowships.update", "__INDEX__") }}'.replace('__INDEX__', encodeURIComponent(document.getElementById('fsIndex').value));
  } else {
    document.getElementById('fsMethod').value = '';
    form.setAttribute('action', '{{ route("settings.fellowships.store") }}');
  }

  openDrawerById('fellowshipDrawer');
}

document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-fellowship-open-btn]');
  if(btn){ e.preventDefault(); spawnFellowshipFrom(btn); return; }
  if(e.target.closest('button, form, a, input, select, textarea')) return;
  var row = e.target.closest('[data-fellowship-open-row]');
  if(row){ spawnFellowshipFrom(row); }
});
</script>
@endpush
