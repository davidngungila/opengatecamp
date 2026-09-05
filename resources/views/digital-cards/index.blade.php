@extends('layouts.app')

@section('title', 'Digital Cards — OpenGate Camp Connect')
@section('crumb', 'Giving / Digital Cards')
@section('page_title', 'Digital Cards')

@section('content')
<div class="fade-in">

  <div class="section-head">
    <div><h2>Digital Cards</h2><div class="sub">
      {{ $currentEventName }}
      @if($currentEventDate)<span>· {{ \Carbon\Carbon::parse($currentEventDate)->format('d M Y') }}</span>@endif
      @if($currentEventVenue)<span>· {{ $currentEventVenue }}</span>@endif
    </div></div>
    <div class="section-actions">
      <a class="btn btn-outline" href="{{ route('cards.preview', $card) }}" target="_blank">Preview Card</a>
      <a class="btn btn-outline" href="{{ route('cards.show', $card->hash) }}" target="_blank">Public Page</a>
    </div>
  </div>

  <div class="table-card" style="margin-bottom:18px">
    <div style="padding:18px">
      <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:12px">
        <div style="min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="badge badge-neutral badge-dotted">{{ $card->card_no }}</span>
            <span class="badge badge-{{ $card->getStatusColor() }} badge-dotted">{{ $card->getStatusLabel() }}</span>
          </div>
          <h3 style="font-size:18px;margin:8px 0 4px">{{ $card->title }}</h3>
          @if($card->message)
          <p style="color:var(--text-secondary);font-size:13px;margin:0;max-width:560px;line-height:1.6">{{ $card->message }}</p>
          @endif
          <div style="margin-top:8px;font-size:12.5px"><span style="color:var(--text-tertiary);font-weight:600">Public link:</span> <a href="{{ route('cards.show', $card->hash) }}" target="_blank" class="link-mono" style="color:#2563eb;text-decoration:none">{{ $card->public_url }}</a></div>
        </div>
        <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center">
          <div class="mini-stat"><span>Raised</span><b>TZS {{ number_format($confirmedTotal) }}</b></div>
          <div class="mini-stat"><span>Target</span><b>TZS {{ number_format($targetAmount) }}</b></div>
          <div class="mini-stat"><span>Contributions</span><b>{{ number_format($contributionsCount) }}</b></div>
          <div class="mini-stat"><span>Invited</span><b>{{ number_format($recipients->count()) }}</b></div>
        </div>
      </div>
      @if($targetAmount > 0)
      <div style="margin-top:16px">
        <div style="height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden"><div style="height:100%;width:{{ min(100, $progressPercent) }}%;background:{{ $card->accent_color }};border-radius:999px"></div></div>
        <div style="font-size:11.5px;color:var(--text-tertiary);font-weight:700;margin-top:6px">{{ number_format($progressPercent, 1) }}% of goal reached</div>
      </div>
      @endif
    </div>
  </div>

  @if(!$isCommittee)
  <div class="table-card" style="margin-bottom:18px">
    <div class="table-head">
      <h3>Invite New Person</h3>
      <span class="tf-info">Full Name + Phone — the invitation is sent by SMS</span>
    </div>
    <form method="POST" action="{{ route('cards.sendSms', $card) }}" id="sendSmsForm" style="padding:16px 18px 18px">
      @csrf
      <input type="hidden" name="invitees" id="smsInvitees" value="">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px">
        <div class="text-muted" style="font-size:12px">Each person receives a personalised SMS with their own card link. Invite status changes to <b>Invited</b> once sent.</div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addInviteRow()">+ Add Person</button>
      </div>
      <div id="smsInviteRows"></div>
      <div style="display:flex;justify-content:flex-end;margin-top:12px">
        <button type="submit" class="btn btn-accent">Invite &amp; Send SMS</button>
      </div>
    </form>
  </div>
  @endif

  <div class="table-card">
    <div class="table-head"><h3>Invitations ({{ number_format($recipients->count()) }})</h3></div>
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
                <button type="button" class="btn btn-sm btn-secondary" style="height:26px;padding:0 8px;font-size:11px" data-check-recipient-delivery data-id="{{ $r->id }}" data-mid="{{ $r->message_id }}" title="Check delivery via API">Check</button>
                @endif
              </div>
              @if($r->delivery_checked_at)
              <div style="font-size:10.5px;color:var(--text-tertiary);margin-top:3px">{{ $r->delivery_checked_at->format('d M Y H:i') }}</div>
              @endif
            </td>
            <td><a href="{{ route('cards.show', $r->digitalCard?->hash).'?r='.$r->token }}" target="_blank" class="link-mono">{{ substr($r->token, 0, 12) }}…</a></td>
            <td>{{ $r->sent_at?->format('d M Y H:i') ?: 'Not sent' }}</td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state"><h3>No invitations yet</h3><p>Enter a person's full name and phone number above to send the first invitation.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

<style>
  .section-actions{display:flex;gap:10px;flex-wrap:wrap;}
  .mini-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 14px;}
  .mini-stat span{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:4px;}
  .mini-stat b{font-size:15px;font-weight:800;color:#0f172a;}
  .link-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;}
  .table-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px 0;}
  .invite-row{display:grid;grid-template-columns:1.5fr 1fr auto;gap:8px;margin-bottom:8px;}
  @media (max-width:600px){.invite-row{grid-template-columns:1fr 1fr auto;}}
</style>
@endsection

@push('scripts')
<script>
(function(){
  function inviteRowHtml(){
    return '<div class="invite-row">' +
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
  window.addInviteRow = function(){
    document.getElementById('smsInviteRows').insertAdjacentHTML('beforeend', inviteRowHtml());
  };
  window.removeInviteRow = function(btn){
    btn.closest('.invite-row').remove();
  };

  var sendSmsForm = document.getElementById('sendSmsForm');
  if (sendSmsForm) {
    resetInviteRows();
    sendSmsForm.addEventListener('submit', function(event){
      var invitees = [];
      document.querySelectorAll('#smsInviteRows .invite-row').forEach(function(row){
        var name = row.querySelector('.inv-name').value.trim();
        var phone = row.querySelector('.inv-phone').value.replace(/[^+\d]/g, '');
        if (phone) invitees.push({ name: name, phone: phone });
      });
      if (invitees.length === 0) {
        event.preventDefault();
        toast('Enter at least one person\'s full name and phone number', 'error');
        return;
      }
      document.getElementById('smsInvitees').value = JSON.stringify(invitees);
      var btn = sendSmsForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Sending...';
    });
  }

  document.addEventListener('click', function(e){
    var btn = e.target.closest('[data-check-recipient-delivery]');
    if (!btn) return;
    checkRecipientDelivery(btn.dataset.id, btn.dataset.mid, btn);
  });

  function checkRecipientDelivery(id, mid, btn){
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
  }
})();
</script>
@endpush