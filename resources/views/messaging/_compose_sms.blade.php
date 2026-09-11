<div class="two-col" style="align-items:start">
  {{-- ── Left: Compose ─────────────────────────────────────────────────────── --}}
  <div class="glass-card">
    <h2 style="font-size:14.5px;margin:0 0 14px">Compose SMS</h2>
    <form method="POST" action="{{ route('messaging.store') }}" id="composeForm">
      @csrf
      <input type="hidden" name="channel" value="sms">
      <input type="hidden" name="phones_json" id="phonesJson" value="{{ old('phones_json', '') }}">
      <input type="hidden" name="recipients" id="recipientsLabel" value="{{ old('recipients', 'Selected Recipients') }}">
      <input type="hidden" name="recipient_filter" id="recipientFilter" value="{{ old('recipient_filter', '') }}">
      <input type="hidden" name="recipient_value" id="recipientValue" value="{{ old('recipient_value', '') }}">
      <input type="hidden" name="phone" id="manualPhone" value="{{ old('phone', '') }}">
      <div class="form-grid">
        <div class="field full">
          <label>Template <span style="font-weight:400;color:var(--text-tertiary)">(optional — loads a saved template)</span></label>
          <select id="templateSelect" style="width:100%" onchange="onTemplateSelect(this)">
            <option value="">— Choose a template —</option>
            @foreach($templates as $tpl)
            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field full"><label>Message *</label>
          <textarea name="message" required placeholder="Type your message here..." style="min-height:140px" id="smsMessage" oninput="updateSmsCount()">{{ old('message', session('template', '')) }}</textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;gap:12px;flex-wrap:wrap">
            <small style="color:var(--text-muted)">1 SMS = 160 chars. Longer messages split (153 chars/part) and charged per part.</small>
            <small id="smsCount" style="font-weight:700;color:var(--text-secondary)">0 SMS</small>
          </div>
        </div>
      </div>
      <div class="flex gap-8" style="margin-top:14px;justify-content:flex-end;align-items:center;flex-wrap:wrap">
        <span id="costHint" style="font-size:11px;color:var(--text-tertiary);margin-right:auto"></span>
        <button type="submit" name="action" value="draft" class="btn btn-secondary">Save Draft</button>
        <button type="submit" name="action" value="send" id="sendSmsBtn" class="btn btn-accent"
                data-confirm data-confirm-title="Send SMS?"
                data-confirm-message="SMS messages will be sent via the multi API and your account will be charged per recipient."
                data-confirm-label="Send SMS">Send SMS</button>
      </div>
      <p style="margin:8px 0 0;font-size:11px;color:var(--text-tertiary);text-align:right">Bulk send uses <code>POST /api/sms/v2/text/multi</code> · flash=0 · one message object per recipient.</p>
    </form>
  </div>

  {{-- ── Right: Recipient picker ─────────────────────────────────────────── --}}
  <div class="glass-card" id="recipientsCard">
    <div class="flex" style="align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px;flex-wrap:wrap">
      <div>
        <h2 style="font-size:14.5px;margin:0">Recipients</h2>
        <small style="color:var(--text-tertiary);font-weight:600"><span id="recipientCountBadge">0</span> selected</small>
      </div>
      <div style="display:flex;gap:6px">
        <button type="button" class="btn btn-secondary btn-sm" id="viewRecipientsBtn" data-drawer-open="recipientDrawer" style="padding:4px 10px;font-size:12px" disabled>View List</button>
        <button type="button" class="btn btn-ghost btn-sm" id="clearAllBtn" style="padding:4px 10px;font-size:12px;display:none" onclick="clearAllRecipients()">Clear all</button>
      </div>
    </div>

    {{-- Search box + source filter --}}
    <div style="position:relative;margin-bottom:10px">
      <div style="display:flex;gap:8px">
        <div class="tfield" style="flex:1;position:relative">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="recipientSearch" placeholder="Search users, members, pledges, registrations by name or phone…" autocomplete="off" style="padding-left:38px;padding-right:30px">
          <button type="button" id="clearSearchBtn" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:transparent;border:none;color:var(--text-tertiary);display:none;cursor:pointer" onclick="clearSearch()">✕</button>
        </div>
        <select id="sourceFilter" style="height:40px;border-radius:11px;border:1px solid var(--border);background:var(--white);padding:0 10px;font-size:12.5px;font-weight:700;color:var(--text-secondary);min-width:125px" onchange="onSourceFilterChange()">
          <option value="all">All sources</option>
          <option value="user">Users</option>
          <option value="member">Members</option>
          <option value="pledge">Pledges</option>
          <option value="registration">Registrations</option>
        </select>
      </div>
      {{-- Results dropdown --}}
      <div id="searchResults" style="display:none;position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:30;background:var(--white);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-lg);max-height:360px;overflow-y:auto"></div>
      <div id="searchHint" style="margin-top:6px;font-size:11px;color:var(--text-tertiary)">Type at least 2 characters. Searches across <b>Users</b> · <b>Members</b> · <b>Pledges</b> · <b>Registrations</b>. Tap a result to add.</div>
      <div id="searchLoading" style="display:none;margin-top:6px;font-size:11.5px;color:var(--text-tertiary)">Searching…</div>
    </div>

    {{-- Bulk group add --}}
    <div style="background:rgba(248,250,252,.9);border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:12px">
      <label style="display:block;font-size:11px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;color:var(--text-tertiary);margin-bottom:6px">Or add a whole group</label>
      <div style="display:flex;gap:8px;align-items:center">
        <select id="bulkGroupSelect" style="flex:1;height:40px;border-radius:11px;border:1px solid var(--border);background:var(--white);padding:0 12px;font-size:13px;font-weight:600;color:var(--text-primary)">
          <option value="">— Choose group —</option>
          <optgroup label="Members">
            <option value="all_active">All Active Members</option>
            <option value="all">All Members</option>
            <option value="inactive">Inactive Members</option>
            <option value="new">New Members</option>
            <option value="member_type:student">Students only</option>
            <option value="member_type:non_student">Non-Students</option>
            <option value="students_activated">Students — Activated (Current FY)</option>
            <option value="students_not_activated">Students — Not Activated</option>
          </optgroup>
          @if($groups && $groups->count())
          <optgroup label="Groups">
            @foreach($groups as $g)
            <option value="group:{{ $g->id }}">{{ $g->name }}</option>
            @endforeach
          </optgroup>
          @endif
          @if($ministries && $ministries->count())
          <optgroup label="Ministries">
            @foreach($ministries as $m)
            <option value="ministry:{{ $m->id }}">{{ $m->name }}</option>
            @endforeach
          </optgroup>
          @endif
          <optgroup label="Pledges">
            <option value="pledge:">All Pledges</option>
            <option value="pledge:pending">Pledges — Pending</option>
            <option value="pledge:partial">Pledges — Partial</option>
            <option value="pledge:fulfilled">Pledges — Fulfilled</option>
            <option value="pledge:cancelled">Pledges — Cancelled</option>
          </optgroup>
          <optgroup label="Registrations / Admission">
            <option value="registration:">All Registrations</option>
            <option value="registration:pending">Registrations — Pending</option>
            <option value="registration:confirmed">Registrations — Confirmed</option>
            <option value="registration:attended">Registrations — Attended</option>
            <option value="registration:cancelled">Registrations — Cancelled</option>
            <option value="admission:">All Admissions</option>
            <option value="admission:pending">Admissions — Pending</option>
            <option value="digital_card:">Digital Card Recipients</option>
          </optgroup>
        </select>
        <button type="button" class="btn btn-secondary btn-sm" id="bulkAddBtn" onclick="bulkAddGroup()" style="white-space:nowrap">Add group</button>
      </div>
      <div id="bulkHint" style="margin-top:6px;font-size:11px;color:var(--text-tertiary);min-height:16px"></div>
    </div>

    {{-- Selected list --}}
    <div id="selectedEmpty" style="border:1.5px dashed var(--border-strong);border-radius:12px;padding:22px;text-align:center;color:var(--text-tertiary);font-size:12.5px;line-height:1.6">
      <div style="width:42px;height:42px;border-radius:12px;background:var(--blue-light);color:var(--blue-accent);display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
      No recipients selected.<br>Search above or add a group to build your list.<br>
      <span style="font-size:11px">You can mix Users, Members, Pledges, Registrations. Duplicates by phone are auto-removed.</span>
    </div>
    <div id="selectedListWrap" style="display:none">
      <div id="selectedList" style="display:flex;flex-direction:column;gap:7px;max-height:380px;overflow-y:auto;padding-right:2px"></div>
      <div id="selectedMeta" style="margin-top:10px;font-size:11.5px;color:var(--text-secondary);line-height:1.5"></div>
    </div>

    {{-- Manual add --}}
    <details style="margin-top:12px;background:var(--white);border:1px solid var(--border);border-radius:10px;padding:10px 12px">
      <summary style="cursor:pointer;font-size:12.5px;font-weight:800;color:var(--text-primary)">Manual add — phone numbers</summary>
      <div style="margin-top:10px;display:grid;gap:8px">
        <div class="form-grid" style="gap:10px">
          <div class="field"><label style="font-size:11.5px">Name (optional)</label><input type="text" id="manualNameInput" placeholder="e.g. John Doe"></div>
          <div class="field"><label style="font-size:11.5px">Phone *</label><input type="text" id="manualPhoneInput" placeholder="2557XXXXXXXXX or 07XXXXXXXX" onkeydown="if(event.key==='Enter'){event.preventDefault();manualAdd();}"></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <button type="button" class="btn btn-secondary btn-sm" onclick="manualAdd()">Add phone</button>
          <small style="color:var(--text-tertiary);font-size:11px">Press Enter or click Add. Pasting multiple numbers? Separate with commas.</small>
        </div>
        <div class="field">
          <label style="font-size:11.5px">Bulk paste (comma or line separated)</label>
          <textarea id="manualBulkPaste" placeholder="255712345678, 255756789012&#10;0768123456" style="min-height:70px;font-family:ui-monospace,monospace;font-size:12.5px"></textarea>
          <button type="button" class="btn btn-secondary btn-sm" style="margin-top:8px" onclick="manualBulkAdd()">Add pasted numbers</button>
        </div>
      </div>
    </details>

    <hr style="border:none;border-top:1px solid var(--border);margin:14px 0">
    <div style="font-size:11.5px;color:var(--text-muted);line-height:1.6">
      <p style="margin:0 0 4px">Selected recipients are sent via <code>POST /api/sms/v2/text/multi</code> as individual <code>messages[]</code> objects (same text, <code>flash:0</code>). Duplicates by phone are removed automatically.</p>
      <p style="margin:0">Use <b>View List</b> to review the full table before sending.</p>
    </div>
  </div>
