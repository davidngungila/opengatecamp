@extends('layouts.app')
@section('title', 'Message History — OpenGate Camp Connect')
@section('crumb', 'Communication / Messaging / History')
@section('page_title', 'Message History')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Message History</h2>
    <span class="badge badge-neutral">{{ $messages->total() }} total</span>
    <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
      <button type="button" class="btn btn-primary" id="bulkCheckBtn" onclick="bulkCheckDelivery()" title="Check delivery status for all SMS messages on this page">
        <span id="bulkCheckLabel">Check All ({{ $checkableCount }})</span>
      </button>
    </div>
  </div>

  @if(session('error'))
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#991b1b;font-size:13.5px">{{ session('error') }}</div>
  @endif
  @if(session('success'))
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#166534;font-size:13.5px">{{ session('success') }}</div>
  @endif

  <div class="glass-card" style="margin-bottom:20px;padding:16px 20px">
    <form method="GET" action="{{ route('messaging.history') }}" class="form-grid filter-grid">
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
            <th>Delivery</th>
            <th>Recipients</th>
            <th>Message</th>
            <th>Sent By</th>
            <th style="width:60px"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($messages as $m)
          <tr class="msg-row" style="cursor:pointer" data-mid="{{ $m->id }}">
            <td style="white-space:nowrap">{{ $m->created_at->format('d M Y, H:i') }}</td>
            <td><span class="badge badge-{{ $m->channel==='sms' ? 'info' : 'purple' }} badge-dotted">{{ strtoupper($m->channel) }}</span></td>
            <td>
              <span class="badge badge-{{ $m->status==='sent' ? 'success' : ($m->status==='failed' ? 'danger' : 'neutral') }} badge-dotted">{{ ucfirst($m->status) }}</span>
              @if($m->status==='failed' && $m->api_response)
              <div style="font-size:10.5px;color:var(--text-tertiary);margin-top:3px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ is_array($m->api_response) ? json_encode($m->api_response) : $m->api_response }}">API: {{ is_array($m->api_response) ? 'error' : Str::limit($m->api_response,40) }}</div>
              @endif
            </td>
            @php
              $hasMsgId = $m->channel==='sms' && (bool) $m->api_message_id;
              $delColor = match ($m->delivery_status) { 'delivered'=>'success', 'undelivered'=>'danger', 'pending'=>'warning', default=>'neutral' };
              $delLabel = $m->delivery_status ? \Illuminate\Support\Str::headline($m->delivery_status) : ($hasMsgId ? 'Unchecked' : '—');
            @endphp
            <td>
              <div style="display:flex;align-items:center;gap:6px;white-space:nowrap">
                <span class="badge badge-dotted badge-{{ $delColor }}" id="del-badge-{{ $m->id }}">{{ $delLabel }}</span>
                @if($hasMsgId)
                <button type="button" class="btn btn-sm btn-secondary" id="del-check-{{ $m->id }}" style="height:26px;padding:0 8px;font-size:11px" data-check-delivery data-id="{{ $m->id }}" title="Check delivery via API">Check</button>
                @endif
              </div>
            </td>
            <td style="font-weight:600;max-width:220px">{{ $m->recipients }}
              @if($m->phone)
              <div style="font-size:11px;color:var(--text-tertiary);font-family:monospace">{{ $m->phone }}</div>
              @endif
            </td>
            <td style="max-width:300px">{{ Str::limit($m->message, 90) }}</td>
            <td style="white-space:nowrap">{{ $m->created_by ?? '—' }}</td>
            <td style="text-align:right;white-space:nowrap">
              <button type="button" class="btn btn-sm btn-primary" style="height:30px;padding:0 12px" onclick="openMsg({{ $m->id }})" title="View full message">View</button>
            </td>
          </tr>
          @empty
          <tr><td colspan="8"><div class="empty-state"><h3>No messages found</h3><p>Try adjusting your filters.</p></div></td></tr>
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

