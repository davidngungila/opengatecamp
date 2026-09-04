<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verification — {{ $org ?? 'OpenGate Camp Connect' }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
    background: linear-gradient(135deg,#eef2f7,#f8fafc);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }
  .card {
    width: 100%;
    max-width: 480px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(20,40,80,.12);
    overflow: hidden;
  }
  .card-head {
    padding: 22px 24px 18px;
    text-align: center;
    color: #fff;
  }
  .card-head.valid { background: linear-gradient(135deg,#16a34a,#15803d); }
  .card-head.invalid { background: linear-gradient(135deg,#dc2626,#b91c1c); }
  .card-head.neutral { background: linear-gradient(135deg,#0f172a,#1e293b); }
  .badge-icon {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,.2);
    margin: 0 auto 12px;
    display: flex; align-items: center; justify-content: center;
    color:#fff;
  }
  .status-label { font-size: 13px; letter-spacing: 1.5px; text-transform: uppercase; opacity: .9; }
  .status-text { font-size: 22px; font-weight: 800; margin-top: 4px; }
  .card-body { padding: 22px 24px 8px; }
  .row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
  .row .k { color: #64748b; }
  .row .v { font-weight: 600; color: #0f172a; text-align: right; word-break: break-word; }
  .row .v.hl { color: #16a34a; }
  .row .v.warn { color: #dc2626; }
  .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin: 18px 0 4px; }
  .card-foot {
    padding: 18px 24px 24px;
    text-align: center;
    color: #94a3b8;
    font-size: 12px;
  }
  .verified-at { margin-top: 8px; color: #64748b; font-size: 12.5px; }
  .error { padding: 18px 24px; text-align: center; }
  .error p { color: #b91c1c; font-weight: 600; margin-bottom: 8px; }
  .chk { font-size: 12px; color: #16a34a; }
  .xchk { font-size: 12px; color: #dc2626; }
  .org-name { font-size: 13px; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; }
</style>
</head>
<body>
@php
    $org = $org ?? 'OpenGate Camp Connect';
    $valid = !empty($confirmed);
    $statusText = $valid ? 'VERIFIED &amp; CONFIRMED' : ($errors->any() ? 'NOT FOUND' : 'NOT CONFIRMED');
    $headClass = $errors->any() ? 'neutral' : ($valid ? 'valid' : 'invalid');
@endphp

<div class="card">
  <div class="card-head {{ $headClass }}">
    <div class="badge-icon">
      @if($errors->any())
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      @elseif($valid)
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      @else
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M4.9 4.9l14.2 14.2"/></svg>
      @endif
    </div>
    <div class="status-label">{{ $errors->any() ? 'Verification result' : ($type === 'receipt' ? 'Official receipt' : 'Event ticket') }}</div>
    <div class="status-text">{!! $statusText !!}</div>
    <div class="verified-at">#{{ $ref ?? '—' }} &middot; {{ now()->format('d M Y H:i') }}</div>
  </div>

  @if($errors->any())
    <div class="error">
      <p>{{ $errors->first() }}</p>
      <div style="font-size:12.5px;color:#64748b">{{ $org }}</div>
    </div>
  @else
  <div class="card-body">
    <div class="org-name">{{ $org }}</div>

    @if($type === 'receipt')
      <div class="section-title">Receipt Details</div>
      <div class="row"><span class="k">Receipt No</span><span class="v">{{ $entry->entry_no }}</span></div>
      <div class="row"><span class="k">Date</span><span class="v">{{ $entry->entry_date->format('d M Y') }}</span></div>
      <div class="row"><span class="k">Reference</span><span class="v">{{ $entry->reference ?: '—' }}</span></div>
      <div class="row"><span class="k">Paid By</span><span class="v">{{ $payer ?? '—' }}</span></div>
      <div class="row"><span class="k">Amount Paid</span><span class="v hl">TZS {{ number_format($amount, 0) }}</span></div>
      <div class="row"><span class="k">Status</span><span class="v {{ $valid ? 'hl' : 'warn' }}">{{ $entry->status === 'posted' ? 'POSTED' : strtoupper($entry->status) }}</span></div>

      <div class="section-title">Breakdown</div>
      @foreach($entry->lines as $line)
      <div class="row">
        <span class="k">{{ $line->account?->name ?? 'Account' }} <span style="color:#cbd5e1">({{ $line->account?->code ?? '—' }})</span></span>
        <span class="v">TZS {{ number_format($line->debit > 0 ? $line->debit : $line->credit, 0) }}</span>
      </div>
      @endforeach
    @else
      <div class="section-title">Ticket Details</div>
      <div class="row"><span class="k">Attendee</span><span class="v">{{ $attendee->name }}</span></div>
      <div class="row"><span class="k">Ticket No</span><span class="v">{{ $attendee->getTicketNo() }}</span></div>
      <div class="row"><span class="k">Event</span><span class="v">{{ $attendee->event?->title ?? '—' }}</span></div>
      <div class="row"><span class="k">Venue</span><span class="v">{{ $attendee->event?->venue ?? '—' }}</span></div>
      <div class="row"><span class="k">Event Date</span><span class="v">{{ $attendee->event?->start_date?->format('d M Y') ?? '—' }}</span></div>
      <div class="row"><span class="k">Fellowship</span><span class="v">{{ $attendee->fellowship ?: '—' }}</span></div>
      <div class="row"><span class="k">Coming From</span><span class="v">{{ $attendee->getRegionLabel() }}</span></div>
      <div class="row"><span class="k">Phone</span><span class="v">{{ $attendee->phone ?: '—' }}</span></div>
      <div class="row"><span class="k">Amount Paid</span><span class="v {{ $valid ? 'hl' : 'warn' }}">TZS {{ number_format($attendee->amount_paid ?? 0, 0) }}</span></div>
      <div class="row"><span class="k">Ticket Issued</span><span class="v">{{ $attendee->ticket_sent_at?->format('d M Y H:i') ?? '—' }}</span></div>
    @endif
  </div>

  <div class="card-foot">
    <strong>Verification result:</strong>
    <span class="{{ $valid ? 'chk' : 'xchk' }}">{{ $valid ? 'Valid — confirmed information matches our records.' : 'Invalid — this document could not be confirmed.' }}</span>
    <div class="verified-at">OpenGate Camp Connect &middot; Automatic verification system</div>
  </div>
  @endif
</div>
</body>
</html>