</div>

{{-- ── Drawer: full recipient table ─────────────────────────────────────── --}}
<div class="drawer-overlay" id="recipientDrawer">
  <div class="drawer-panel" style="max-width:560px">
    <div class="drawer-head">
      <div><h3>Message Recipients</h3><p id="recipientDrawerMeta">0 selected</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body" style="padding:0">
      <div class="table-scroll" style="max-height:none">
        <table class="data-table" style="min-width:560px">
          <thead>
            <tr>
              <th>Name</th>
              <th>Phone</th>
              <th>Source</th>
              <th>Details</th>
              <th style="width:40px"></th>
            </tr>
          </thead>
          <tbody id="recipientTableBody"></tbody>
        </table>
      </div>
      <div id="drawerEmpty" style="padding:40px 20px;text-align:center;color:var(--text-tertiary);font-size:13px">No recipients selected.</div>
    </div>
    <div class="drawer-foot">
      <span id="drawerCountHint" style="margin-right:auto;font-size:12px;font-weight:700;color:var(--text-tertiary)"></span>
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-accent" onclick="closeDrawerById('recipientDrawer')">Done</button>
    </div>
  </div>
</div>

<script>
var smsTemplates = @json($templates->map(fn($t) => ['id'=>$t->id,'name'=>$t->name,'message'=>$t->message])->values()->toArray());
var SEARCH_URL = "{{ route('messaging.search-recipients') }}";
var RECIPIENTS_URL = "{{ route('messaging.recipients') }}";