{{-- Message detail drawer --}}
<div class="drawer-overlay" id="msgDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Message Detail</h3><p id="msgMeta" class="cu-sub">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="flex gap-8" style="align-items:center;flex-wrap:wrap" id="msgBadges"></div>
      <div style="margin-top:10px;color:var(--text-secondary);font-size:13px" id="msgSentOn">—</div>
      <div style="margin-top:6px;font-weight:600;font-size:15px" id="msgSubject" class="hidden"></div>

      <div class="info-grid" style="margin-top:18px">
        <div class="info-row"><span>Recipients</span><b id="msgRecipients" style="text-align:right;word-break:break-word">—</b></div>
        <div class="info-row" id="msgPhoneRow" style="display:none"><span>Phone / Target</span><b id="msgPhone" style="font-family:monospace">—</b></div>
      </div>

      <div style="margin-top:20px">
        <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-tertiary)">Message</div>
        <div style="margin-top:8px;background:var(--bg-muted,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:16px 18px;white-space:pre-wrap;word-break:break-word;line-height:1.7;font-size:14px" id="msgBody">—</div>
      </div>

      <details style="margin-top:20px;border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:14px 18px" id="msgApiWrap">
        <summary style="cursor:pointer;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary)">Technical / API Response</summary>
        <div style="margin-top:12px;display:grid;grid-template-columns:auto 1fr;gap:8px 18px;font-size:13px;font-family:monospace">
          <div style="color:var(--text-tertiary)">API Message ID</div><div id="msgApiId">—</div>
          <div style="color:var(--text-tertiary)">Status</div><div id="msgApiStatus">—</div>
          <div style="color:var(--text-tertiary)">Delivery</div><div><span id="msgDeliveryBadge" class="badge badge-dotted badge-neutral">—</span></div>
          <div style="color:var(--text-tertiary);vertical-align:top">Response</div>
          <div id="msgApiResponse" style="background:var(--bg-muted,#f8fafc);border-radius:8px;padding:10px 12px;overflow:auto;max-height:220px;white-space:pre-wrap;word-break:break-word">—</div>
        </div>
      </details>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-primary" id="msgCheckDeliveryBtn" onclick="checkDelivery(window.__msgId)">Check Delivery</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@php
    $msgData = collect($messages->items())->mapWithKeys(function ($m) {
        return [$m->id => [
            'channel'     => $m->channel,
            'status'      => $m->status,
            'recipients'  => $m->recipients,
            'phone'       => $m->phone,
            'subject'     => $m->subject,
            'message'     => $m->message,
            'created_at'  => $m->created_at?->format('d M Y, H:i'),
            'created_by'  => $m->created_by,
            'api_message_id' => $m->api_message_id,
            'api_response'   => is_array($m->api_response) ? json_encode($m->api_response, JSON_PRETTY_PRINT) : $m->api_response,
            'delivery_status' => $m->delivery_status,
            'delivery_checked_at' => $m->delivery_checked_at?->format('d M Y H:i'),
        ],
    ];
})->toArray();
    $msgDataJson = json_encode($msgData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script>
var MSG_DATA = {!! $msgDataJson !!};

function openMsg(id){
  var d = MSG_DATA[id];
  if(!d) return;

  var badges = document.getElementById('msgBadges');
  badges.innerHTML = '';
  function addBadge(cls, text){
    var s = document.createElement('span');
    s.className = 'badge ' + cls + ' badge-dotted';
    s.textContent = text;
    badges.appendChild(s);
  }
  addBadge(d.channel === 'sms' ? 'badge-info' : 'badge-purple', String(d.channel).toUpperCase());
  addBadge(d.status === 'sent' ? 'badge-success' : (d.status === 'failed' ? 'badge-danger' : 'badge-neutral'), d.status.charAt(0).toUpperCase() + d.status.slice(1));
  if(d.api_message_id) addBadge('badge-neutral', 'ID ' + d.api_message_id);

  document.getElementById('msgSentOn').innerHTML = (d.created_at || '—') + (d.created_by ? ' &middot; by ' + d.created_by : '');
  var subj = document.getElementById('msgSubject');
  if(d.subject){ subj.textContent = d.subject; subj.classList.remove('hidden'); } else { subj.classList.add('hidden'); }

  document.getElementById('msgRecipients').textContent = d.recipients || '—';
  var phoneRow = document.getElementById('msgPhoneRow');
  if(d.phone){ phoneRow.style.display = ''; document.getElementById('msgPhone').textContent = d.phone; }
  else { phoneRow.style.display = 'none'; }
  document.getElementById('msgBody').textContent = d.message || '—';

  document.getElementById('msgApiId').textContent = d.api_message_id || '—';
  document.getElementById('msgApiStatus').textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
  document.getElementById('msgApiResponse').textContent = d.api_response || '—';
  document.getElementById('msgApiWrap').style.display = (d.api_message_id || d.api_response) ? '' : 'none';

  var dBadge = document.getElementById('msgDeliveryBadge');
  var delStatus = d.delivery_status || (d.channel === 'sms' && d.api_message_id ? 'unchecked' : 'na');
  var delLabel = delStatus === 'unchecked' ? 'Unchecked' : (delStatus === 'na' ? '—' : delStatus.charAt(0).toUpperCase() + delStatus.slice(1));
  var delColor = delStatus === 'delivered' ? 'success' : (delStatus === 'undelivered' ? 'danger' : (delStatus === 'pending' ? 'warning' : 'neutral'));
  dBadge.className = 'badge badge-dotted badge-' + delColor;
  dBadge.textContent = delLabel;
  window.__msgId = id;
  var chkBtn = document.getElementById('msgCheckDeliveryBtn');
  if (chkBtn) chkBtn.style.display = (d.channel === 'sms' && d.api_message_id) ? '' : 'none';

  document.getElementById('msgMeta').textContent = d.channel === 'sms' ? 'SMS message' : 'Email message';
  openDrawerById('msgDetailDrawer');
}

function checkDelivery(id){
  if(!id) return;
  var btn = document.getElementById('del-check-' + id);
  if(btn){ btn.disabled = true; btn.textContent = 'Checking...'; }
  var drawerBtn = document.getElementById('msgCheckDeliveryBtn');
  if(drawerBtn){ drawerBtn.disabled = true; drawerBtn.textContent = 'Checking...'; }
  fetch("{{ url('/messaging/history') }}/" + id + "/delivery", {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
  }).then(function(r){ return r.json(); }).then(function(d){
    var label = d.label || d.status || 'Unknown';
    var color = d.color || 'neutral';
    var badge = document.getElementById('del-badge-' + id);
    if(badge){ badge.className = 'badge badge-dotted badge-' + color; badge.textContent = label; }
    var dBadge = document.getElementById('msgDeliveryBadge');
    if(dBadge){ dBadge.className = 'badge badge-dotted badge-' + color; dBadge.textContent = label; }
    if(window.MSG_DATA && MSG_DATA[id]){ MSG_DATA[id].delivery_status = d.status || 'unknown'; }
    if(btn){ btn.disabled = false; btn.textContent = 'Check'; }
    if(drawerBtn){ drawerBtn.disabled = false; drawerBtn.textContent = 'Check Delivery'; }
    toast(label + (d.checked_at ? ' · checked ' + d.checked_at : ''), d.status === 'undelivered' ? 'warning' : 'success');
  }).catch(function(){
    if(btn){ btn.disabled = false; btn.textContent = 'Check'; }
    if(drawerBtn){ drawerBtn.disabled = false; drawerBtn.textContent = 'Check Delivery'; }
    toast('Delivery check failed', 'error');
  });
}

function bulkCheckDelivery(){
  var ids = [];
  for(var id in MSG_DATA){
    if(MSG_DATA[id].channel === 'sms' && MSG_DATA[id].api_message_id){ ids.push(id); }
  }
  if(ids.length === 0){ toast('No SMS messages with message IDs on this page', 'warning'); return; }

  var btn = document.getElementById('bulkCheckBtn');
  var lbl = document.getElementById('bulkCheckLabel');
  btn.disabled = true; lbl.textContent = 'Checking ' + ids.length + '...';
  ids.forEach(function(id){
    var b = document.getElementById('del-badge-' + id);
    if(b){ b.textContent = '…'; }
  });

  fetch("{{ route('messaging.delivery.bulk') }}", {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ ids: ids })
  }).then(function(r){ return r.json(); }).then(function(d){
    if(d.results){
      for(var id in d.results){
        var res = d.results[id];
        var badge = document.getElementById('del-badge-' + id);
        if(badge){ badge.className = 'badge badge-dotted badge-' + res.color; badge.textContent = res.label; }
        var chk = document.getElementById('del-check-' + id);
        if(chk){ chk.disabled = false; chk.textContent = 'Check'; }
        if(MSG_DATA[id]){ MSG_DATA[id].delivery_status = res.status; }
      }
    }
    var s = d.summary || {};
    toast('Checked ' + (s.checked || ids.length) + ': ' + (s.delivered||0) + ' delivered, ' + (s.undelivered||0) + ' not delivered, ' + (s.pending||0) + ' pending, ' + (s.unknown||0) + ' unknown' + ((s.failed||0) ? ', ' + s.failed + ' failed' : ''), 'success');
    btn.disabled = false; lbl.textContent = 'Check All (' + ids.length + ')';
  }).catch(function(){
    toast('Bulk delivery check failed', 'error');
    ids.forEach(function(id){
      var b = document.getElementById('del-badge-' + id);
      if(b && b.textContent === '…'){ b.textContent = 'Unchecked'; }
      var chk = document.getElementById('del-check-' + id);
      if(chk){ chk.disabled = false; chk.textContent = 'Check'; }
    });
    btn.disabled = false;
    lbl.textContent = 'Check All (' + ids.length + ')';
  });
}

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-check-delivery]').forEach(function(btnEl){
    btnEl.addEventListener('click', function(){ checkDelivery(btnEl.dataset.id); });
  });
  document.querySelectorAll('.msg-row').forEach(function(row){
    row.addEventListener('click', function(e){
      if(e.target.closest('a') || e.target.closest('button') || e.target.closest('form')) return;
      openMsg(row.dataset.mid);
    });
  });
});
</script>
@endpush
