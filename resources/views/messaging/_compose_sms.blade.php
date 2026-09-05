<div class="two-col" style="align-items:start">
  <div class="glass-card">
    <h2 style="font-size:14.5px;margin:0 0 14px">Compose SMS</h2>
    <form method="POST" action="{{ route('messaging.store') }}" id="composeForm">
      @csrf
      <input type="hidden" name="channel" value="sms">
      <input type="hidden" name="phones_json" id="phonesJson" value="">
      <input type="hidden" name="recipient_filter" id="recipientFilter" value="all_active">
      <input type="hidden" name="recipient_value" id="recipientValue" value="">
      <div class="form-grid">
        <div class="field full">
          <label>Send To *</label>
          <select id="recipientType" style="width:100%" onchange="onRecipientTypeChange(this)">
            <option value="admission">Admission Desk</option>
            <option value="registration">Registrations</option>
            <option value="pledge">Pledges</option>
            <option value="digital_card">Digital Cards</option>
            <option value="all_active" selected>All Active Members</option>
            <option value="all">All Members</option>
            <option value="manual">Manual Single Number</option>
            <option value="group">By Group</option>
            <option value="ministry">By Ministry</option>
            <option value="member_type">By Member Type</option>
            <option value="staff_type">By Staff Type</option>
            <option value="status">By Status</option>
            <option value="students_activated">Students — Activated This Year</option>
            <option value="students_not_activated">Students — Not Yet Activated</option>
            <option value="inactive">Inactive Members</option>
            <option value="new">New Members</option>
          </select>
          <div id="filterValueWrap" style="display:none;margin-top:8px">
            <select id="filterValue" style="width:100%" onchange="loadRecipients()"><option value="">— Select —</option></select>
          </div>
        </div>
        <div class="field full" id="manualPhoneWrap" style="display:none">
          <label>Or enter phone manually</label>
          <input name="phone" id="manualPhone" placeholder="e.g. 0622239304" style="font-size:15px;letter-spacing:0.5px">
          <small style="color:var(--text-muted);margin-top:4px;display:block">Overrides the filter. Leave empty to use filtered recipients.</small>
        </div>
        <div class="field full"><label>Recipients Label *</label>
          <input name="recipients" required value="{{ old('recipients', session('templateName', 'All Active Members')) }}" placeholder="e.g. Youth Group, Payment Reminders" id="recipientsLabel">
        </div>
        <div class="field full"><label>Message *</label>
          <textarea name="message" required placeholder="Type your message here..." style="min-height:140px" id="smsMessage" oninput="updateSmsCount()">{{ old('message', session('template', '')) }}</textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px">
            <small style="color:var(--text-muted)">1 SMS = 160 chars. Longer messages split and charged per part.</small>
            <small id="smsCount" style="font-weight:700;color:var(--text-secondary)">0 SMS</small>
          </div>
        </div>
      </div>
      <div class="flex gap-8" style="margin-top:14px;justify-content:flex-end;align-items:center">
        <span id="costHint" style="font-size:11px;color:var(--text-tertiary);margin-right:auto"></span>
        <button type="submit" name="action" value="draft" class="btn btn-secondary">Save Draft</button>
        <button type="submit" name="action" value="send" class="btn btn-accent"
                data-confirm data-confirm-title="Send SMS?"
                data-confirm-message="SMS messages will be sent via the API and your account will be charged."
                data-confirm-label="Send SMS">Send SMS</button>
      </div>
    </form>
  </div>

  <div class="glass-card">
    <div class="flex" style="align-items:center;justify-content:space-between;margin-bottom:12px">
      <h2 style="font-size:14.5px;margin:0">Recipients</h2>
      <button type="button" class="btn btn-secondary btn-sm" id="viewRecipientsBtn"
              data-drawer-open="recipientDrawer" style="padding:4px 12px;font-size:12px" disabled>View List</button>
    </div>
    <div id="recipientSummary">
      <div style="display:flex;align-items:center;gap:12px">
        <div class="m-ico" style="width:40px;height:40px;background:var(--blue-light);color:var(--blue-accent)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div>
          <div style="font-size:24px;font-weight:800;color:var(--navy-900);line-height:1" id="recipientCount">0</div>
          <div style="font-size:11.5px;color:var(--text-tertiary)">recipient(s) with phone numbers</div>
        </div>
      </div>
      <div id="recipientMeta" style="margin-top:12px;font-size:12.5px;color:var(--text-secondary);line-height:1.6;min-height:20px"></div>
    </div>
    <div id="recipientLoading" style="display:none;padding:8px;font-size:12px;color:var(--text-tertiary)">Loading recipients...</div>
    <div id="recipientError" style="display:none;padding:8px;font-size:12.5px;color:var(--red)"></div>
    <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
    <div style="font-size:12.5px;color:var(--text-muted)">
      <p style="margin:0 0 6px">Recipients are loaded from the selected filter (members with a phone number).</p>
      <p style="margin:0">Use <b>View List</b> to confirm who will receive this message before sending.</p>
    </div>
  </div>
