@extends('layouts.app')
@section('title', 'Message History — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / History')
@section('page_title', 'Message History')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Message History</h2>
    <span class="badge badge-neutral">{{ $messages->total() }} total</span>
  </div>
  @include('messaging._tabs', ['active' => 'history'])

  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">{{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">{{ session('success') }}</div>
  @endif

  <div class="glass-card" style="margin-bottom:20px;padding:16px 20px">
    <form method="GET" action="{{ route('messaging.history') }}" class="form-grid" style="grid-template-columns:1fr 1fr 1fr auto;align-items:end;gap:12px">
      <div class="field"><label>Search</label>
        <input name="q" value="{{ $q }}" placeholder="Search recipients, phone, or message...">
      </div>
      <div class="field"><label>Channel</label>
        <select name="channel">
          <option value="all" {{ $channel==='all' ? 'selected':'' }}>All Channels</option>
          <option value="sms" {{ $channel==='sms' ? 'selected':'' }}>SMS</option>
          <option value="email" {{ $channel==='email' ? 'selected':'' }}>Email</option>
        </select>
      </div>
      <div class="field"><label>Status</label>
        <select name="status">
          <option value="all" {{ $status==='all' ? 'selected':'' }}>All Status</option>
          <option value="sent" {{ $status==='sent' ? 'selected':'' }}>Sent</option>
          <option value="failed" {{ $status==='failed' ? 'selected':'' }}>Failed</option>
          <option value="draft" {{ $status==='draft' ? 'selected':'' }}>Draft</option>
        </select>
      </div>
      <button type="submit" class="btn btn-secondary" style="height:38px">Filter</button>
    </form>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Channel</th>
            <th>Status</th>
            <th>Recipients</th>
            <th>Message</th>
            <th>Sent By</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($messages as $m)
          <tr>
            <td style="white-space:nowrap">{{ $m->created_at->format('d M Y, H:i') }}</td>
            <td><span class="badge badge-{{ $m->channel==='sms' ? 'info' : 'purple' }} badge-dotted">{{ strtoupper($m->channel) }}</span></td>
            <td>
              <span class="badge badge-{{ $m->status==='sent' ? 'success' : ($m->status==='failed' ? 'danger' : 'neutral') }} badge-dotted">{{ ucfirst($m->status) }}</span>
              @if($m->status==='failed' && $m->api_response)
              <div style="font-size:10.5px;color:var(--text-tertiary);margin-top:3px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ is_string($m->api_response) ? $m->api_response : json_encode($m->api_response) }}">API: {{ is_string($m->api_response) ? Str::limit($m->api_response,40) : 'error' }}</div>
              @endif
            </td>
            <td style="font-weight:600;max-width:220px">{{ $m->recipients }}
              @if($m->phone)
              <div style="font-size:11px;color:var(--text-tertiary);font-family:monospace">{{ $m->phone }}</div>
              @endif
            </td>
            <td style="max-width:300px">{{ Str::limit($m->message, 90) }}</td>
            <td style="white-space:nowrap">{{ $m->created_by ?? '—' }}</td>
            <td style="text-align:right;white-space:nowrap">
              <a href="{{ route('messaging.show', $m) }}" class="btn btn-sm btn-primary"
                 style="height:30px;padding:0 12px" title="View full message">View</a>
            </td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state"><h3>No messages found</h3><p>Try adjusting your filters.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $messages->firstItem() ?? 0 }}–{{ $messages->lastItem() ?? 0 }} of {{ $messages->total() }}</span>
      <div class="pagination">{{ $messages->links() }}</div>
    </div>
  </div>
</div>
@endsection