// ── State ───────────────────────────────────────────────────────────────
var selectedRecipients = []; // {key, source, source_label, name, phone, extra, badge_color}
var searchDebounceTimer = null;
var lastSearchQuery = '';

function normPhone(p) {
  p = String(p||'').replace(/[\s\-\(\)]/g,'');
  if (p.startsWith('+255')) return p.slice(1);
  if (p.startsWith('255')) return p;
  if (p.startsWith('0')) return '255' + p.slice(1);
  return p;
}
function isDuplicatePhone(phone) {
  var n = normPhone(phone);
  return selectedRecipients.some(function(r){ return normPhone(r.phone) === n; });
}
function badgeClass(color) {
  return 'badge-' + (color||'neutral');
}
function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Template ───────────────────────────────────────────────────────────
function onTemplateSelect(sel) {
  var id = sel.value;
  if (!id) return;
  var tpl = smsTemplates.find(function(t){ return String(t.id) === String(id); });
  if (!tpl) return;
  var msg = document.getElementById('smsMessage');
  msg.value = tpl.message;
  sel.value = '';
  updateSmsCount();
  toast('Template loaded — review and edit before sending', 'info');
}

// ── Search ─────────────────────────────────────────────────────────────
var searchInput = document.getElementById('recipientSearch');
var sourceFilter = document.getElementById('sourceFilter');
var searchResults = document.getElementById('searchResults');
var searchHint = document.getElementById('searchHint');
var searchLoading = document.getElementById('searchLoading');
var clearSearchBtn = document.getElementById('clearSearchBtn');