</div>

<div class="drawer-overlay" id="recipientDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Message Recipients</h3><p id="recipientDrawerMeta">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body">
      <div class="table-scroll" style="max-height:none">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Phone</th>
              <th>Type</th>
              <th>Status</th>
              <th>Group</th>
              <th>Ministry</th>
            </tr>
          </thead>
          <tbody id="recipientTableBody"></tbody>
        </table>
      </div>
    </div>
    <div class="drawer-foot">
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
    </div>
  </div>
</div>

<script>
var recipientsData = [];
var groupOptions = @json($groups->map(fn($g) => ['id'=>$g->id,'name'=>$g->name])->toArray());
var ministryOptions = @json($ministries->map(fn($m) => ['id'=>$m->id,'name'=>$m->name])->toArray());
var recipientDrawer = document.getElementById('recipientDrawer');

function onRecipientTypeChange(sel) {
  var wrap = document.getElementById('filterValueWrap');
  var valSel = document.getElementById('filterValue');
  var manualWrap = document.getElementById('manualPhoneWrap');
  var f = sel.value;
  document.getElementById('recipientFilter').value = f;

  if (f === 'manual') {
    wrap.style.display = 'none';
    manualWrap.style.display = 'block';
    document.getElementById('phonesJson').value = '';
    document.getElementById('recipientCount').textContent = '0';
    document.getElementById('recipientMeta').innerHTML = '';
    document.getElementById('recipientSummary').style.display = 'block';
    document.getElementById('recipientError').style.display = 'none';
    document.getElementById('recipientTableBody').innerHTML = '';
    document.getElementById('recipientDrawerMeta').textContent = 'Manual entry';
    document.getElementById('viewRecipientsBtn').disabled = true;
    updateSmsCount();
    return;
  }

  var STATUS_MAP = {
    admission: [['', 'All Statuses'], ['pending', 'Pending'], ['confirmed', 'Confirmed'], ['attended', 'Attended'], ['no_show', 'No Show'], ['cancelled', 'Cancelled']],
    registration: [['', 'All Statuses'], ['pending', 'Pending'], ['confirmed', 'Confirmed'], ['attended', 'Attended'], ['no_show', 'No Show'], ['cancelled', 'Cancelled']],
    pledge: [['', 'All Statuses'], ['pending', 'Pending'], ['partial', 'Partial'], ['fulfilled', 'Fulfilled'], ['cancelled', 'Cancelled']],
    digital_card: [['', 'All Statuses'], ['draft', 'Draft'], ['active', 'Active'], ['closed', 'Closed']]
  };

  if (STATUS_MAP[f]) {
    valSel.innerHTML = '';
    STATUS_MAP[f].forEach(function(s) { valSel.innerHTML += '<option value="'+s[0]+'">'+s[1]+'</option>'; });
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
    document.getElementById('manualPhone').value = '';
    document.getElementById('recipientValue').value = '';
    loadRecipients();
    return;
  }

  if (f === 'group') {
    valSel.innerHTML = '<option value="">— Select Group —</option>';
    groupOptions.forEach(function(g) { valSel.innerHTML += '<option value="'+g.id+'">'+g.name+'</option>'; });
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
    document.getElementById('manualPhone').value = '';
  } else if (f === 'ministry') {
    valSel.innerHTML = '<option value="">— Select Ministry —</option>';
    ministryOptions.forEach(function(m) { valSel.innerHTML += '<option value="'+m.id+'">'+m.name+'</option>'; });
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
    document.getElementById('manualPhone').value = '';
  } else if (f === 'member_type') {
    valSel.innerHTML = '<option value="">— Select Type —</option><option value="student">Student</option><option value="non_student">Non-Student</option>';
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
    document.getElementById('manualPhone').value = '';
  } else if (f === 'staff_type') {
    valSel.innerHTML = '<option value="">— Select Staff Type —</option><option value="staff">Staff</option><option value="non_staff">Non-Staff</option>';
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
    document.getElementById('manualPhone').value = '';
  } else if (f === 'status') {
    valSel.innerHTML = '<option value="">— Select Status —</option><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="New">New</option>';
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
    document.getElementById('manualPhone').value = '';
  } else {
    wrap.style.display = 'none';
    manualWrap.style.display = 'none';
    document.getElementById('manualPhone').value = '';
    loadRecipients();
    return;
  }
}

