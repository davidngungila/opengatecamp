@extends('layouts.app')

@section('title', $card->card_no.' — Digital Cards')
@section('crumb', 'Giving / Digital Cards')
@section('page_title', $card->card_no)

@php
    $types = \App\Models\DigitalCard::types();
    $statuses = \App\Models\DigitalCard::statuses();
    $methodLabels = \App\Models\DigitalCardContribution::methods();
    $contributionStatuses = \App\Models\DigitalCardContribution::statuses();
@endphp

@section('content')
<div class="fade-in">

  <div class="section-head">
    <div>
      <a href="{{ route('cards.index') }}" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        All Cards
      </a>
      <h2>{{ $card->card_no }}
        @if($card->title)<span style="font-weight:600;color:#64748b">· {{ $card->title }}</span>@endif
      </h2>
      <div class="sub">
        <span class="badge badge-dotted" style="background:{{ $card->background_color }};color:#fff;border:1px solid {{ $card->accent_color }}">{{ $types[$card->card_type] ?? $card->card_type }}</span>
        <span class="badge badge-{{ $card->getStatusColor() }} badge-dotted">{{ $statuses[$card->status] ?? $card->status }}</span>
        <span class="text-muted">Issued {{ $card->created_at?->format('d M Y, H:i') }}@if($card->created_by) · by {{ $card->created_by }}@endif</span>
      </div>
    </div>
    <div class="section-actions">
      <a class="btn btn-outline" href="{{ route('cards.preview', $card) }}" target="_blank">Preview</a>
      <a class="btn btn-outline" href="{{ route('cards.show', $card->hash) }}" target="_blank">Public Page</a>
      @if(!$isCommittee)
      <button type="button" class="btn btn-accent" data-send-sms data-id="{{ $card->id }}" data-title="{{ $card->title }}" data-url="{{ $card->public_url }}">Invite via SMS</button>
      @endif
      <a class="btn btn-outline" href="{{ route('cards.pdf', $card) }}">Pakua PDF</a>
    </div>
  </div>

  <div class="kpi-grid" style="margin-bottom:18px">
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span></div><div class="kpi-value">TZS {{ number_format($confirmedTotal) }}</div><div class="kpi-label">Confirmed Collections</div></div>
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--purple-bg);color:var(--purple)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span></div><div class="kpi-value">TZS {{ number_format($card->target_amount) }}</div><div class="kpi-label">Target</div></div>
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span></div><div class="kpi-value">{{ number_format($contributions->count()) }}</div><div class="kpi-label">Contributions</div></div>
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--warning-bg);color:var(--warning)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></span></div><div class="kpi-value">{{ number_format($recipients->count()) }}</div><div class="kpi-label">Recipients (SMS)</div></div>
  </div>

  @if($card->target_amount > 0)
  <div class="table-card" style="padding:18px;margin-bottom:18px">
    <div class="details-row">
      <div style="flex:1;min-width:0">
        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px">
          <span style="font-weight:700;font-size:14px">Campaign Progress</span>
          <span class="text-muted" style="font-size:12px">TZS {{ number_format($confirmedTotal) }} raised of TZS {{ number_format($card->target_amount) }} · {{ number_format($card->progress_percent, 1) }}%</span>
        </div>
        <div class="drawer-progress" style="margin-top:10px">
          <div class="dp-track"><div class="dp-fill" style="width:{{ $card->progress_percent }}%;background:{{ $card->accent_color }}"></div></div>
        </div>
      </div>
    </div>
  </div>
  @endif

  <div class="details-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;margin-bottom:18px">
    <div class="table-card" style="padding:0;overflow:hidden">
      <div style="padding:{{ $card->background_color ? '24px' : '24px' }};background:{{ $card->background_color ?: '#1a237e' }};text-align:center;position:relative;border-radius:0">
        <span class="badge badge-dotted" style="color:#fff;border-color:{{ $card->accent_color }};background:rgba(255,255,255,.08)">{{ $types[$card->card_type] ?? $card->card_type }}</span>
        @if($card->title)
        <div style="color:#fff;font-weight:800;font-size:22px;margin-top:10px;line-height:1.25">{{ $card->title }}</div>
        <div style="width:44px;height:2px;background:{{ $card->accent_color }};margin:10px auto"></div>
        @endif
        @if($card->message)
        <div style="color:rgba(255,255,255,.92);font-size:13px;line-height:1.7;margin-top:6px;white-space:pre-line">{{ $card->message }}</div>
        @endif
      </div>
      <div class="meta-list">
        <div class="meta-row"><span class="meta-k">Public URL</span><span class="meta-v"><a href="{{ route('cards.show', $card->hash) }}" target="_blank">{{ $card->public_url }}</a></span></div>
        <div class="meta-row"><span class="meta-k">Accent Color</span><span class="meta-v"><span class="swatch" style="background:{{ $card->accent_color }}"></span>{{ $card->accent_color }}</span></div>
        <div class="meta-row"><span class="meta-k">Background</span><span class="meta-v"><span class="swatch" style="background:{{ $card->background_color }}"></span>{{ $card->background_color }}</span></div>
        <div class="meta-row"><span class="meta-k">Status</span><span class="meta-v badge badge-{{ $card->getStatusColor() }} badge-dotted">{{ $statuses[$card->status] ?? $card->status }}</span></div>
        <div class="meta-row"><span class="meta-k">Created By</span><span class="meta-v">{{ $card->created_by ?: '—' }}</span></div>
      </div>
    </div>

    <div class="table-card" style="padding:18px">
      <h3 style="margin-bottom:14px;font-size:15px">Event &amp; Messaging</h3>
      <div class="meta-list">
        <div class="meta-row"><span class="meta-k">Event</span><span class="meta-v">{{ \App\Models\Setting::get('event.name', 'Open Gate Camp') }}</span></div>
        <div class="meta-row"><span class="meta-k">Event Date</span><span class="meta-v">{{ \App\Models\Setting::get('event.start_date') ? \Carbon\Carbon::parse(\App\Models\Setting::get('event.start_date'))->format('d M Y') : '—' }}</span></div>
        <div class="meta-row"><span class="meta-k">Venue</span><span class="meta-v">{{ \App\Models\Setting::get('event.venue') ?: '—' }}</span></div>
        <div class="meta-row"><span class="meta-k">CTA Text</span><span class="meta-v">{{ $card->cta_text ?: '—' }}</span></div>
        <div class="meta-row"><span class="meta-k">Contributor Note</span><span class="meta-v">{{ $card->contributor_note ?: '—' }}</span></div>
      </div>
      @if($card->sms_text)
      <div style="margin-top:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:6px">SMS Template</div>
        <div style="font-size:12.5px;color:#334155;line-height:1.6;white-space:pre-wrap">{{ $card->sms_text }}</div>
      </div>
      @endif
    </div>
  </div>

  <div class="table-card" style="margin-bottom:18px">
    <div class="table-head"><h3>Contributions ({{ number_format($contributions->count()) }})</h3>
      <span class="tf-info">Confirmed: TZS {{ number_format($confirmedTotal) }}</span>
    </div>
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Contributor</th><th>Phone</th><th>Method</th><th>Reference</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          @forelse($contributions as $c)
          <tr>
            <td>{{ $c->created_at?->format('d M Y H:i') }}</td>
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:var(--blue-light);color:var(--blue-accent)">{{ mb_substr($c->contributor_name ?: '?', 0, 1) }}</div>
                <div><div class="cu-name">{{ $c->contributor_name ?: '—' }}</div>@if($c->contributor_email)<div class="cu-sub">{{ $c->contributor_email }}</div>@endif</div>
              </div>
            </td>
            <td>{{ $c->contributor_phone ?: '—' }}</td>
            <td><span class="badge badge-neutral badge-dotted">{{ $methodLabels[$c->method] ?? $c->method }}</span></td>
            <td>{{ $c->reference_no ?: '—' }}</td>
            <td><b>TZS {{ number_format($c->amount) }}</b></td>
            <td><span class="badge badge-{{ $c->getStatusColor() }} badge-dotted">{{ $contributionStatuses[$c->status] ?? $c->status }}</span></td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state"><h3>No contributions yet</h3><p>Contributions made via the public card page will appear here.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="table-card">
    <div class="table-head"><h3>SMS Recipients ({{ number_format($recipients->count()) }})</h3></div>
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Phone</th><th>Invite Status</th><th>Delivery</th><th>Personalised Link</th><th>Sent At</th></tr></thead>
        <tbody>
          @forelse($recipients as $r)
          <tr>
            <td><b>{{ $r->name ?: '—' }}</b></td>
            <td style="font-family:monospace">{{ $r->phone }}</td>
            <td><span class="badge badge-{{ $r->getInviteStatusColor() }} badge-dotted">{{ $r->status ? ucfirst($r->status) : 'Pending' }}</span></td>
            <td>
              <div style="display:flex;align-items:center;gap:6px;white-space:nowrap">
                <span class="badge badge-{{ $r->getDeliveryStatusColor() }} badge-dotted" id="rdel-badge-{{ $r->id }}">{{ $r->delivery_status ? ucfirst(str_replace('_',' ',$r->delivery_status)) : ($r->message_id ? 'Unchecked' : '—') }}</span>
                @if($r->message_id)
                <button type="button" class="btn btn-sm btn-secondary" id="rdel-check-{{ $r->id }}" style="height:26px;padding:0 8px;font-size:11px" data-check-recipient-delivery data-id="{{ $r->id }}" data-mid="{{ $r->message_id }}" title="Check delivery via API">Check</button>
                @endif
              </div>
              @if($r->delivery_checked_at)
              <div style="font-size:10.5px;color:var(--text-tertiary);margin-top:3px">{{ $r->delivery_checked_at->format('d M Y H:i') }}</div>
              @endif
            </td>
            <td><a href="{{ route('cards.show', $card->hash).'?r='.$r->token }}" target="_blank" class="link-mono">{{ substr($r->token, 0, 12) }}…</a></td>
            <td>{{ $r->sent_at?->format('d M Y H:i') ?: 'Not sent' }}</td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state"><h3>No SMS recipients</h3><p>Send the card via SMS from the list to create personalized recipient links.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@if(!$isCommittee)
