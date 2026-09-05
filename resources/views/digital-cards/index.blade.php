@extends('layouts.app')

@section('title', 'Digital Cards — OpenGate Camp Connect')
@section('crumb', 'Giving / Digital Cards')
@section('page_title', 'Digital Cards')

@section('content')
@php
    $v = fn($f) => old($f, $filters[$f] ?? null);
    $deliveryOptions = ['delivered' => 'Delivered', 'undelivered' => 'Not delivered', 'pending' => 'Pending', 'unknown' => 'Unknown'];
@endphp
<div class="fade-in">
  <div class="section-head">
    <div><h2>Digital Cards</h2><div class="sub">
      {{ $totals['invited'] }} invited · {{ $totals['delivered'] }} delivered · {{ $totals['failed'] }} failed · {{ $totals['pending'] }} pending
    </div></div>
    @if(!$isCommittee)
    <button type="button" class="btn btn-accent" data-drawer-open="inviteNewDrawer">Add List</button>
    @endif
  </div>

  <form class="toolbar" method="GET" action="{{ route('cards.index') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ $v('q') }}" placeholder="Search by name or phone..."></div>
    <select class="filter-select" name="status" onchange="this.form.submit()">
      <option value="">All Invite Status</option>
      @foreach($inviteStatuses as $k=>$s)<option value="{{ $k }}" {{ $v('status')===$k ? 'selected' : '' }}>{{ $s }}</option>@endforeach
    </select>
    <select class="filter-select" name="delivery" onchange="this.form.submit()">
      <option value="">All Delivery</option>
      @foreach($deliveryOptions as $k=>$s)<option value="{{ $k }}" {{ $v('delivery')===$k ? 'selected' : '' }}>{{ $s }}</option>@endforeach
    </select>
    <a class="btn btn-secondary btn-sm" href="{{ route('cards.export', array_filter($filters, fn($f) => $f !== '' && $f !== null)) }}">Export</a>
    @if(!$isCommittee && $totals['pending'] > 0)
    <form method="POST" action="{{ route('cards.sendPending', $card) }}">@csrf
      <button type="submit" class="btn btn-accent btn-sm" data-confirm
        data-confirm-title="Send {{ $totals['pending'] }} pending invites?"
        data-confirm-message="SMS will be sent to everyone on the list who has not been invited yet."
        data-confirm-label="Send SMS">Send SMS to {{ $totals['pending'] }} pending</button>
    </form>
    @endif
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Phone</th><th>Invite Status</th><th>Delivery</th><th>Sent At</th><th style="width:60px">Actions</th></tr></thead>
        <tbody id="invitesTbody">
          @forelse($recipients as $r)
          <tr style="cursor:pointer" data-view-invite
            data-id="{{ $r->id }}"
            data-name="{{ $r->name }}"
            data-phone="{{ $r->phone }}"
            data-status="{{ $r->status ?: 'pending' }}"
            data-status-label="{{ $r->status ? ucfirst($r->status) : 'Pending' }}"
            data-status-color="{{ $r->getInviteStatusColor() }}"
            data-delivery-label="{{ $r->delivery_status ? ucfirst(str_replace('_',' ',$r->delivery_status)) : ($r->message_id ? 'Unchecked' : '—') }}"
            data-delivery-color="{{ $r->getDeliveryStatusColor() }}"
            data-mid="{{ $r->message_id }}"
            data-sent-at="{{ $r->sent_at?->format('d M Y H:i') }}"
            data-checked-at="{{ $r->delivery_checked_at?->format('d M Y H:i') }}"
            data-link="{{ $r->short_link }}">
            <td>
              <div class="cell-user">
                <div class="cell-avatar">{{ collect(explode(' ', $r->name ?? '?'))->map(fn($w) => mb_substr($w,0,1))->take(2)->implode('') }}</div>
                <div><div class="cu-name">{{ $r->name ?: '—' }}</div></div>
              </div>
            </td>
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
            <td>{{ $r->sent_at?->format('d M Y H:i') ?: 'Not sent' }}</td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-invite-{{ $r->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-invite-{{ $r->id }}">
                  @if($r->message_id)
                  <button type="button" data-check-recipient-delivery data-id="{{ $r->id }}" data-mid="{{ $r->message_id }}">Check Delivery</button>
                  @endif
                  <form method="POST" action="{{ route('cards.recipient.resend', $r) }}" style="display:contents">@csrf<button type="submit">Send Again (SMS)</button></form>
                  <button type="button" onclick="copyInviteLink('{{ $r->id }}')">Copy Link</button>
                  @if(!$isCommittee)
                  <form method="POST" action="{{ route('cards.recipient.destroy', $r) }}" data-confirm
                        data-confirm-title="Remove this invite?"
                        data-confirm-message="{{ $r->name ?: $r->phone }} will be removed from this campaign's invitations."
                        data-confirm-label="Remove Invite">@csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state" style="padding:40px 20px"><h3>No invitations yet</h3><p>Add a list first, then send SMS invites with each person's short card link.</p>@if(!$isCommittee)<button type="button" class="btn btn-accent" data-drawer-open="inviteNewDrawer">Add List</button>@endif</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $recipients->firstItem() ?? 0 }}–{{ $recipients->lastItem() ?? 0 }} of {{ $recipients->total() }} invites</span>
      <div class="pagination">{{ $recipients->links() }}</div>
    </div>
  </div>
</div>

@if(!$isCommittee)
<div class="drawer-overlay" id="inviteNewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Add List</h3><p>Add people to the pending list. Send the SMS invites afterwards in bulk (below) or from the card details page.</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px">
        <div class="text-muted" style="font-size:12px">Each phone number can only have one card — duplicates will be skipped. SMS is sent later in bulk.</div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addInviteRow()">+ Add Person</button>
      </div>
      <div id="smsInviteRows"></div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
      <button type="submit" class="btn btn-accent" id="addListBtn">Save List</button>
    </div>
  </div>
</div>
@endif

<div class="drawer-overlay" id="inviteDetailDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Invite Details</h3><p>Person invitation record</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="profile-detail">
        <div class="cell-avatar avatar-lg" id="dInviteAvatar">—</div>
        <div>
          <div class="cu-name" id="dInviteName" style="font-size:17px">—</div>
          <span class="badge badge-dotted" id="dInviteStatus">—</span>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-row"><span>Phone</span><b id="dInvitePhone">—</b></div>
        <div class="info-row"><span>Sent At</span><b id="dInviteSent">—</b></div>
        <div class="info-row"><span>Delivery Checked</span><b id="dInviteChecked">—</b></div>
        <div class="info-row"><span>Delivery</span><b style="display:flex;gap:8px;align-items:center;justify-content:flex-end"><span class="badge badge-neutral badge-dotted" id="dDeliveryBadge">—</span>
          <button type="button" class="btn btn-sm btn-secondary" id="dCheckDelivery" style="height:26px;padding:0 8px;font-size:11px">Check</button></b></div>
        <div class="info-row full"><span>Personalised Link</span><b style="white-space:normal;text-align:right"><a id="dInviteLink" href="#" target="_blank">—</a></b></div>
        <div class="info-row full"><span>Message ID</span><b id="dInviteMsg" style="font-family:monospace;font-size:11px;word-break:break-all;text-align:right">—</b></div>
      </div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-accent" id="dCopyLink">Copy Link</button>
    </div>
  </div>
</div>

<style>
  .invite-row{display:grid;grid-template-columns:1.5fr 1fr auto;gap:8px;margin-bottom:8px;}
  @media (max-width:600px){.invite-row{grid-template-columns:1fr 1fr auto;}}
</style>
@endsection

@push('scripts')
<script>
(function(){
  var openInviteId = null;

  function inviteRowHtml(){
    return '<div class="invite-row">' +
      '<input class="inv-name" placeholder="Full Name">' +
      '<input class="inv-phone" placeholder="+255 7XX XXX XXX">' +
      '<button type="button" class="btn btn-sm" onclick="removeInviteRow(this)" style="height:38px;padding:0 10px;background:transparent;color:var(--danger)" title="Remove person">&times;</button>' +
      '</div>';
  }

  function resetInviteRows(){
    var box = document.getElementById('smsInviteRows');
    if (!box) return;
    box.innerHTML = '';
    box.insertAdjacentHTML('beforeend', inviteRowHtml());
  }
  window.addInviteRow = function(){
    var box = document.getElementById('smsInviteRows');
    if (box) box.insertAdjacentHTML('beforeend', inviteRowHtml());
  };
  window.removeInviteRow = function(btn){
    btn.closest('.invite-row').remove();
  };
  window.copyInviteLink = function(id){
    var tr = document.querySelector('[data-view-invite][data-id="' + id + '"]');
    if (tr && tr.dataset.link) {
      navigator.clipboard.writeText(tr.dataset.link).then(function(){
        toast('Invite link copied', 'success');
      }, function(){ toast('Could not copy link', 'error'); });
    }
  };

  document.addEventListener('DOMContentLoaded', function(){
    resetInviteRows();

    function collectInvitees(){
      var invitees = [];
      document.querySelectorAll('#smsInviteRows .invite-row').forEach(function(row){
        var name = row.querySelector('.inv-name').value.trim();
        var phone = row.querySelector('.inv-phone').value.replace(/[^+\d]/g, '');
        if (phone) invitees.push({ name: name, phone: phone });
      });
      return invitees;
    }

    function submitInvites(action, successLabel, busyLabel){
      var invitees = collectInvitees();
      if (invitees.length === 0) {
        toast('Enter at least one person\'s full name and phone number', 'error');
        return;
      }

      var listBtn = document.getElementById('addListBtn');
      listBtn.disabled = true;
      listBtn.textContent = busyLabel;

      var formData = new FormData();
      formData.append('invitees', JSON.stringify(invitees));
      formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

      fetch(action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: formData
      })
      .then(function(r){ return r.json(); })
      .then(function(j){
        listBtn.disabled = false;
        listBtn.textContent = 'Save List';
        resetInviteRows();
        if (j && j.ok) { toast(j.message || successLabel, 'success'); }
        else { toast((j && j.message) || 'Action could not be completed', 'error'); }
        if (j && j.recipients && j.recipients.length) {
          j.recipients.forEach(addInviteRowToTable);
          updateFooterCount(j.recipients.length);
        }
      })
      .catch(function(){
        listBtn.disabled = false;
        listBtn.textContent = 'Save List';
        toast('Could not complete the action. Please try again.', 'error');
      });
    }

    var addListBtn = document.getElementById('addListBtn');
    if (addListBtn) {
      addListBtn.addEventListener('click', function(event){
        event.preventDefault();
        submitInvites('{{ route('cards.addList', $card) }}', 'List saved', 'Saving...');
      });
    }

    function updateFooterCount(added){
      var info = document.querySelector('.table-footer .tf-info');
      if (!info || added < 1) return;
      var m = info.textContent.match(/^Showing (\d+)\u2013(\d+) of (\d+)/);
      if (!m) return;
      var last = parseInt(m[2], 10) + added;
      var total = parseInt(m[3], 10) + added;
      info.textContent = 'Showing ' + (parseInt(m[1], 10) > 0 ? m[1] : 1) + '\u2013' + last + ' of ' + total + ' invites';
    }

    function addInviteRowToTable(r){
      var tbody = document.getElementById('invitesTbody');
      if (!tbody) return;

      var empty = tbody.querySelector('.empty-state');
      if (empty) empty.closest('tr').remove();

      var delivery = '<span class="badge badge-' + (r.delivery_color || 'neutral') + ' badge-dotted" id="rdel-badge-' + r.id + '">' + esc(r.delivery_label || '—') + '</span>';
      if (r.message_id) {
        delivery += '<button type="button" class="btn btn-sm btn-secondary" style="height:26px;padding:0 8px;font-size:11px" data-check-recipient-delivery data-id="' + r.id + '" data-mid="' + r.message_id + '" title="Check delivery via API">Check</button>';
      }
      var checkedAt = r.checked_at ? '<div style="font-size:10.5px;color:var(--text-tertiary);margin-top:3px">' + esc(r.checked_at) + '</div>' : '';
      var token = r.token || '';

      var html =
        '<tr style="cursor:pointer" data-view-invite' +
          ' data-id="' + r.id + '"' +
          ' data-name="' + esc(r.name || '') + '"' +
          ' data-phone="' + esc(r.phone) + '"' +
          ' data-status="' + esc(r.status || 'pending') + '"' +
          ' data-status-label="' + esc(r.status_label || 'Pending') + '"' +
          ' data-status-color="' + esc(r.status_color || 'neutral') + '"' +
          ' data-delivery-label="' + esc(r.delivery_label || '—') + '"' +
          ' data-delivery-color="' + esc(r.delivery_color || 'neutral') + '"' +
          ' data-mid="' + esc(r.message_id || '') + '"' +
          ' data-sent-at="' + esc(r.sent_at || '') + '"' +
          ' data-checked-at="' + esc(r.checked_at || '') + '"' +
          ' data-link="' + esc(r.link) + '">' +
          '<td><div class="cell-user"><div class="cell-avatar">' + esc(initials(r.name)) + '</div><div><div class="cu-name">' + esc(r.name || '—') + '</div></div></div></td>' +
          '<td style="font-family:monospace">' + esc(r.phone) + '</td>' +
          '<td><span class="badge badge-' + esc(r.status_color || 'neutral') + ' badge-dotted">' + esc(r.status_label || 'Pending') + '</span></td>' +
          '<td><div style="display:flex;align-items:center;gap:6px;white-space:nowrap">' + delivery + '</div>' + checkedAt + '</td>' +
          '<td>' + esc(r.sent_at || 'Not sent') + '</td>' +
          '<td onclick="event.stopPropagation()">' +
            '<div class="action-menu-wrap">' +
              '<button type="button" class="action-trigger" onclick="toggleActionMenu(\'am-invite-' + r.id + '\')">' +
              '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg></button>' +
              '<div class="action-menu" id="am-invite-' + r.id + '">' +
                (r.message_id ? '<button type="button" data-check-recipient-delivery data-id="' + r.id + '" data-mid="' + esc(r.message_id) + '">Check Delivery</button>' : '') +
                '<form method="POST" action="' + "{{ url('/digital-cards/recipients') }}/" + r.id + '/resend' + '" style="display:contents">' +
                  '<input type="hidden" name="_token" value="' + document.querySelector('meta[name="csrf-token"]').content + '">' +
                  '<button type="submit">Send Again (SMS)</button></form>' +
                '<button type="button" onclick="copyInviteLink(' + r.id + ')">Copy Link</button>' +
                '@if(!$isCommittee)' +
                '<form method="POST" action="' + "{{ url('/digital-cards/recipients') }}/" + r.id + '" data-confirm data-confirm-title="Remove this invite?" data-confirm-message="' + esc(r.name || r.phone) + ' will be removed from this campaign\'s invitations." data-confirm-label="Remove Invite">' +
                  '<input type="hidden" name="_token" value="' + document.querySelector('meta[name="csrf-token"]').content + '">' +
                  '<input type="hidden" name="_method" value="DELETE">' +
                  '<button type="submit" class="danger">Delete</button></form>' +
                '@endif' +
              '</div>' +
            '</div>' +
          '</td>' +
        '</tr>';

      tbody.insertAdjacentHTML('afterbegin', html);
      bindInviteRow(tbody.querySelector('[data-view-invite]'));
    }

    function initials(name){
      return String(name || '?').trim().split(/\s+/).map(function(w){ return w.charAt(0); }).slice(0, 2).join('').toUpperCase();
    }

    function bindInviteRow(tr){
      if (!tr) return;
      tr.addEventListener('click', function(e){
        if (e.target.closest('.action-menu-wrap') || e.target.closest('form') || e.target.closest('button') || e.target.closest('a')) return;
        openInviteDetail(tr);
      });
    }
    document.querySelectorAll('[data-view-invite]').forEach(bindInviteRow);

    function openInviteDetail(tr){
      var d = tr.dataset;
      openInviteId = d.id;
      document.getElementById('dInviteAvatar').textContent = initials(d.name);
      document.getElementById('dInviteName').textContent = d.name || '—';
      var st = document.getElementById('dInviteStatus');
      st.textContent = d.statusLabel || 'Pending';
      st.className = 'badge badge-' + (d.statusColor || 'neutral') + ' badge-dotted';
      document.getElementById('dInvitePhone').textContent = d.phone || '—';
      document.getElementById('dInviteSent').textContent = d.sentAt || 'Not sent';
      document.getElementById('dInviteChecked').textContent = d.checkedAt || '—';

      var db = document.getElementById('dDeliveryBadge');
      db.textContent = d.deliveryLabel || '—';
      db.className = 'badge badge-' + (d.deliveryColor || 'neutral') + ' badge-dotted';
      document.getElementById('dCheckDelivery').disabled = !d.mid;

      var linkEl = document.getElementById('dInviteLink');
      linkEl.href = d.link || '#';
      linkEl.textContent = d.link ? d.link.replace(/^https?:\/\//, '') : '—';
      document.getElementById('dInviteMsg').textContent = d.mid ? d.mid : '—';

      openDrawerById('inviteDetailDrawer');
    }

    document.getElementById('dCheckDelivery').addEventListener('click', function(){
      var tr = document.querySelector('[data-view-invite][data-id="' + openInviteId + '"]');
      if (!tr || !tr.dataset.mid) return;
      this.disabled = true;
      this.textContent = '…';
      fetch("{{ url('/digital-cards/recipients') }}/" + openInviteId + '/delivery', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
      })
      .then(function(r){ return r.json(); })
      .then(function(j){
        var btn = document.getElementById('dCheckDelivery');
        btn.disabled = false;
        btn.textContent = 'Check';
        var badge = document.getElementById('dDeliveryBadge');
        if (badge && j.label) {
          badge.textContent = j.label;
          badge.className = 'badge badge-' + (j.color || 'neutral') + ' badge-dotted';
        }
        document.getElementById('dInviteChecked').textContent = j.checked_at || '—';
        var rowBadge = document.getElementById('rdel-badge-' + openInviteId);
        if (rowBadge && j.label) {
          rowBadge.textContent = j.label;
          rowBadge.className = 'badge badge-' + (j.color || 'neutral') + ' badge-dotted';
        }
        toast(j.label || 'Status updated', j.color === 'success' ? 'success' : (j.color === 'danger' ? 'error' : 'info'));
      })
      .catch(function(){
        var btn = document.getElementById('dCheckDelivery');
        btn.disabled = false;
        btn.textContent = 'Check';
        toast('Could not check delivery status', 'error');
      });
    });

    document.getElementById('dCopyLink').addEventListener('click', function(){
      var tr = document.querySelector('[data-view-invite][data-id="' + openInviteId + '"]');
      if (tr && tr.dataset.link) {
        navigator.clipboard.writeText(tr.dataset.link).then(function(){
          toast('Invite link copied', 'success');
        }, function(){ toast('Could not copy link', 'error'); });
      }
    });

    document.addEventListener('click', function(e){
      var btn = e.target.closest('[data-check-recipient-delivery]');
      if (!btn) return;
      checkDelivery(btn.dataset.id, btn.dataset.mid, btn);
    });

    function checkDelivery(id, mid, btn){
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
        if (btn) { btn.textContent = 'Check'; btn.disabled = false; }
        var badge = document.getElementById('rdel-badge-' + id);
        if (badge && j.label) {
          badge.textContent = j.label;
          badge.className = 'badge badge-' + (j.color || 'neutral') + ' badge-dotted';
        }
        if (String(openInviteId) === String(id)) {
          var dBadge = document.getElementById('dDeliveryBadge');
          if (dBadge && j.label) {
            dBadge.textContent = j.label;
            dBadge.className = 'badge badge-' + (j.color || 'neutral') + ' badge-dotted';
          }
          if (j.checked_at) document.getElementById('dInviteChecked').textContent = j.checked_at;
        }
        toast(j.label || 'Status updated', j.color === 'success' ? 'success' : (j.color === 'danger' ? 'error' : 'info'));
      })
      .catch(function(){
        if (btn) { btn.textContent = 'Check'; btn.disabled = false; }
        toast('Could not check delivery status', 'error');
      });
    }
  });
})();
</script>
@endpush