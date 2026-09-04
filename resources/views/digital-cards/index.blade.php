@extends('layouts.app')

@section('title', 'Digital Cards — Open Gate Camp Mission')
@section('crumb', 'Giving / Digital Cards')
@section('page_title', 'Digital Cards')

@section('content')
@php
    $v = fn($f) => old($f, ['type' => $cardType ?? null, 'status' => $status ?? null, 'q' => $q ?? null][$f] ?? null);
    $typeColors = [
        'camp_invitation' => '#1a237e',
        'fundraising'     => '#0d47a1',
        'thank_you'       => '#4a148c',
        'birthday'        => '#e91e63',
        'christmas'       => '#1b5e20',
        'general'         => '#263238',
    ];
    $typeAccents = [
        'camp_invitation' => '#ffd700',
        'fundraising'     => '#4caf50',
        'thank_you'       => '#ff6f00',
        'birthday'        => '#ffffff',
        'christmas'       => '#c62828',
        'general'         => '#2563eb',
    ];
@endphp
<div class="fade-in">
  <div class="section-head">
    <div><h2>Digital Cards</h2><div class="sub">
      {{ number_format($totals['total_cards']) }} cards · {{ number_format($totals['active_cards']) }} active · TZS {{ number_format($totals['total_amount']) }} collected
    </div></div>
    <button type="button" class="btn btn-accent" data-drawer-open="cardNewDrawer">+ New Card</button>
  </div>

  <div class="kpi-grid" style="margin-bottom:18px">
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--blue-light);color:var(--blue-accent)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8h20"/></svg></span></div><div class="kpi-value">{{ number_format($totals['total_cards']) }}</div><div class="kpi-label">Total Cards</div></div>
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--success-bg);color:var(--success)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span></div><div class="kpi-value">{{ number_format($totals['active_cards']) }}</div><div class="kpi-label">Active</div></div>
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--warning-bg);color:var(--warning)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span></div><div class="kpi-value">{{ number_format($totals['total_received']) }}</div><div class="kpi-label">Contributions</div></div>
    <div class="kpi-card"><div class="kpi-top"><span class="kpi-icon" style="background:var(--purple-bg);color:var(--purple)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span></div><div class="kpi-value">TZS {{ number_format($totals['total_amount']) }}</div><div class="kpi-label">Collected</div></div>
  </div>

  <form class="toolbar" method="GET" action="{{ route('cards.index') }}">
    <div class="tfield grow"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input name="q" value="{{ $v('q') }}" placeholder="Search by title, card no..."></div>
    <select class="filter-select" name="type" onchange="this.form.submit()">
      <option value="">All Types</option>
      @foreach($types as $k=>$t)<option value="{{ $k }}" {{ $v('type')==$k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
    </select>
    <select class="filter-select" name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      @foreach($statuses as $k=>$s)<option value="{{ $k }}" {{ $v('status')===$k ? 'selected' : '' }}>{{ $s }}</option>@endforeach
    </select>
  </form>

  <div class="table-card">
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Card No</th><th>Title / Type</th><th>Event</th><th>Collected</th><th>Target</th><th>Progress</th><th>Status</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @forelse($cards as $card)
          <tr>
            <td><span class="badge badge-neutral badge-dotted">{{ $card->card_no }}</span></td>
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:{{ $card->background_color }};color:{{ $card->accent_color }};font-weight:800">{{ mb_substr($card->title, 0, 1) }}</div>
                <div><div class="cu-name">{{ $card->title }}</div><div class="cu-sub">
                  <span class="badge badge-{{ $card->getTypeColor() }} badge-dotted">{{ $card->getTypeLabel() }}</span>
                </div></div>
              </div>
            </td>
            <td>{{ $card->event?->title ?? '—' }}</td>
            <td><b>TZS {{ number_format($card->total_contributions) }}</b></td>
            <td>{{ $card->target_amount ? 'TZS '.number_format($card->target_amount) : '—' }}</td>
            <td>
              @if($card->target_amount > 0)
              <div class="drawer-progress" style="min-width:120px">
                <div class="dp-track"><div class="dp-fill" style="width:{{ $card->progress_percent }}%;background:{{ $card->accent_color }}"></div></div>
                <span class="text-muted" style="font-size:11px">{{ number_format($card->progress_percent, 1) }}%</span>
              </div>
              @else
              <span class="text-muted">—</span>
              @endif
            </td>
            <td><span class="badge badge-{{ $card->getStatusColor() }} badge-dotted">{{ $card->getStatusLabel() }}</span></td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-card-{{ $card->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-card-{{ $card->id }}">
                  <a href="{{ route('cards.preview', $card) }}" target="_blank">Preview</a>
                  @if(!$isCommittee)
                  <button type="button" data-edit-card data-id="{{ $card->id }}" data-title="{{ $card->title }}" data-message="{{ $card->message }}" data-type="{{ $card->card_type }}" data-bg="{{ $card->background_color }}" data-accent="{{ $card->accent_color }}" data-event="{{ $card->event_id }}" data-target="{{ $card->target_amount }}" data-note="{{ $card->contributor_note }}" data-cta="{{ $card->cta_text }}" data-sms="{{ $card->sms_text }}">Edit</button>
                  <button type="button" data-send-sms data-id="{{ $card->id }}" data-title="{{ $card->title }}" data-url="{{ $card->public_url }}">Send via SMS</button>
                  <a href="{{ route('cards.pdf', $card) }}">Download PDF</a>
                  <form method="POST" action="{{ route('cards.status', $card) }}" style="display:contents">@csrf
                    <input type="hidden" name="status" value="{{ $card->status === 'active' ? 'closed' : ($card->status === 'draft' ? 'active' : 'active') }}">
                    <button type="submit">{{ $card->status === 'active' ? 'Close Card' : ($card->status === 'draft' ? 'Activate Card' : 'Re-activate') }}</button>
                  </form>
                  <form method="POST" action="{{ route('cards.destroy', $card) }}" data-confirm
                        data-confirm-title="Delete this card?"
                        data-confirm-message="{{ $card->card_no }} — {{ $card->title }} will be permanently removed."
                        data-confirm-label="Delete Card">@csrf @method('DELETE')
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  @endif
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="8"><div class="empty-state" style="padding:40px 20px"><h3>No digital cards yet</h3><p>Create your first professional digital card to share.</p><button type="button" class="btn btn-accent" data-drawer-open="cardNewDrawer">+ New Card</button></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <span class="tf-info">Showing {{ $cards->firstItem() ?? 0 }}–{{ $cards->lastItem() ?? 0 }} of {{ $cards->total() }} cards</span>
      <div class="pagination">{{ $cards->links() }}</div>
    </div>
  </div>
</div>

@if(!$isCommittee)
<div class="drawer-overlay" id="cardNewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Create Digital Card</h3><p>Design a professional card to share via SMS</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('cards.store') }}">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Title *</label><input name="title" placeholder="e.g. Open Gate Camp Season 3" value="{{ old('title') }}" required></div>
          <div class="field"><label>Card Type *</label><select name="card_type" id="newCardType" required>
            @foreach($types as $k=>$t)<option value="{{ $k }}" @if(old('card_type')==$k) selected @endif>{{ $t }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Message *</label><textarea name="message" placeholder="Write a heartfelt message for your recipients..." required>{{ old('message') }}</textarea></div>
          <div class="field"><label>Linked Event</label><select name="event_id">
            <option value="">— None —</option>
            @foreach($events as $e)<option value="{{ $e->id }}" @if(old('event_id')==$e->id) selected @endif>{{ $e->title }} · {{ $e->start_date?->format('d M Y') }}</option>@endforeach
          </select></div>
          <div class="field"><label>Target Amount (TZS)</label><input type="number" step="0.01" min="0" name="target_amount" value="{{ old('target_amount') }}" placeholder="e.g. 500000"></div>
          <div class="field"><label>Background Color</label><input type="color" name="background_color" id="newCardBg" value="{{ old('background_color', '#1a237e') }}"></div>
          <div class="field"><label>Accent Color</label><input type="color" name="accent_color" id="newCardAccent" value="{{ old('accent_color', '#ffd700') }}"></div>
          <div class="field full"><label>Contributor Note</label><input name="contributor_note" placeholder='e.g. "Your support helps us reach more students"' value="{{ old('contributor_note') }}"></div>
          <div class="field"><label>Button Text</label><input name="cta_text" placeholder="Contribute Now" value="{{ old('cta_text', 'Contribute Now') }}"></div>
          <div class="field full"><label>SMS Text (if blank, a default link message is used)</label><textarea name="sms_text" placeholder="View your special digital card: {link}">{{ old('sms_text') }}</textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Create Card</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="cardEditDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Edit Digital Card</h3><p>Update card design and content</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="editCardForm">
      @csrf @method('PUT')
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Title *</label><input name="title" id="editCardTitle" required></div>
          <div class="field"><label>Card Type *</label><select name="card_type" id="editCardType">
            @foreach($types as $k=>$t)<option value="{{ $k }}">{{ $t }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Message *</label><textarea name="message" id="editCardMessage" required></textarea></div>
          <div class="field"><label>Linked Event</label><select name="event_id" id="editCardEvent">
            <option value="">— None —</option>
            @foreach($events as $e)<option value="{{ $e->id }}">{{ $e->title }} · {{ $e->start_date?->format('d M Y') }}</option>@endforeach
          </select></div>
          <div class="field"><label>Target Amount (TZS)</label><input type="number" step="0.01" min="0" name="target_amount" id="editCardTarget"></div>
          <div class="field"><label>Background Color</label><input type="color" name="background_color" id="editCardBg"></div>
          <div class="field"><label>Accent Color</label><input type="color" name="accent_color" id="editCardAccent"></div>
          <div class="field full"><label>Contributor Note</label><input name="contributor_note" id="editCardNote"></div>
          <div class="field"><label>Button Text</label><input name="cta_text" id="editCardCta"></div>
          <div class="field full"><label>SMS Text (if blank, a default link message is used)</label><textarea name="sms_text" id="editCardSms"></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div class="drawer-overlay" id="cardSmsDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Send Card Link via SMS</h3><p id="smsCardTitle">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="" id="sendSmsForm">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Card Link</label><input type="text" id="smsCardUrl" readonly style="background:var(--blue-light);color:var(--blue-accent);font-weight:700"></div>
          <div class="field full"><label>Recipients — Name &amp; Phone</label><textarea name="phones" id="smsPhones" placeholder='John Doe, +255 7XX XXX XXX&#10;Jane Smith, +255 6XX XXX XXX&#10;+255 7XX XXX XXX'></textarea>
            <div class="text-muted" style="font-size:11px;margin-top:6px">One per line: <b>Name, Phone</b> (or a phone number alone). Each person gets a personalized link — their name and phone are pre-filled when they open the card.</div>
          </div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Send SMS</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function(){
  var typeColors = @json($typeColors);
  var typeAccents = @json($typeAccents);

  var newTypeEl = document.getElementById('newCardType');
  var newBgEl = document.getElementById('newCardBg');
  var newAccentEl = document.getElementById('newCardAccent');

  function applyTypeColors(type) {
    if (typeColors[type]) {
      newBgEl.value = typeColors[type];
      newAccentEl.value = typeAccents[type];
    }
  }

  if (newTypeEl) {
    applyTypeColors(newTypeEl.value);
    newTypeEl.addEventListener('change', function() { applyTypeColors(this.value); });
  }

  document.querySelectorAll('[data-edit-card]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('editCardTitle').value = d.title || '';
      document.getElementById('editCardMessage').value = d.message || '';
      document.getElementById('editCardType').value = d.type || 'general';
      document.getElementById('editCardEvent').value = d.event || '';
      document.getElementById('editCardTarget').value = d.target || '';
      document.getElementById('editCardBg').value = d.bg || '#1a237e';
      document.getElementById('editCardAccent').value = d.accent || '#ffd700';
      document.getElementById('editCardNote').value = d.note || '';
      document.getElementById('editCardCta').value = d.cta || 'Contribute Now';
      document.getElementById('editCardSms').value = d.sms || '';
      document.getElementById('editCardForm').action = "{{ url('/digital-cards') }}/" + d.id;
      openDrawerById('cardEditDrawer');
    });
  });

  document.querySelectorAll('[data-send-sms]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('smsCardTitle').textContent = d.title || '—';
      document.getElementById('smsCardUrl').value = d.url || '';
      document.getElementById('smsPhones').value = '';
      document.getElementById('sendSmsForm').action = "{{ url('/digital-cards') }}/" + d.id + "/send-sms";
      openDrawerById('cardSmsDrawer');
    });
  });

  var smsPhones = document.getElementById('smsPhones');
  if (smsPhones) {
    smsPhones.addEventListener('change', function(){
      this.value = this.value.split('\n').map(function(line){
        return line.replace(/,\s*$/, '').trim();
      }).join('\n');
    });
  }
})();
</script>
@endpush