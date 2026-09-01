@extends('layouts.app')
@section('title', 'Document Categories â€” Open Gate Camp Mission')
@section('crumb', 'Management / Document Categories')
@section('page_title', 'Document Categories')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Document Categories</h2><div class="sub">{{ $totalCats }} categories</div></div>
    <button type="button" class="btn btn-accent" data-modal-open="catModal">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Category
    </button>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Category</th>
            <th>Slug</th>
            <th>Description</th>
            <th>Documents</th>
            <th style="width:80px">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $cat)
          <tr>
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:{{ $cat->color }}20;color:{{ $cat->color }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                </div>
                <div class="cu-name" style="font-weight:700">{{ $cat->name }}</div>
              </div>
            </td>
            <td><code style="font-size:12px;background:rgba(15,23,42,.04);padding:2px 8px;border-radius:4px">{{ $cat->slug }}</code></td>
            <td style="max-width:300px;color:var(--text-secondary);font-size:13px">{{ $cat->description ?? 'â€”' }}</td>
            <td>
              <span class="badge badge-info badge-dotted" style="font-size:10px">{{ $cat->documents_count }} {{ Str::plural('file', $cat->documents_count) }}</span>
            </td>
            <td>
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-cat-{{ $cat->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-cat-{{ $cat->id }}">
                  <button type="button" onclick="openEditCat({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ addslashes($cat->description ?? '') }}', '{{ $cat->color }}')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                  </button>
                  @if($cat->documents_count === 0)
                  <form method="POST" action="{{ route('documents.categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="action-menu-item danger">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                      Delete
                    </button>
                  </form>
                  @else
                  <button type="button" disabled style="opacity:.4;cursor:not-allowed">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                    Has files
                  </button>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="5">
            <div class="empty-state" style="padding:40px">
              <div class="es-ico"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg></div>
              <h3>No Categories Found</h3>
              <p>Create your first document category to get started.</p>
            </div>
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($categories->hasPages())
    <div class="table-footer">
      <span class="tf-info">Showing {{ $categories->firstItem() }}â€“{{ $categories->lastItem() }} of {{ $categories->total() }}</span>
      <div class="pagination">{{ $categories->links() }}</div>
    </div>
    @endif
  </div>
</div>

<div class="modal-overlay" id="catModal">
  <div class="modal-box sm">
    <div class="modal-head">
      <div><h3 id="catModalTitle">Add Category</h3></div>
      <button type="button" class="modal-close" data-modal-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form id="catForm" method="POST" action="{{ route('documents.categories.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-grid">
          <div class="field full">
            <label>Name</label>
            <input type="text" name="name" id="catName" required placeholder="Category name" maxlength="100">
          </div>
          <div class="field full">
            <label>Description</label>
            <input type="text" name="description" id="catDesc" placeholder="Brief description (optional)" maxlength="500">
          </div>
          <div class="field full">
            <label>Color</label>
            <input type="color" name="color" id="catColor" value="#2563EB" style="width:60px;height:36px;padding:2px;border:1px solid var(--border);border-radius:6px;cursor:pointer">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="catSubmitBtn">Create</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openEditCat(id, name, desc, color) {
  document.getElementById('catModalTitle').textContent = 'Edit Category';
  document.getElementById('catSubmitBtn').textContent = 'Update';
  document.getElementById('catName').value = name;
  document.getElementById('catDesc').value = desc;
  document.getElementById('catColor').value = color;
  var form = document.getElementById('catForm');
  form.action = '/documents/categories/' + id;
  var methodInput = form.querySelector('input[name="_method"]');
  if (!methodInput) {
    methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    form.appendChild(methodInput);
  }
  methodInput.value = 'PUT';
  openModalById('catModal');
}
document.querySelector('[data-modal-open="catModal"]').addEventListener('click', function() {
  document.getElementById('catModalTitle').textContent = 'Add Category';
  document.getElementById('catSubmitBtn').textContent = 'Create';
  document.getElementById('catName').value = '';
  document.getElementById('catDesc').value = '';
  document.getElementById('catColor').value = '#2563EB';
  var form = document.getElementById('catForm');
  form.action = '{{ route("documents.categories.store") }}';
  var methodInput = form.querySelector('input[name="_method"]');
  if (methodInput) methodInput.value = 'POST';
});
</script>
@endpush
@endsection
