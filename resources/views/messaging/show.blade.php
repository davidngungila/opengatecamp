@extends('layouts.app')
@section('title', 'Message Detail — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / History / Detail')
@section('page_title', 'Message Detail')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Message Detail</h2>
    <a href="{{ route('messaging.history') }}" class="btn btn-secondary btn-sm">&larr; Back to History</a>
  </div>
  @include('messaging._tabs', ['active' => 'history'])

  <div class="glass-card" style="margin-top:20px;padding:22px 26px">
    {{-- Header row --}}
    <div class="flex" style="align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;border-bottom:1px solid var(--border,#e5e7eb);padding-bottom:16px">
      <div>
        <div class="flex gap-8" style="align-items:center;flex-wrap:wrap">
          <span class="badge badge-{{ $message->channel==='sms' ? 'info' : 'purple' }} badge-dotted">{{ strtoupper($message->channel) }}</span>
          <span class="badge badge-{{ $message->status==='sent' ? 'success' : ($message->status==='failed' ? 'danger' : 'neutral') }} badge-dotted">{{ ucfirst($message->status) }}</span>
          @if($message->api_message_id)
          <span class="badge badge-neutral" style="font-family:monospace;font-size:11px">ID {{ $message->api_message_id }}</span>
          @endif
        </div>
        <div style="margin-top:8px;color:var(--text-secondary);font-size:13px">
          Sent on {{ $message->created_at->format('d M Y \a\t H:i') }}
          @if($message->created_by) &middot; by {{ $message->created_by }}@endif
        </div>
        @if($message->subject)
        <div style="margin-top:10px;font-weight:600;font-size:15px">{{ $message->subject }}</div>
        @endif
      </div>
    </div>

    {{-- Recipients / phone --}}
    <div style="margin-top:18px">
      <div class="flex gap-8" style="flex-wrap:wrap;gap:10px">
        <div style="flex:1;min-width:260px">
          <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-tertiary)">Recipients</div>
          <div style="margin-top:6px;font-weight:600;font-size:14px;word-break:break-word">{{ $message->recipients }}</div>
        </div>
        @if($message->phone)
        <div style="min-width:180px">
          <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-tertiary)">Phone / Target</div>
          <div style="margin-top:6px;font-family:monospace;font-size:13.5px">{{ $message->phone }}</div>
        </div>
        @endif
      </div>
    </div>

    {{-- Full message body --}}
    <div style="margin-top:22px">
      <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-tertiary)">Message</div>
      <div style="margin-top:8px;background:var(--bg-muted,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:16px 18px;white-space:pre-wrap;word-break:break-word;line-height:1.7;font-size:14px">{{ $message->message }}</div>
    </div>

    {{-- API response / technical info (collapsible) --}}
    @if($message->api_message_id || $message->api_response)
    <details style="margin-top:20px;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:14px 18px">
      <summary style="cursor:pointer;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary)">Technical / API Response</summary>
      <div style="margin-top:12px;display:grid;grid-template-columns:auto 1fr;gap:8px 18px;font-size:13px;font-family:monospace">
        @if($message->api_message_id)
        <div style="color:var(--text-tertiary)">API Message ID</div><div>{{ $message->api_message_id }}</div>
        @endif
        <div style="color:var(--text-tertiary)">Status</div><div>{{ ucfirst($message->status) }}</div>
        @if($message->api_response)
        <div style="color:var(--text-tertiary);vertical-align:top">Response</div>
        <div style="background:var(--bg-muted,#f8fafc);border-radius:8px;padding:10px 12px;overflow:auto;max-height:320px;white-space:pre-wrap;word-break:break-word">{{ is_array($message->api_response) ? json_encode($message->api_response, JSON_PRETTY_PRINT) : $message->api_response }}</div>
        @endif
      </div>
    </details>
    @endif

    <div style="margin-top:24px;border-top:1px solid var(--border,#e5e7eb);padding-top:16px">
      <a href="{{ route('messaging.history') }}" class="btn btn-secondary">Back to History</a>
    </div>
  </div>
</div>
@endsection