function loadRecipients() {
  var filter = document.getElementById('recipientType').value;
  var value = document.getElementById('filterValue') ? document.getElementById('filterValue').value : '';
  document.getElementById('recipientValue').value = value;

  if (['group','ministry','member_type','staff_type','status'].indexOf(filter) !== -1 && !value) { resetRecipients(); return; }

  document.getElementById('recipientSummary').style.display = 'none';
  document.getElementById('recipientLoading').style.display = 'block';
  document.getElementById('recipientError').style.display = 'none';
  document.getElementById('viewRecipientsBtn').disabled = true;

  var url = '{{ route("messaging.recipients") }}?filter=' + encodeURIComponent(filter);
  if (value) url += '&value=' + encodeURIComponent(value);

  fetch(url).then(function(r) { return r.json(); }).then(function(data) {
    document.getElementById('recipientLoading').style.display = 'none';
    recipientsData = data.members;
    document.getElementById('recipientCount').textContent = data.count;
    document.getElementById('phonesJson').value = JSON.stringify(data.members.map(function(m){ return m.phone; }));

    var meta = document.getElementById('recipientMeta');
    if (data.count > 0) {
      var totalSms = 0;
      var phoneTypes = {};
      data.members.forEach(function(m){ phoneTypes[m.type] = (phoneTypes[m.type]||0)+1; });
      var summary = [];
      Object.keys(phoneTypes).forEach(function(k){ summary.push(k.replace('_',' ') + ': ' + phoneTypes[k]); });
      meta.innerHTML = summary.join(' &nbsp;&middot;&nbsp; ');
    } else {
      meta.innerHTML = '';
    }

    var tbody = document.getElementById('recipientTableBody');
    tbody.innerHTML = '';
    data.members.forEach(function(m) {
      var tr = document.createElement('tr');
      tr.style.borderBottom = '1px solid var(--border)';
      var isMember = m.type === 'student' || m.type === 'non_student';
      var typeBadge = isMember
        ? '<span class="badge badge-'+(m.type === 'student' ? 'info' : 'neutral')+' badge-dotted">'+(m.type === 'student' ? 'Student' : 'Non-Student')+'</span>'
        : '<span class="badge badge-purple badge-dotted">'+m.type+'</span>';
      var s = String(m.status).toLowerCase();
      var statusClass = ['attended','fulfilled','active','invited','delivered','paid','success'].indexOf(s) !== -1 ? 'success'
        : (['pending','partial','no_show','no show','draft','unchecked','enroute','sending'].indexOf(s) !== -1 ? 'warning'
        : (['cancelled','failed','closed','undelivered','expired','rejected'].indexOf(s) !== -1 ? 'danger'
        : (s === 'confirmed' || s === 'new' ? 'info' : 'neutral')));
      tr.innerHTML = '<td style="padding:8px 10px;font-weight:600">'+m.name+'</td>'+
        '<td style="padding:8px 10px;font-family:monospace">'+m.phone+'</td>'+
        '<td style="padding:8px 10px">'+typeBadge+'</td>'+
        '<td style="padding:8px 10px"><span class="badge badge-'+statusClass+' badge-dotted">'+m.status+'</span></td>'+
        '<td style="padding:8px 10px">'+(m.group||'—')+'</td>'+
        '<td style="padding:8px 10px">'+(m.ministry||'—')+'</td>';
      tbody.appendChild(tr);
    });

    if (data.count === 0) {
      document.getElementById('recipientError').textContent = 'No members found with phone numbers for this filter.';
      document.getElementById('recipientError').style.display = 'block';
      document.getElementById('viewRecipientsBtn').disabled = true;
      document.getElementById('recipientSummary').style.display = 'none';
      document.getElementById('recipientDrawerMeta').textContent = 'No recipients';
    } else {
      document.getElementById('recipientSummary').style.display = 'block';
      document.getElementById('viewRecipientsBtn').disabled = false;
      var label = document.getElementById('recipientsLabel').value.trim() || 'Selected recipients';
      document.getElementById('recipientDrawerMeta').textContent = data.count + ' recipient(s) · ' + label;
    }
    updateSmsCount();
  }).catch(function() {
    document.getElementById('recipientLoading').style.display = 'none';
    document.getElementById('recipientError').textContent = 'Error loading recipients.';
    document.getElementById('recipientError').style.display = 'block';
    document.getElementById('recipientSummary').style.display = 'none';
    document.getElementById('viewRecipientsBtn').disabled = true;
  });
}

