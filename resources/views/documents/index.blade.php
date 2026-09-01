@extends('layouts.app')
@section('title', 'Documents — Open Gate Camp Mission')
@section('crumb', 'Management / Documents')
@section('page_title', 'Document Center')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Document Center</h2><div class="sub">{{ $totalDocs }} documents</div></div>
    <button type="button" class="btn btn-accent" data-modal-open="documentModal">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Upload Document
    </button>
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
          <tr>
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
                  <form method="POST" action="{{ route('documents.destroy', $eid) }}" onsubmit="return confirm('Delete this document?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="action-menu-item danger">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                      Delete
                    </button>
                  </form>
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

<div class="modal-overlay" id="documentModal">
  <div class="modal-box sm">
    <div class="modal-head">
      <div><h3>Upload Document</h3></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-body">
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
            <input type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt,.csv">
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px">PDF, DOCX, XLSX, JPG, PNG up to 20MB</div>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Upload</button>
      </div>
    </form>
  </div>
</div>
@endsection
