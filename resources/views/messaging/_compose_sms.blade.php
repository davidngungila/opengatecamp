<div class="glass-card">
    <h2 style="font-size:14.5px;margin:0 0 14px">Compose SMS</h2>
    <form method="POST" action="{{ route('messaging.store') }}" id="composeForm">
      @csrf
      <input type="hidden" name="channel" value="sms">
      <input type="hidden" name="phones_json" id="phonesJson" value="">
      <input type="hidden" name="recipient_filter" id="recipientFilter" value="">
      <input type="hidden" name="recipient_value" id="recipientValue" value="">
      <div class="form-grid">
        <div class="field full">
          <label>Send To *</label>
          <select id="recipientType" style="width:100%" onchange="onRecipientTypeChange(this)">
            <option value="all_active">All Active Members</option>
            <option value="all">All Members</option>
            <option value="group">By Group</option>
            <option value="ministry">By Ministry</option>
            <option value="member_type">By Member Type</option>
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
        <div class="field full">
          <div id="recipientBadge" style="display:none;padding:10px 14px;background:var(--blue-light);border-radius:10px;border:1px solid rgba(37,99,235,.15)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
              <span style="font-size:13px;font-weight:700;color:var(--navy-900)"><span id="recipientCount">0</span> recipient(s) selected</span>
              <button type="button" class="btn btn-ghost btn-sm" style="padding:2px 8px;font-size:11px" onclick="toggleRecipientList()">View List</button>
            </div>
            <div id="recipientListWrap" style="display:none;max-height:140px;overflow-y:auto;font-size:11.5px;color:var(--text-secondary)">
              <table style="width:100%;border-collapse:collapse">
                <thead><tr style="text-align:left;border-bottom:1px solid var(--border)"><th style="padding:3px 0;font-weight:700">Name</th><th style="padding:3px 0;font-weight:700">Phone</th><th style="padding:3px 0;font-weight:700">Type</th></tr></thead>
                <tbody id="recipientTableBody"></tbody>
              </table>
            </div>
          </div>
          <div id="recipientLoading" style="display:none;padding:8px;font-size:12px;color:var(--text-tertiary)">Loading recipients...</div>
          <div id="recipientError" style="display:none;padding:8px;font-size:12px;color:var(--red)"></div>
        </div>
        <div class="field full" id="manualPhoneWrap" style="display:none">
          <label>Or enter phone manually</label>
          <input name="phone" placeholder="e.g. 0622239304" style="font-size:15px;letter-spacing:0.5px">
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

<script>
var recipientsData = [];
var groupOptions = @json($groups->map(fn($g) => ['id'=>$g->id,'name'=>$g->name])->toArray());
var ministryOptions = @json($ministries->map(fn($m) => ['id'=>$m->id,'name'=>$m->name])->toArray());

function onRecipientTypeChange(sel) {
  var wrap = document.getElementById('filterValueWrap');
  var valSel = document.getElementById('filterValue');
  var manualWrap = document.getElementById('manualPhoneWrap');
  var f = sel.value;
  document.getElementById('recipientFilter').value = f;

  if (f === 'group') {
    valSel.innerHTML = '<option value="">— Select Group —</option>';
    groupOptions.forEach(function(g) { valSel.innerHTML += '<option value="'+g.id+'">'+g.name+'</option>'; });
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
  } else if (f === 'ministry') {
    valSel.innerHTML = '<option value="">— Select Ministry —</option>';
    ministryOptions.forEach(function(m) { valSel.innerHTML += '<option value="'+m.id+'">'+m.name+'</option>'; });
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
  } else if (f === 'member_type') {
    valSel.innerHTML = '<option value="">— Select Type —</option><option value="student">Student</option><option value="non_student">Non-Student</option>';
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
  } else if (f === 'status') {
    valSel.innerHTML = '<option value="">— Select Status —</option><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="New">New</option>';
    wrap.style.display = 'block';
    manualWrap.style.display = 'none';
  } else if (['all','all_active','inactive','new','students_activated','students_not_activated'].indexOf(f) !== -1) {
    wrap.style.display = 'none';
    manualWrap.style.display = 'none';
    loadRecipients();
    return;
  } else {
    wrap.style.display = 'none';
    manualWrap.style.display = 'none';
    resetRecipients();
    return;
  }
}

function loadRecipients() {
  var filter = document.getElementById('recipientType').value;
  var value = document.getElementById('filterValue') ? document.getElementById('filterValue').value : '';
  document.getElementById('recipientValue').value = value;

  if (['group','ministry','member_type','status'].indexOf(filter) !== -1 && !value) { resetRecipients(); return; }

  document.getElementById('recipientLoading').style.display = 'block';
  document.getElementById('recipientError').style.display = 'none';
  document.getElementById('recipientBadge').style.display = 'none';

  var url = '{{ route("messaging.recipients") }}?filter=' + encodeURIComponent(filter);
  if (value) url += '&value=' + encodeURIComponent(value);

  fetch(url).then(function(r) { return r.json(); }).then(function(data) {
    document.getElementById('recipientLoading').style.display = 'none';
    recipientsData = data.members;
    document.getElementById('recipientCount').textContent = data.count;
    document.getElementById('phonesJson').value = JSON.stringify(data.members.map(function(m){ return m.phone; }));

    var tbody = document.getElementById('recipientTableBody');
    tbody.innerHTML = '';
    data.members.forEach(function(m) {
      var tr = document.createElement('tr');
      tr.style.borderBottom = '1px solid var(--border)';
      tr.innerHTML = '<td style="padding:3px 0">'+m.name+'</td><td style="padding:3px 0;font-family:monospace">'+m.phone+'</td><td style="padding:3px 0">'+m.type+'</td>';
      tbody.appendChild(tr);
    });

    if (data.count === 0) {
      document.getElementById('recipientError').textContent = 'No members found with phone numbers for this filter.';
      document.getElementById('recipientError').style.display = 'block';
    } else {
      document.getElementById('recipientBadge').style.display = 'block';
      updateSmsCount();
    }
  }).catch(function() {
    document.getElementById('recipientLoading').style.display = 'none';
    document.getElementById('recipientError').textContent = 'Error loading recipients.';
    document.getElementById('recipientError').style.display = 'block';
  });
}

function resetRecipients() {
  recipientsData = [];
  document.getElementById('phonesJson').value = '';
  document.getElementById('recipientCount').textContent = '0';
  document.getElementById('recipientBadge').style.display = 'none';
  updateSmsCount();
}

function toggleRecipientList() {
  var el = document.getElementById('recipientListWrap');
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function updateSmsCount() {
  var msg = document.getElementById('smsMessage');
  if (!msg) return;
  var len = msg.value.length;
  var parts = len === 0 ? 0 : (len <= 160 ? 1 : Math.ceil(len / 153));
  var count = recipientsData.length || 0;
  var el = document.getElementById('smsCount');
  if (el) el.textContent = parts + ' SMS' + (parts !== 1 ? 's' : '') + (count > 0 ? ' x ' + count + ' recipients = ' + (parts * count) + ' total' : '');
  var hint = document.getElementById('costHint');
  if (hint) hint.textContent = count > 0 ? 'Total messages to send: ' + (parts * count) : '';
}
</script>