function resetRecipients() {
  recipientsData = [];
  document.getElementById('phonesJson').value = '';
  document.getElementById('recipientCount').textContent = '0';
  document.getElementById('recipientMeta').innerHTML = '';
  document.getElementById('recipientSummary').style.display = 'block';
  document.getElementById('recipientError').style.display = 'none';
  document.getElementById('recipientTableBody').innerHTML = '';
  document.getElementById('recipientDrawerMeta').textContent = 'No recipients';
  document.getElementById('viewRecipientsBtn').disabled = true;
  updateSmsCount();
}

function updateSmsCount() {
  var msg = document.getElementById('smsMessage');
  if (!msg) return;
  var len = msg.value.length;
  var parts = len === 0 ? 0 : (len <= 160 ? 1 : Math.ceil(len / 153));
  var isManual = document.getElementById('recipientType').value === 'manual';
  var manualPhone = document.getElementById('manualPhone').value.trim();
  var count = isManual && manualPhone ? 1 : (recipientsData.length || 0);
  if (isManual && manualPhone) {
    document.getElementById('phonesJson').value = JSON.stringify([manualPhone]);
  }
  var el = document.getElementById('smsCount');
  if (el) el.textContent = parts + ' SMS' + (parts !== 1 ? 's' : '') + (count > 0 ? ' x ' + count + ' recipient' + (count !== 1 ? 's' : '') + ' = ' + (parts * count) + ' total' : '');
  var hint = document.getElementById('costHint');
  if (hint) hint.textContent = count > 0 ? 'Total messages to send: ' + (parts * count) : '';
}

document.addEventListener('DOMContentLoaded', function() {
  loadRecipients();
  document.getElementById('manualPhone').addEventListener('input', function() {
    updateSmsCount();
    var phone = this.value.trim();
    if (phone) {
      document.getElementById('recipientCount').textContent = '1';
      document.getElementById('recipientMeta').innerHTML = '';
      document.getElementById('recipientSummary').style.display = 'block';
      document.getElementById('recipientError').style.display = 'none';
    } else {
      document.getElementById('recipientCount').textContent = '0';
      document.getElementById('recipientMeta').innerHTML = '';
    }
  });
});
</script>