if (searchInput) {
  searchInput.addEventListener('input', function(){
    var q = this.value.trim();
    clearSearchBtn.style.display = q ? 'block' : 'none';
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    if (q.length < 2) {
      searchResults.style.display = 'none';
      searchResults.innerHTML = '';
      searchLoading.style.display = 'none';
      searchHint.style.display = 'block';
      return;
    }
    searchHint.style.display = 'none';
    searchLoading.style.display = 'block';
    searchDebounceTimer = setTimeout(function(){ doSearch(q); }, 300);
  });
  searchInput.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { clearSearch(); }
    if (e.key === 'Enter') { e.preventDefault(); if (searchResults.style.display !== 'none') { var first = searchResults.querySelector('[data-add-key]'); if(first) first.click(); } }
  });
}
document.addEventListener('click', function(e){
  if (!e.target.closest('#recipientsCard')) return;
  // close dropdown if clicking outside search area but inside card? Keep open; only close when clicking outside card
});
document.addEventListener('click', function(e){
  if (!e.target.closest('#searchResults') && !e.target.closest('#recipientSearch') && !e.target.closest('#sourceFilter')) {
    // do not auto-close immediately; keep results visible until cleared – but hide if click outside card
    if (!e.target.closest('#recipientsCard')) { /* keep */ }
  }
});
function onSourceFilterChange(){
  var q = searchInput.value.trim();
  if (q.length >= 2) doSearch(q);
}
function clearSearch(){
  searchInput.value = '';
  clearSearchBtn.style.display = 'none';
  searchResults.style.display = 'none';
  searchResults.innerHTML = '';
  searchLoading.style.display = 'none';
  searchHint.style.display = 'block';
  lastSearchQuery = '';
  searchInput.focus();
}
function doSearch(q){
  lastSearchQuery = q;
  var source = sourceFilter.value || 'all';
  var url = SEARCH_URL + '?q=' + encodeURIComponent(q) + '&source=' + encodeURIComponent(source) + '&limit=20';
  fetch(url).then(function(r){ return r.json(); }).then(function(data){
    if (q !== lastSearchQuery) return; // stale
    searchLoading.style.display = 'none';
    renderSearchResults(data.results || [], q);
  }).catch(function(){
    searchLoading.style.display = 'none';
    searchResults.style.display = 'block';
    searchResults.innerHTML = '<div style="padding:14px;color:var(--danger);font-size:12.5px">Search failed. Try again.</div>';
  });
}
function renderSearchResults(results, q){
  if (!results.length) {
    searchResults.innerHTML = '<div style="padding:14px;color:var(--text-tertiary);font-size:12.5px">No matches for "'+escapeHtml(q)+'". Try a different name or phone.</div>';
    searchResults.style.display = 'block';
    return;
  }
  var html = '';
  results.forEach(function(r){
    var dup = isDuplicatePhone(r.phone);
    var badge = '<span class="badge '+badgeClass(r.badge_color)+' badge-dotted" style="font-size:10.5px">'+escapeHtml(r.source_label)+'</span>';
    html += '<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border);cursor:'+(dup?'not-allowed':'pointer')+';opacity:'+(dup?'.55':'1')+'" '+(dup?'':'data-add-key="'+escapeHtml(r.key)+'"')+'>'
      + '<div style="flex:1;min-width:0">'
      + '<div style="font-weight:700;font-size:13px;color:var(--text-primary)">'+escapeHtml(r.name)+' '+badge+'</div>'
      + '<div style="font-size:12px;color:var(--text-tertiary);font-family:ui-monospace,monospace">'+escapeHtml(r.phone)+'<span style="margin:0 6px;opacity:.4">·</span>'+escapeHtml(r.extra||'—')+'</div>'
      + '</div>'
      + '<div style="flex-shrink:0">'+(dup?'<span style="font-size:11px;font-weight:700;color:var(--text-tertiary)">Added ✓</span>':'<span style="font-size:11px;font-weight:800;color:var(--blue-accent)">Add +</span>')+'</div>'
      + '</div>';
  });
  html += '<div style="padding:8px 14px;font-size:11px;color:var(--text-tertiary);background:rgba(248,250,252,.8)">'+results.length+' result(s) · click to add · duplicates by phone blocked</div>';
  searchResults.innerHTML = html;
  searchResults.style.display = 'block';
  // attach click handlers
  searchResults.querySelectorAll('[data-add-key]').forEach(function(el){
    el.addEventListener('click', function(){
      var key = this.getAttribute('data-add-key');
      var item = results.find(function(x){ return x.key === key; });
      if (item) { addRecipient(item); }
    });
  });
}

