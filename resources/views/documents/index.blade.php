@extends('layouts.app')
@section('title', 'Documents — OpenGate Camp Connect')
@section('crumb', 'Management / Documents')
@section('page_title', 'Document Center')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Document Center</h2><div class="sub">{{ $totalDocs }} documents</div></div>
    @if($canManage)
    <button type="button" class="btn btn-accent" data-drawer-open="documentModal">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Upload Document
    </button>
    @endif
  </div>

  <div class="glass-card" style="margin-bottom:18px">
    <form method="GET" action="{{ route('documents.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      <div class="field" style="flex:1;min-width:180px;margin:0">
        <label>Search</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search documents...">
      </div>
      <div class="field" style="min-width:160px;margin:0">
        <label>Category</label>
        <select name="category_id">
          <option value="">All Categories</option>
          @foreach($categories as $cat)
          <option value="{{ $cat->id }}" {{ request('category_id')==$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field" style="min-width:140px;margin:0">
        <label>Access</label>
        <select name="access">
          <option value="">All Access</option>
          <option value="all_staff" {{ request('access')==='all_staff' ? 'selected' : '' }}>All Staff</option>
          <option value="restricted" {{ request('access')==='restricted' ? 'selected' : '' }}>Restricted</option>
          <option value="admin_only" {{ request('access')==='admin_only' ? 'selected' : '' }}>Admin Only</option>
        </select>
      </div>
      <button type="submit" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Filter
      </button>
      @if(request()->hasAny(['q','category_id','access']))
      <a href="{{ route('documents.index') }}" class="btn btn-ghost">Clear</a>
      @endif
    </form>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Document</th>
            <th>Category</th>
            <th>Size</th>
            <th>Uploaded</th>
            <th>Access</th>
            <th style="width:80px">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($documents as $d)
          @php $eid = rtrim(strtr(Crypt::encryptString($d->id), '+/', '-_'), '='); @endphp
          <tr style="cursor:pointer" data-view-doc="docBody{{ $d->id }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:{{ $d->getFileIconBg() }};color:{{ $d->getFileIconColor() }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                </div>
                <div class="cu-name">{{ $d->title }}</div>
              </div>
            </td>
            <td><span class="badge badge-info badge-dotted" style="font-size:10px;border-color:{{ $d->category?->color ?? '#2563EB' }};color:{{ $d->category?->color ?? '#2563EB' }}">{{ $d->category?->name ?? '—' }}</span></td>
            <td>{{ $d->file_size_formatted }}</td>
            <td>{{ $d->created_at->format('d M Y') }}</td>
            <td>
              <span class="badge badge-{{ $d->access_level==='admin_only' ? 'danger' : ($d->access_level==='restricted' ? 'warning' : 'success') }} badge-dotted" style="font-size:10px">
                {{ str_replace('_',' ',ucfirst($d->access_level)) }}
              </span>
            </td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-docs-{{ $d->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-docs-{{ $d->id }}">
                  <a href="{{ route('documents.preview', $eid) }}" class="action-menu-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Preview
                  </a>
                  <a href="{{ route('documents.download', $eid) }}" class="action-menu-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download
                  </a>
                  @if($canManage)
                  <form method="POST" action="{{ route('documents.destroy', $eid) }}" onsubmit="return confirm('Delete this document?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="action-menu-item danger">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                      Delete
                    </button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6">
            <div class="empty-state" style="padding:40px">
              <div class="es-ico"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></div>
              <h3>No Documents Found</h3>
              <p>Upload your first document to get started.</p>
            </div>
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($documents->hasPages())
    <div class="table-footer">
      <span class="tf-info">Showing {{ $documents->firstItem() }}“{{ $documents->lastItem() }} of {{ $documents->total() }}</span>
      <div class="pagination">{{ $documents->links() }}</div>
    </div>
    @endif
  </div>
</div>

<div class="drawer-overlay" id="documentModal">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Upload Document</h3><p>PDF, DOCX, XLSX, JPG, PNG &mdash; up to 2MB</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" id="docUploadForm">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full">
            <label>Title</label>
            <input type="text" name="title" required placeholder="Document title">
          </div>
          <div class="field full">
            <label>Description</label>
            <input type="text" name="description" placeholder="Brief description (optional)">
          </div>
          <div class="field full">
            <label>Category</label>
            <select name="category_id" required>
              <option value="">Select category</option>
              @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="field full">
            <label>Access Level</label>
            <select name="access_level" required>
              <option value="all_staff">All Staff</option>
              <option value="restricted">Restricted</option>
              <option value="admin_only">Admin Only</option>
            </select>
          </div>
          <div class="field full">
            <label>File</label>
            <input type="file" name="file" id="docFileInput" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.csv">
            <div id="docFileHint" style="font-size:12px;color:var(--text-muted);margin-top:4px">PDF, DOCX, XLSX, JPG, PNG up to 2MB</div>
          </div>
        </div>
        <div id="docUploadProgress" style="display:none;margin-top:8px">
          <div style="display:flex;align-items:center;justify-content:space-between;font-size:12.5px;font-weight:700;margin-bottom:8px">
            <span id="docUploadStatus" style="display:flex;align-items:center;gap:8px;color:var(--text-secondary)">Uploading document…</span>
            <span id="docUploadPct" style="font-variant-numeric:tabular-nums;color:var(--text-primary)">0%</span>
          </div>
          <div style="height:9px;border-radius:999px;background:rgba(15,23,42,.08);overflow:hidden">
            <div id="docUploadBar" style="height:100%;width:0%;background:linear-gradient(90deg,#2563eb,#4f46e5);border-radius:999px;transition:width .12s ease"></div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Upload</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="docDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3 id="docDrawerTitle">Document Details</h3><p id="docDrawerCat" class="badge badge-dotted">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body" id="docDrawerBody"></div>
    <div class="drawer-foot">
      <a id="docDrawerPreview" href="#" class="btn btn-secondary" style="text-decoration:none">Preview</a>
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

@foreach($documents as $d)
@php $deid = rtrim(strtr(Crypt::encryptString($d->id), '+/', '-_'), '='); @endphp
<div style="display:none" id="docBody{{ $d->id }}"
     data-name="{{ $d->title }}"
     data-cat="{{ $d->category?->name ?? 'Uncategorized' }}"
     data-color="{{ $d->category?->color ?? '#2563EB' }}"
     data-preview="{{ route('documents.preview', $deid) }}"
     data-download="{{ route('documents.download', $deid) }}">
  <div class="profile-detail">
    <div class="avatar avatar-lg" style="background:{{ $d->getFileIconBg() }};color:{{ $d->getFileIconColor() }}">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
    </div>
    <div>
      <div style="font-size:16px;font-weight:800">{{ $d->title }}</div>
      <div style="font-size:12.5px;color:var(--text-tertiary);font-weight:600">{{ $d->file_name }}</div>
    </div>
  </div>

  @if($d->description)
  <div style="background:rgba(15,23,42,.03);border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:var(--text-secondary);line-height:1.5">{{ $d->description }}</div>
  @endif

  <div class="info-grid">
    <div class="info-row"><span>Category</span><b>{{ $d->category?->name ?? '—' }}</b></div>
    <div class="info-row"><span>Access Level</span><b><span class="badge badge-{{ $d->access_level==='admin_only' ? 'danger' : ($d->access_level==='restricted' ? 'warning' : 'success') }} badge-dotted" style="font-size:10px">{{ str_replace('_',' ',ucfirst($d->access_level)) }}</span></b></div>
    <div class="info-row"><span>File Type</span><b>{{ strtoupper(pathinfo($d->file_name, PATHINFO_EXTENSION)) }}</b></div>
    <div class="info-row"><span>File Size</span><b>{{ $d->file_size_formatted }}</b></div>
    <div class="info-row"><span>Uploaded By</span><b>{{ $d->uploaded_by }}</b></div>
    <div class="info-row"><span>Upload Date</span><b>{{ $d->created_at->format('d M Y H:i') }}</b></div>
  </div>

  @if($canManage)
  <div style="margin-top:16px">
    <form method="POST" action="{{ route('documents.destroy', $deid) }}" onsubmit="return confirm('Delete this document?')">
      @csrf @method('DELETE')
      <button type="submit" class="btn btn-danger btn-sm">Delete Document</button>
    </form>
  </div>
  @endif
</div>
@endforeach
@endsection

@push('scripts')
<script>
(function(){
  var fence = 2 * 1024 * 1024;
  var input = document.getElementById('docFileInput');
  var hint = document.getElementById('docFileHint');
  if(input && hint){
    input.addEventListener('change', function(){
      if(this.files[0] && this.files[0].size > fence){
        this.setCustomValidity('File must be 2MB or smaller.');
        hint.style.color = '#dc2626';
        hint.textContent = 'Selected file is ' + Math.round(this.files[0].size/1024).toLocaleString() + ' KB — ' +
          'the maximum is 2MB. Please choose a smaller file.';
      } else {
        this.setCustomValidity('');
        hint.style.color = '';
        hint.textContent = 'PDF, DOCX, XLSX, JPG, PNG up to 2MB';
      }
    });
  }

  var form = document.getElementById('docUploadForm');
  if(form){
    form.addEventListener('submit', function(e){
      if(!form.querySelector('[name="file"]').files[0]) return;
      e.preventDefault();
      var bar = document.getElementById('docUploadBar');
      var pct = document.getElementById('docUploadPct');
      var status = document.getElementById('docUploadStatus');
      var prog = document.getElementById('docUploadProgress');
      var submitBtn = form.querySelector('[type="submit"]');
      var cancelBtn = form.querySelector('[data-drawer-close]');

      function setP(c){
        var v = Math.max(0, Math.min(100, c));
        if(bar) bar.style.width = v + '%';
        if(pct) pct.textContent = Math.round(v) + '%';
        return v;
      }
      setP(0);
      prog.style.display = 'block';
      submitBtn.disabled = true;
      submitBtn.textContent = 'Uploading…';
      if(cancelBtn) cancelBtn.disabled = true;

      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.action, true);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

      xhr.upload.onprogress = function(ev){
        if(ev.lengthComputable){
          var c = ev.loaded / ev.total * 100;
          setP(c);
          if(status) status.textContent = 'Uploading document… ' + Math.round(c) + '%';
        }
      };
      xhr.upload.onload = function(){
        setP(100);
        if(status){ status.textContent = 'Upload complete — finalizing…'; status.style.color = ''; document.getElementById('docUploadBar').style.background = 'linear-gradient(90deg,#059669,#10b981)'; }
      };
      xhr.onload = function(){
        if(xhr.status >= 200 && xhr.status < 400){
          if(status){
            status.style.color = '#059669';
            status.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> Upload successful!';
          }
          setTimeout(function(){
            if(toast) toast('Document uploaded successfully', 'success');
            window.location.href = '/documents';
          }, 500);
        } else {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Upload';
          if(cancelBtn) cancelBtn.disabled = false;
          if(status){
            status.style.color = '#dc2626';
            status.textContent = 'Upload failed — please try again.';
          }
          if(toast) toast('Upload failed — please try again.', 'error');
        }
      };
      xhr.onerror = function(){
        submitBtn.disabled = false;
        submitBtn.textContent = 'Upload';
        if(cancelBtn) cancelBtn.disabled = false;
        if(status){
          status.style.color = '#dc2626';
          status.textContent = 'Upload failed — please try again.';
        }
        if(toast) toast('Upload failed — please try again.', 'error');
      };

      xhr.send(new FormData(form));
    });
  }
})();
function openDocDrawer(id){
  var tpl = document.getElementById(id);
  if(!tpl) return;
  document.getElementById('docDrawerTitle').textContent = tpl.dataset.name || 'Document';
  var cat = document.getElementById('docDrawerCat');
  cat.textContent = tpl.dataset.cat || '—';
  cat.style.color = tpl.dataset.color;
  cat.style.borderColor = tpl.dataset.color;
  document.getElementById('docDrawerBody').innerHTML = tpl.innerHTML;
  document.getElementById('docDrawerPreview').href = tpl.dataset.preview || '#';
  openDrawerById('docDetailDrawer');
}
document.addEventListener('click', function(e){
  var el = e.target.closest('[data-view-doc]');
  if(!el) return;
  if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form') || e.target.closest('input')) return;
  openDocDrawer(el.getAttribute('data-view-doc'));
});
</script>
@endpush
