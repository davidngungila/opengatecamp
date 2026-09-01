@extends('layouts.app')
@section('title', $document->title . ' — Preview')
@section('crumb', 'Management / Documents / Preview')
@section('page_title', 'Document Preview')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div>
      <h2 style="font-size:16px">{{ $document->title }}</h2>
      <div class="sub">{{ $document->file_name }} &middot; {{ $document->file_size_formatted }} &middot; {{ $document->category?->name ?? '—' }}</div>
    </div>
    <div style="display:flex;gap:8px">
      <a href="{{ route('documents.download', rtrim(strtr(\Illuminate\Support\Facades\Crypt::encryptString($document->id), '+/', '-_'), '=')) }}" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download
      </a>
      <a href="{{ route('documents.index') }}" class="btn btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Documents
      </a>
    </div>
  </div>

  <div class="glass-card">
    @if($isImage)
    <div style="text-align:center;padding:20px">
      <img src="{{ $previewUrl }}" alt="{{ $document->title }}" style="max-width:100%;max-height:70vh;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.1)">
    </div>
    @elseif($isPdf)
    <iframe src="{{ $previewUrl }}" style="width:100%;height:70vh;border:none;border-radius:8px"></iframe>
    @elseif($isText)
    <div style="padding:20px">
      <pre style="background:var(--navy-900);color:#e2e8f0;padding:16px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.6;white-space:pre-wrap">{{ $content }}</pre>
    </div>
    @else
    <div style="text-align:center;padding:60px 20px">
      <div style="width:72px;height:72px;border-radius:20px;background:{{ $document->getFileIconBg() }};color:{{ $document->getFileIconColor() }};display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
      </div>
      <h2 style="margin:0 0 8px">Preview Not Available</h2>
      <p style="color:var(--text-secondary);font-size:13.5px;margin:0 0 20px">
        This file type ({{ strtoupper(pathinfo($document->file_name, PATHINFO_EXTENSION)) }}) cannot be previewed inline.<br>
        Please download the file to view it.
      </p>
      <a href="{{ route('documents.download', rtrim(strtr(\Illuminate\Support\Facades\Crypt::encryptString($document->id), '+/', '-_'), '=')) }}" class="btn btn-accent">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download File
      </a>
    </div>
    @endif
  </div>

  @if($document->description)
  <div class="glass-card" style="margin-top:18px">
    <h2 style="font-size:14.5px;margin:0 0 10px">Description</h2>
    <p style="color:var(--text-secondary);font-size:13.5px;margin:0">{{ $document->description }}</p>
  </div>
  @endif

  <div class="glass-card" style="margin-top:18px">
    <h2 style="font-size:14.5px;margin:0 0 14px">Document Details</h2>
    <div class="info-row"><span>Uploaded By</span><b>{{ $document->uploaded_by }}</b></div>
    <div class="info-row"><span>Upload Date</span><b>{{ $document->created_at->format('d M Y H:i') }}</b></div>
    <div class="info-row"><span>Category</span><b>{{ $document->category?->name ?? '—' }}</b></div>
    <div class="info-row"><span>Access Level</span><b>
      <span class="badge badge-{{ $document->access_level==='admin_only' ? 'danger' : ($document->access_level==='restricted' ? 'warning' : 'success') }} badge-dotted" style="font-size:10px">
        {{ str_replace('_',' ',ucfirst($document->access_level)) }}
      </span>
    </b></div>
    <div class="info-row"><span>File Type</span><b>{{ strtoupper(pathinfo($document->file_name, PATHINFO_EXTENSION)) }}</b></div>
    <div class="info-row"><span>File Size</span><b>{{ $document->file_size_formatted }}</b></div>
  </div>
</div>
@endsection
