@extends('layouts.app')
@section('title', 'Email — OpenGate Camp Connect')
@section('crumb', 'Communication / Messaging / Email')
@section('page_title', 'Email')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Email Messaging</h2>
  </div>
  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M20 6L9 17l-5-5"/></svg> {{ session('success') }}</div>
  @endif

  <div class="two-col">
    <div class="glass-card">
      <h2 style="font-size:14.5px;margin:0 0 14px">Compose Email</h2>
      <form method="POST" action="{{ route('messaging.store') }}">
        @csrf
        <input type="hidden" name="channel" value="email">
        <input type="hidden" name="recipient_filter" id="recipientFilter" value="all_active">
        <input type="hidden" name="recipient_value" id="recipientValue" value="">
        <div class="form-grid">
          <div class="field full">
            <label>Recipients Label *</label>
            <input name="recipients" required value="{{ old('recipients', 'All Active Members') }}" placeholder="e.g. Youth Group, All Members">
          </div>
          <div class="field full">
            <label>Subject *</label>
            <input name="subject" required value="{{ old('subject') }}" placeholder="Email subject line">
          </div>
          <div class="field full">
            <label>Message *</label>
            <textarea name="message" required placeholder="Type your email body here..." style="min-height:180px">{{ old('message') }}</textarea>
          </div>
        </div>
        <div style="margin-top:14px;display:flex;justify-content:flex-end;gap:8px">
          <button type="submit" name="action" value="draft" class="btn btn-secondary">Save Draft</button>
          <button type="submit" name="action" value="send" class="btn btn-accent">Send Email</button>
        </div>
      </form>
    </div>

    <div class="glass-card">
      <h2 style="font-size:14.5px;margin:0 0 12px">Email History</h2>
      @php $emailMessages = $messages->filter(fn($m) => $m->channel === 'email'); @endphp
      @if($emailMessages->isEmpty())
      <div class="empty-state" style="padding:30px 16px"><p>No emails sent yet.</p></div>
      @endif
      @foreach($emailMessages->take(10) as $h)
      @php $rowClass = $h->status==='sent' ? 'var(--green-light)' : ($h->status==='failed' ? '#fef2f2' : 'var(--blue-light)'); $icoColor = $h->status==='sent' ? 'var(--green-accent)' : ($h->status==='failed' ? '#991b1b' : 'var(--blue-accent)'); @endphp
      <div class="mini-row">
        <div class="m-ico" style="background:{{ $rowClass }};color:{{ $icoColor }}">
          @php echo $h->status==='sent' ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'; @endphp
        </div>
        <div class="m-body">
          <p>{{ $h->recipients }}</p>
          <span>{{ Str::limit($h->subject ?? $h->message, 46) }}</span>
        </div>
        <span class="badge badge-{{ $h->status==='sent' ? 'success' : 'danger' }} badge-dotted">{{ ucfirst($h->status) }}</span>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