<div class="drawer-overlay" id="cardSmsDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Invite People</h3><p id="smsCardTitle">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="sendSmsForm">
      @csrf
      <input type="hidden" name="invitees" id="smsInvitees" value="">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Card Link</label><input type="text" id="smsCardUrl" readonly style="background:var(--blue-light);color:var(--blue-accent);font-weight:700"></div>
          <div class="field full">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px">
              <label style="margin:0">Invitees — Full Name &amp; Phone</label>
              <button type="button" class="btn btn-secondary btn-sm" onclick="addInviteRow()">+ Add Person</button>
            </div>
            <div id="smsInviteRows"></div>
            <div class="text-muted" style="font-size:11px;margin-top:6px">Each person receives a personalised SMS with their own card link. Their invite status changes to <b>Invited</b> once the SMS is sent.</div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Invite &amp; Send SMS</button>
      </div>
    </form>
  </div>
</div>
@endif

</div>

@push('scripts')
<script>
(function(){
  document.querySelectorAll('[data-check-recipient-delivery]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = btn.dataset.id;
      btn.disabled = true;
      btn.textContent = '…';
      fetch("{{ url('/digital-cards/recipients') }}/" + id + "/delivery", {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
      })
      .then(function(r){ return r.json(); })
      .then(function(j){
        btn.textContent = 'Check';
        btn.disabled = false;
        var badge = document.getElementById('rdel-badge-' + id);
        if (badge && j.label) {
          badge.textContent = j.label;
          badge.className = 'badge badge-' + (j.color || 'neutral') + ' badge-dotted';
        }
        toast(j.label || 'Status updated', j.color === 'success' ? 'success' : (j.color === 'danger' ? 'error' : 'info'));
      })
      .catch(function(){
        btn.textContent = 'Check';
        btn.disabled = false;
        toast('Could not check delivery status', 'error');
      });
    });
  });

  document.querySelectorAll('[data-send-sms]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('smsCardTitle').textContent = d.title || '—';
      document.getElementById('smsCardUrl').value = d.url || '';
      resetInviteRows();
      document.getElementById('sendSmsForm').action = "{{ url('/digital-cards') }}/" + d.id + "/send-sms";
      openDrawerById('cardSmsDrawer');
    });
  });

  function inviteRowHtml(){
    return '<div class="invite-row" style="display:grid;grid-template-columns:1.5fr 1fr auto;gap:8px;margin-bottom:8px">' +
      '<input class="inv-name" placeholder="Full Name">' +
      '<input class="inv-phone" placeholder="+255 7XX XXX XXX">' +
      '<button type="button" class="btn btn-sm" onclick="removeInviteRow(this)" style="height:38px;padding:0 10px;background:transparent;color:var(--danger)" title="Remove person">&times;</button>' +
      '</div>';
  }

  function resetInviteRows(){
    var box = document.getElementById('smsInviteRows');
    box.innerHTML = '';
    box.insertAdjacentHTML('beforeend', inviteRowHtml());
  }

  function addInviteRow(){
    document.getElementById('smsInviteRows').insertAdjacentHTML('beforeend', inviteRowHtml());
  }

  function removeInviteRow(btn){
    btn.closest('.invite-row').remove();
  }

  var sendSmsForm = document.getElementById('sendSmsForm');
  if (sendSmsForm) {
    sendSmsForm.addEventListener('submit', function(event){
      var invitees = [];
      document.querySelectorAll('#smsInviteRows .invite-row').forEach(function(row){
        var name = row.querySelector('.inv-name').value.trim();
        var phone = row.querySelector('.inv-phone').value.replace(/[^+\d]/g, '');
        if (phone) invitees.push({ name: name, phone: phone });
      });
      if (invitees.length === 0) {
        event.preventDefault();
        toast('Add at least one person with a phone number', 'error');
        return;
      }
      document.getElementById('smsInvitees').value = JSON.stringify(invitees);
      var btn = sendSmsForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Sending...';
    });
  }
})();
</script>
@endpush

<style>
  .back-link{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:6px;}
  .back-link:hover{color:#0f172a;}
  .section-actions{display:flex;gap:10px;flex-wrap:wrap;}
  .meta-list{display:flex;flex-direction:column;}
  .meta-row{display:flex;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px;align-items:center;}
  .meta-row:last-child{border-bottom:none;}
  .meta-k{color:#94a3b8;font-weight:600;}
  .meta-v{color:#0f172a;font-weight:600;text-align:right;word-break:break-all;}
  .meta-v a{color:#2563eb;text-decoration:none;}
  .swatch{display:inline-block;width:14px;height:14px;border-radius:4px;border:1px solid rgba(0,0,0,.12);vertical-align:-2px;margin-right:8px;}
  .table-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px 0;}
  .link-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;}
</style>
@endsection