// ── Add / Remove / Render ─────────────────────────────────────────────
function addRecipient(obj){
  if (!obj || !obj.phone) return;
  if (isDuplicatePhone(obj.phone)) {
    toast('Already added: ' + obj.phone, 'warning');
    return;
  }
  if (selectedRecipients.length >= 300) {
    toast('Recipient limit reached (300). Remove some before adding more.', 'error');
    return;
  }
  // normalize obj shape
  selectedRecipients.push({
    key: obj.key || ('manual_'+normPhone(obj.phone)),
    source: obj.source || 'manual',
    source_label: obj.source_label || 'Manual',
    name: obj.name || obj.phone,
    phone: normPhone(obj.phone),
    extra: obj.extra || '',
    badge_color: obj.badge_color || 'neutral'
  });
  renderSelected();
  toast('Added: ' + obj.name + ' ('+normPhone(obj.phone)+')', 'success');
  // keep search open but refresh dup states
  if (searchResults.style.display !== 'none' && lastSearchQuery) {
    // re-render to mark new duplicate
    var q = lastSearchQuery;
    var source = sourceFilter.value || 'all';
    // quick local re-render without refetch if we have results cached
    var els = searchResults.querySelectorAll('[data-add-key]');
    // Instead refetch to update dup visuals simply re-render last results if available
    // We'll just call render logic again after small delay by re-searching cache
    // For now, just update that specific row visually
    searchResults.querySelectorAll('div').forEach(function(row){
      // crude: find row containing phone
      if (row.textContent && row.textContent.indexOf(obj.phone) !== -1) {
        row.style.opacity = '.55';
        row.style.cursor = 'not-allowed';
        row.removeAttribute('data-add-key');
        var span = row.querySelector('span');
        if (span) span.textContent = 'Added ✓';
      }
    });
  }
}
function removeRecipientByPhone(phone){
  var n = normPhone(phone);
  selectedRecipients = selectedRecipients.filter(function(r){ return normPhone(r.phone) !== n; });
  renderSelected();
  toast('Removed ' + phone, 'info');
}
function clearAllRecipients(){
  if (selectedRecipients.length === 0) return;
  selectedRecipients = [];
  renderSelected();
  toast('All recipients cleared', 'info');
}
function renderSelected(){
  var list = document.getElementById('selectedList');
  var wrap = document.getElementById('selectedListWrap');
  var empty = document.getElementById('selectedEmpty');
  var countBadge = document.getElementById('recipientCountBadge');
  var viewBtn = document.getElementById('viewRecipientsBtn');
  var clearBtn = document.getElementById('clearAllBtn');
  var meta = document.getElementById('selectedMeta');
  var phonesJson = document.getElementById('phonesJson');
  var recipientsLabel = document.getElementById('recipientsLabel');
  var recipientFilter = document.getElementById('recipientFilter');

  var count = selectedRecipients.length;
  if (countBadge) countBadge.textContent = count;
  if (phonesJson) phonesJson.value = JSON.stringify(selectedRecipients.map(function(r){ return r.phone; }));
  // recipients label for backend audit log
  if (recipientsLabel) {
    if (count === 0) recipientsLabel.value = 'No recipients';
    else if (count === 1) recipientsLabel.value = selectedRecipients[0].name + ' ('+selectedRecipients[0].phone+')';
    else {
      var sources = {};
      selectedRecipients.forEach(function(r){ sources[r.source_label] = (sources[r.source_label]||0)+1; });
      var srcSummary = Object.keys(sources).map(function(k){ return k+':'+sources[k]; }).join(', ');
      recipientsLabel.value = count + ' selected ('+srcSummary+')';
    }
  }
  if (recipientFilter) recipientFilter.value = 'custom_selection';

  if (count === 0) {
    wrap.style.display = 'none';
    empty.style.display = 'block';
    viewBtn.disabled = true;
    clearBtn.style.display = 'none';
  } else {
    empty.style.display = 'none';
    wrap.style.display = 'block';
    viewBtn.disabled = false;
    clearBtn.style.display = 'inline-flex';
  }

  // Render chips/list
  list.innerHTML = '';
  selectedRecipients.forEach(function(r){
    var row = document.createElement('div');
    row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--white);border:1px solid var(--border);border-radius:10px';
    row.innerHTML = '<div style="width:34px;height:34px;border-radius:9px;background:var(--blue-light);color:var(--blue-accent);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0">'+escapeHtml((r.name||'?').charAt(0).toUpperCase())+'</div>'
      + '<div style="flex:1;min-width:0">'
      + '<div style="font-weight:700;font-size:13px;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+escapeHtml(r.name)+' <span class="badge '+badgeClass(r.badge_color)+' badge-dotted" style="font-size:10px;vertical-align:middle;margin-left:6px">'+escapeHtml(r.source_label)+'</span></div>'
      + '<div style="font-size:11.5px;color:var(--text-tertiary);font-family:ui-monospace,monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+escapeHtml(r.phone)+' <span style="opacity:.35">·</span> '+escapeHtml(r.extra||'—')+'</div>'
      + '</div>'
      + '<button type="button" onclick="removeRecipientByPhone(\''+escapeHtml(r.phone).replace(/'/g,"\\'")+'\')" style="width:28px;height:28px;border-radius:8px;border:1px solid var(--border);background:var(--white);color:var(--text-tertiary);display:flex;align-items:center;justify-content:center;flex-shrink:0" title="Remove">✕</button>';
    list.appendChild(row);
  });

  // Meta summary
  if (count > 0 && meta) {
    var bySource = {};
    selectedRecipients.forEach(function(r){ bySource[r.source_label]=(bySource[r.source_label]||0)+1; });
    var parts = [];
    Object.keys(bySource).forEach(function(k){ parts.push(k+': '+bySource[k]); });
    meta.innerHTML = parts.join(' &nbsp;&middot;&nbsp; ') + ' &nbsp;|&nbsp; '+count+' unique phone(s)';
  }

  renderDrawerTable();
  updateSmsCount();
}

function renderDrawerTable(){
  var tbody = document.getElementById('recipientTableBody');
  var empty = document.getElementById('drawerEmpty');
  var meta = document.getElementById('recipientDrawerMeta');
  var hint = document.getElementById('drawerCountHint');
  if (!tbody) return;
  tbody.innerHTML = '';
  var count = selectedRecipients.length;
  if (meta) meta.textContent = count ? count + ' recipient(s) selected · duplicates removed' : 'No recipients';
  if (hint) hint.textContent = count ? count + ' recipient(s)' : '';
  if (count === 0) {
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';
  selectedRecipients.forEach(function(r){
    var tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid var(--border)';
    var badge = '<span class="badge '+badgeClass(r.badge_color)+' badge-dotted" style="font-size:10.5px">'+escapeHtml(r.source_label)+'</span>';
    tr.innerHTML = '<td style="padding:8px 10px;font-weight:600;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+escapeHtml(r.name)+'</td>'
      + '<td style="padding:8px 10px;font-family:ui-monospace,monospace;font-size:12.5px">'+escapeHtml(r.phone)+'</td>'
      + '<td style="padding:8px 10px">'+badge+'</td>'
      + '<td style="padding:8px 10px;font-size:12px;color:var(--text-tertiary);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+escapeHtml(r.extra||'—')+'</td>'
      + '<td style="padding:8px 10px"><button type="button" onclick="removeRecipientByPhone(\''+escapeHtml(r.phone).replace(/'/g,"\\'")+'\')" style="width:26px;height:26px;border-radius:7px;border:1px solid var(--border);background:var(--white);color:var(--danger)">✕</button></td>';
    tbody.appendChild(tr);
  });
}

// ── Bulk group add ─────────────────────────────────────────────────────
function bulkAddGroup(){
  var sel = document.getElementById('bulkGroupSelect');
  var btn = document.getElementById('bulkAddBtn');
  var hint = document.getElementById('bulkHint');
  var raw = sel.value;
  if (!raw) { toast('Choose a group first', 'warning'); return; }
  var filter, value;
  if (raw.indexOf(':') !== -1) {
    var parts = raw.split(':');
    filter = parts[0];
    value = parts.slice(1).join(':');
  } else {
    filter = raw;
    value = '';
  }
  btn.disabled = true;
  btn.textContent = 'Loading…';
  hint.textContent = 'Fetching recipients…';
  var url = RECIPIENTS_URL + '?filter=' + encodeURIComponent(filter) + '&value=' + encodeURIComponent(value);
  fetch(url).then(function(r){ return r.json(); }).then(function(data){
    btn.disabled = false;
    btn.textContent = 'Add group';
    var members = data.members || [];
    if (!members.length) {
      hint.textContent = 'No recipients found for that group.';
      toast('No recipients in that group', 'warning');
      return;
    }
    var added = 0, skipped = 0;
    members.forEach(function(m){
      var phone = m.phone;
      if (!phone) return;
      if (isDuplicatePhone(phone)) { skipped++; return; }
      // map to uniform object
      var source = 'member';
      var source_label = m.type ? (m.type==='student'?'Student': m.type==='non_student'?'Member': m.type) : 'Member';
      var extra = m.status || '';
      var badge = 'neutral';
      // detect module types
      if (filter === 'pledge' || m.type === 'Pledge') { source='pledge'; source_label='Pledge'; badge='purple'; extra = (m.status||'') + (m.group?' · '+m.group:''); }
      else if (filter === 'registration' || filter === 'admission' || m.type==='Registration' || m.type==='Admission') { source='registration'; source_label='Registration'; badge='warning'; }
      else if (m.type==='student') badge='info';
      addRecipientDirect({
        key: 'bulk_'+normPhone(phone)+'_'+Math.random().toString(36).slice(2,6),
        source: source,
        source_label: source_label,
        name: m.name,
        phone: phone,
        extra: (m.group && m.group!=='—' ? m.group : extra) + (m.ministry && m.ministry!=='—' ? ' · '+m.ministry : ''),
        badge_color: badge
      }, true); // silent
      added++;
    });
    // batch render once
    renderSelected();
    hint.textContent = 'Added ' + added + ' recipient(s)' + (skipped ? ' ('+skipped+' duplicates skipped)' : '') + ' from selected group.';
    toast('Added ' + added + ' from group' + (skipped ? ' ('+skipped+' duplicates skipped)' : ''), 'success');
  }).catch(function(){
    btn.disabled = false;
    btn.textContent = 'Add group';
    hint.textContent = 'Failed to load group.';
    toast('Failed to load group', 'error');
  });
}
function addRecipientDirect(obj, silent){
  if (!obj || !obj.phone || isDuplicatePhone(obj.phone)) return;
  selectedRecipients.push({
    key: obj.key,
    source: obj.source,
    source_label: obj.source_label,
    name: obj.name,
    phone: normPhone(obj.phone),
    extra: obj.extra,
    badge_color: obj.badge_color
  });
}

// ── Manual add ─────────────────────────────────────────────────────────
function manualAdd(){
  var nameEl = document.getElementById('manualNameInput');
  var phoneEl = document.getElementById('manualPhoneInput');
  var name = nameEl.value.trim() || phoneEl.value.trim();
  var phone = phoneEl.value.trim();
  if (!phone) { toast('Enter a phone number', 'warning'); phoneEl.focus(); return; }
  // support comma-separated in single input
  if (phone.indexOf(',') !== -1 || phone.indexOf(' ') !== -1) {
    // if multiple numbers pasted into phone input, delegate to bulk
    var parts = phone.split(/[\n,;]+/).map(function(s){return s.trim();}).filter(Boolean);
    if (parts.length > 1) {
      var added=0, dup=0;
      parts.forEach(function(p){
        var clean = p.replace(/\s/g,'');
        if (!clean) return;
        if (isDuplicatePhone(clean)) dup++; else { addRecipientDirect({key:'manual_'+normPhone(clean),source:'manual',source_label:'Manual',name:name,phone:clean,extra:'Manually added',badge_color:'neutral'},true); added++; }
      });
      renderSelected();
      if (added) toast('Added '+added+' number(s)'+(dup?' ('+dup+' dup skipped)':''),'success');
      else toast('All numbers already added','warning');
      nameEl.value=''; phoneEl.value='';
      return;
    }
  }
  var normalized = normPhone(phone);
  if (normalized.length < 10) { toast('Invalid phone number', 'error'); return; }
  addRecipient({key:'manual_'+normalized,source:'manual',source_label:'Manual',name:name,phone:normalized,extra:'Manually added',badge_color:'neutral'});
  nameEl.value=''; phoneEl.value='';
}
function manualBulkAdd(){
  var ta = document.getElementById('manualBulkPaste');
  var raw = ta.value.trim();
  if (!raw) { toast('Paste numbers first', 'warning'); return; }
  var parts = raw.split(/[\n,;]+/).map(function(s){ return s.trim(); }).filter(Boolean);
  var added=0, dup=0, invalid=0;
  parts.forEach(function(p){
    var clean = p.replace(/\s/g,'');
    var n = normPhone(clean);
    if (n.length < 10) { invalid++; return; }
    if (isDuplicatePhone(n)) { dup++; return; }
    addRecipientDirect({key:'manual_'+n+'_'+Math.random().toString(36).slice(2,4),source:'manual',source_label:'Manual',name:n,phone:n,extra:'Pasted',badge_color:'neutral'},true);
    added++;
  });
  renderSelected();
  ta.value='';
  var msg = 'Added '+added+' number(s)';
  if (dup) msg += ' ('+dup+' duplicates skipped)';
  if (invalid) msg += ' ('+invalid+' invalid skipped)';
  toast(msg, added? 'success':'warning');
}

// ── SMS count ──────────────────────────────────────────────────────────
function updateSmsCount() {
  var msg = document.getElementById('smsMessage');
  if (!msg) return;
  var len = msg.value.length;
  var parts = len === 0 ? 0 : (len <= 160 ? 1 : Math.ceil(len / 153));
  var count = selectedRecipients.length || 0;
  var el = document.getElementById('smsCount');
  if (el) el.textContent = parts + ' SMS' + (parts !== 1 ? 's' : '') + (count > 0 ? ' × ' + count + ' recipient' + (count !== 1 ? 's' : '') + ' = ' + (parts * count) + ' total' : '');
  var hint = document.getElementById('costHint');
  if (hint) hint.textContent = count > 0 ? 'Total messages to send: ' + (parts * count) + ' (' + parts + ' part(s) × ' + count + ' recipients via multi)' : '';
}

// ── Form validation & hydration ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
  // Hydrate from old input (validation failure) — phones_json may contain JSON array of phones
  (function hydrateFromOld(){
    var el = document.getElementById('phonesJson');
    var raw = el ? el.value : '';
    if (!raw) return;
    try {
      var arr = JSON.parse(raw);
      if (!Array.isArray(arr) || !arr.length) return;
      arr.forEach(function(p){
        var ph = String(p).trim();
        if (!ph) return;
        if (isDuplicatePhone(ph)) return;
        addRecipientDirect({key:'restore_'+normPhone(ph),source:'manual',source_label:'Restored',name:ph,phone:normPhone(ph),extra:'From previous input',badge_color:'neutral'}, true);
      });
      // keep el value as-is; render will refresh it
    } catch(e) {}
  })();
  updateSmsCount();
  renderSelected(); // init (or hydrated)
  // intercept send button
  var sendBtn = document.getElementById('sendSmsBtn');
  var form = document.getElementById('composeForm');
  if (sendBtn && form) {
    sendBtn.addEventListener('click', function(e){
      // allow draft path to pass; check which button was clicked via event
      // This click fires before submit; we can block if no recipients
      var msg = document.getElementById('smsMessage').value.trim();
      if (!msg) { toast('Message cannot be empty', 'error'); e.preventDefault(); e.stopPropagation(); return; }
      if (selectedRecipients.length === 0) {
        toast('Select at least one recipient before sending', 'error');
        e.preventDefault(); e.stopPropagation();
        // prevent confirm modal from opening by temporarily disabling data-confirm
        return false;
      }
    });
  }
  if (form) {
    form.addEventListener('submit', function(e){
      var submitter = e.submitter;
      var action = submitter ? submitter.value : '';
      if (action === 'send' && selectedRecipients.length === 0) {
        e.preventDefault();
        toast('Select at least one recipient', 'error');
        return false;
      }
      // ensure hidden fields are fresh
      renderSelected();
    });
  }
  // allow Enter on manual bulk paste? already handled
  // autofocus search on desktop
  if (window.innerWidth > 860) {
    setTimeout(function(){ var el=document.getElementById('recipientSearch'); if(el) el.focus(); }, 400);
  }
});

// Expose for inline handlers
window.removeRecipientByPhone = removeRecipientByPhone;
window.clearAllRecipients = clearAllRecipients;
window.clearSearch = clearSearch;
window.onSourceFilterChange = onSourceFilterChange;
window.bulkAddGroup = bulkAddGroup;
window.manualAdd = manualAdd;
window.manualBulkAdd = manualBulkAdd;
</script>
