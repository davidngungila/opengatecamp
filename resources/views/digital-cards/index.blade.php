@extends('layouts.app')

@section('title', 'Digital Cards — OpenGate Camp Connect')
@section('crumb', 'Giving / Digital Cards')
@section('page_title', 'Digital Cards')

@section('content')
@php
    $heroBg = $primaryCard?->background_color ?: '#1a237e';
    $heroAccent = $primaryCard?->accent_color ?: '#ffd700';
    $heroTitle = $primaryCard?->title ?: $currentEventName;
    $methodLabels = $methodLabels ?? \App\Models\DigitalCardContribution::methods();
    $contributionStatuses = $contributionStatuses ?? \App\Models\DigitalCardContribution::statuses();
@endphp
<div class="fade-in">

  <div class="section-head">
    <div><h2>Digital Cards</h2><div class="sub">
      {{ count($eventCards) }} {{ Str::plural('card', count($eventCards)) }}
      · {{ $currentEventName }}
      · TZS {{ number_format($confirmedTotal) }} collected
    </div></div>
    @if(!$isCommittee)
    <button type="button" class="btn btn-accent" data-drawer-open="cardNewDrawer">+ New Card</button>
    @endif
  </div>

  <div class="toolbar" style="flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="badge badge-info badge-dotted">Current Event</span>
      <b>{{ $currentEventName }}</b>
      @if($currentEventDate)<span class="text-muted">· {{ \Carbon\Carbon::parse($currentEventDate)->format('d M Y') }}</span>@endif
      @if($currentEventVenue)<span class="text-muted">· {{ $currentEventVenue }}</span>@endif
    </div>
    <div class="tfield-grow" style="color:var(--text-tertiary);display:flex;align-items:center;font-size:12.5px;font-weight:600">
      This system manages a single event. Pending / failed previews are excluded — only confirmed contributions count toward the campaign.
    </div>
  </div>

  @if($eventCards->isEmpty())
  <div class="table-card">
    <div class="empty-state" style="padding:48px 20px">
      <h3>No digital cards for this event yet</h3>
      <p>Create a card so people can contribute and receive personalized invites.</p>
      @if(!$isCommittee)
      <button type="button" class="btn btn-accent" data-drawer-open="cardNewDrawer">+ New Card</button>
      @endif
    </div>
  </div>
  @else

  <div class="camp-hero" style="--hero-bg:{{ $heroBg }};--hero-accent:{{ $heroAccent }}">
    <div class="camp-tag">GIVING CAMPAIGN</div>
    <div class="camp-title">{{ $heroTitle }}</div>
    <div class="camp-meta">
      @if($currentEventDate)<span>📅 {{ \Carbon\Carbon::parse($currentEventDate)->format('d M Y') }}</span>@endif
      @if($currentEventVenue)<span>📍 {{ $currentEventVenue }}</span>@endif
      <span>🖥 {{ count($eventCards) }} card{{ count($eventCards) === 1 ? '' : 's' }}</span>
    </div>
    <div class="camp-numbers">
      <div class="camp-num"><span>Raised</span><b>TZS {{ number_format($confirmedTotal) }}</b></div>
      <div class="camp-num"><span>Target</span><b>TZS {{ number_format($targetTotal) }}</b></div>
      <div class="camp-num"><span>Contributions</span><b>{{ number_format($contributions->count()) }}</b></div>
      <div class="camp-num"><span>Recipients</span><b>{{ number_format($recipients->count()) }}</b></div>
    </div>
    @if($targetTotal > 0)
    <div class="camp-progress">
      <div class="camp-pbar"><div class="camp-pfill" style="width:{{ min(100, $progressPercent) }}%"></div></div>
      <div class="camp-plabel">{{ number_format($progressPercent, 1) }}% of goal reached</div>
    </div>
    @endif
    <div class="camp-actions">
      @if(!$isCommittee)
      <button type="button" class="btn camp-btn camp-btn-solid" data-drawer-open="contributionDrawer">+ Add Contribution</button>
      @if($primaryCard)
      <button type="button" class="btn camp-btn camp-btn-ghost" data-send-sms data-id="{{ $primaryCard->id }}" data-title="{{ $primaryCard->title }}" data-url="{{ $primaryCard->public_url }}">Invite via SMS</button>
      @endif
      @endif
      @if($primaryCard)
      <a class="btn camp-btn camp-btn-ghost" href="{{ route('cards.preview', $primaryCard) }}" target="_blank">Preview</a>
      <a class="btn camp-btn camp-btn-ghost" href="{{ route('cards.show', $primaryCard->hash) }}" target="_blank">Public Page</a>
      @endif
    </div>
  </div>

  <div class="table-card" style="margin-bottom:18px">
    <div class="table-head">
      <h3>Contributions ({{ number_format($contributions->count()) }})</h3>
      <div style="display:flex;align-items:center;gap:12px">
        <span class="tf-info">Confirmed: TZS {{ number_format($confirmedTotal) }}</span>
        @if(!$isCommittee)
        <button type="button" class="btn btn-accent btn-sm" data-drawer-open="contributionDrawer">+ Add</button>
        @endif
      </div>
    </div>
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Card</th><th>Contributor</th><th>Method</th><th>Reference</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          @forelse($contributions as $c)
          <tr>
            <td style="white-space:nowrap">{{ $c->created_at?->format('d M Y H:i') }}</td>
            <td><span class="badge badge-neutral badge-dotted">{{ $c->digitalCard?->card_no ?? '—' }}</span></td>
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:var(--blue-light);color:var(--blue-accent)">{{ mb_substr($c->contributor_name ?: '?', 0, 1) }}</div>
                <div><div class="cu-name">{{ $c->contributor_name ?: '—' }}</div>@if($c->contributor_phone)<div class="cu-sub">{{ $c->contributor_phone }}</div>@endif</div>
              </div>
            </td>
            <td><span class="badge badge-neutral badge-dotted">{{ $methodLabels[$c->method] ?? $c->method }}</span></td>
            <td>{{ $c->reference_no ?: '—' }}</td>
            <td><b>TZS {{ number_format($c->amount) }}</b></td>
            <td><span class="badge badge-{{ $c->getStatusColor() }} badge-dotted">{{ $contributionStatuses[$c->status] ?? $c->status }}</span></td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state"><h3>No contributions yet</h3><p>Add a contribution manually or share the card link to receive gifts.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="table-card" style="margin-bottom:18px">
    <div class="table-head">
      <h3>Recipients / Invites ({{ number_format($recipients->count()) }})</h3>
      @if(!$isCommittee && $primaryCard)
      <button type="button" class="btn btn-accent btn-sm" data-send-sms data-id="{{ $primaryCard->id }}" data-title="{{ $primaryCard->title }}" data-url="{{ $primaryCard->public_url }}">Invite via SMS</button>
      @endif
    </div>
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
            <td><a href="{{ route('cards.show', $r->digitalCard?->hash).'?r='.$r->token }}" target="_blank" class="link-mono">{{ substr($r->token, 0, 12) }}…</a></td>
            <td>{{ $r->sent_at?->format('d M Y H:i') ?: 'Not sent' }}</td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="empty-state"><h3>No recipients yet</h3><p>Invite people via SMS — each person gets a personalised card link.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="table-card">
    <div class="table-head"><h3>Person Invitation Cards ({{ count($eventCards) }})</h3></div>
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Card No</th><th>Title / Type</th><th>Collected</th><th>Target</th><th>Progress</th><th>Status</th><th style="width:60px">Actions</th></tr></thead>
        <tbody>
          @foreach($eventCards as $card)
          <tr data-card-url="{{ route('cards.details', $card) }}">
            <td><span class="badge badge-neutral badge-dotted">{{ $card->card_no }}</span></td>
            <td>
              <div class="cell-user">
                <div class="cell-avatar" style="background:{{ $card->background_color }};color:{{ $card->accent_color }};font-weight:800">{{ mb_substr($card->title ?: $card->card_no, 0, 1) }}</div>
                <div><div class="cu-name">{{ $card->title ?: '—' }}</div><div class="cu-sub">
                  <span class="badge badge-{{ $card->getTypeColor() }} badge-dotted">{{ $types[$card->card_type] ?? $card->card_type }}</span>
                </div></div>
              </div>
            </td>
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
            <td><span class="badge badge-{{ $card->getStatusColor() }} badge-dotted">{{ $statuses[$card->status] ?? $card->status }}</span></td>
            <td onclick="event.stopPropagation()">
              <div class="action-menu-wrap">
                <button type="button" class="action-trigger" onclick="toggleActionMenu('am-card-{{ $card->id }}')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="5" r=".6"/><circle cx="12" cy="12" r=".6"/><circle cx="12" cy="19" r=".6"/></svg>
                </button>
                <div class="action-menu" id="am-card-{{ $card->id }}">
                  <button type="button" data-card-details data-id="{{ $card->id }}">View Details</button>
                  <a href="{{ route('cards.preview', $card) }}" target="_blank">Preview</a>
                  @if(!$isCommittee)
                  <a href="{{ route('cards.pdf', $card) }}">Download PDF</a>
                  <button type="button" data-edit-card data-id="{{ $card->id }}" data-title="{{ $card->title }}" data-message="{{ $card->message }}" data-type="{{ $card->card_type }}" data-bg="{{ $card->background_color }}" data-accent="{{ $card->accent_color }}" data-event="{{ $card->event_id }}" data-target="{{ $card->target_amount }}" data-note="{{ $card->contributor_note }}" data-cta="{{ $card->cta_text }}" data-sms="{{ $card->sms_text }}">Edit</button>
                  <button type="button" data-send-sms data-id="{{ $card->id }}" data-title="{{ $card->title }}" data-url="{{ $card->public_url }}">Send via SMS</button>
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
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  @endif

  <div id="cardDetailHolder"></div>
</div>

@if(!$isCommittee)
<div class="drawer-overlay" id="cardNewDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Create Digital Card</h3><p>Person invitation card · {{ $currentEventName }}</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('cards.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Title</label><input name="title" placeholder="e.g. Open Gate Camp Season 3" value="{{ old('title') }}"></div>
          <div class="field"><label>Target Amount (TZS)</label><input type="number" step="0.01" min="0" name="target_amount" value="{{ old('target_amount') }}" placeholder="e.g. 500000"></div>
          <div class="field full"><label>Message</label><textarea name="message" placeholder="Write a heartfelt message for your recipients..." value="{{ old('message') }}"></textarea></div>
          <div class="field"><label>Background Color</label><input type="color" name="background_color" id="newCardBg" value="{{ old('background_color', '#1a237e') }}"></div>
          <div class="field"><label>Accent Color</label><input type="color" name="accent_color" id="newCardAccent" value="{{ old('accent_color', '#ffd700') }}"></div>
          <div class="field full"><label>Background Image (optional — replaces the color on the card)</label><input type="file" name="image_path" accept="image/*"></div>
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
    <form method="POST" action="" id="editCardForm" enctype="multipart/form-data">
      @csrf @method('PUT')
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Title</label><input name="title" id="editCardTitle" placeholder="Optional — leave blank to hide"></div>
          <div class="field"><label>Target Amount (TZS)</label><input type="number" step="0.01" min="0" name="target_amount" id="editCardTarget"></div>
          <div class="field full"><label>Message</label><textarea name="message" id="editCardMessage" placeholder="Optional — leave blank to hide"></textarea></div>
          <div class="field"><label>Background Color</label><input type="color" name="background_color" id="editCardBg"></div>
          <div class="field"><label>Accent Color</label><input type="color" name="accent_color" id="editCardAccent"></div>
          <div class="field full"><label>Background Image (optional — replaces the color on the card)</label><input type="file" name="image_path" accept="image/*" id="editCardImage">
            <div style="display:flex;align-items:center;gap:8px;margin-top:8px"><input type="checkbox" name="remove_image" value="1" id="editCardRemoveImage"><label for="editCardRemoveImage" style="margin:0;font-size:12px;color:var(--text-secondary)">Remove current image</label></div>
          </div>
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

<div class="drawer-overlay" id="contributionDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div><h3>Add Contribution</h3><p>Record a gift for @if($primaryCard){{ $primaryCard->card_no }}@endif</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="@if($primaryCard){{ route('cards.addContribution', $primaryCard) }}@endif">
      @csrf
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field"><label>Full Name *</label><input name="contributor_name" value="{{ old('contributor_name') }}" required></div>
          <div class="field"><label>Phone</label><input name="contributor_phone" placeholder="+255 7XX XXX XXX" value="{{ old('contributor_phone') }}"></div>
          <div class="field full"><label>Email</label><input type="email" name="contributor_email" value="{{ old('contributor_email') }}"></div>
          <div class="field"><label>Amount (TZS) *</label><input type="number" step="0.01" min="100" name="amount" value="{{ old('amount') }}" required></div>
          <div class="field"><label>Payment Method *</label><select name="method" required>
            @foreach($methodLabels as $k=>$m)<option value="{{ $k }}" @if(old('method')==$k) selected @endif>{{ $m }}</option>@endforeach
          </select></div>
          <div class="field full"><label>Reference / Txn No</label><input name="reference_no" value="{{ old('reference_no') }}"></div>
          <div class="field full"><label>Note</label><textarea name="note" placeholder="Optional message">{{ old('note') }}</textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent">Record Contribution</button>
      </div>
    </form>
  </div>
</div>

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

<style>
  .camp-hero{
    border-radius:20px;padding:28px 28px 24px;margin-bottom:18px;color:#fff;
    background:
      radial-gradient(900px 320px at 88% -20%, rgba(255,255,255,.14), transparent 55%),
      linear-gradient(135deg, var(--hero-bg), #0f172a 88%);
    border:1px solid rgba(255,255,255,.08);
    box-shadow:0 18px 44px rgba(0,0,0,.18);
    position:relative;overflow:hidden;
  }
  .camp-tag{font-size:11px;font-weight:800;letter-spacing:3px;text-transform:uppercase;color:var(--hero-accent);margin-bottom:6px;}
  .camp-title{font-size:clamp(22px,3vw,32px);font-weight:800;letter-spacing:.3px;line-height:1.2;}
  .camp-meta{display:flex;gap:16px;flex-wrap:wrap;font-size:12.5px;color:rgba(255,255,255,.72);font-weight:600;margin-top:6px;}
  .camp-numbers{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:18px 0 0;}
  .camp-num{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:12px 14px;}
  .camp-num span{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.6);}
  .camp-num b{font-size:17px;font-weight:800;color:#fff;}
  .camp-progress{margin-top:16px;}
  .camp-pbar{height:10px;border-radius:999px;background:rgba(255,255,255,.18);overflow:hidden;}
  .camp-pfill{height:100%;border-radius:999px;background:var(--hero-accent);transition:width .5s ease;}
  .camp-plabel{font-size:11.5px;color:rgba(255,255,255,.72);font-weight:700;margin-top:6px;}
  .camp-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;}
  .camp-btn{padding:10px 18px;font-size:13px;font-weight:700;border-radius:12px;text-decoration:none;}
  .camp-btn-solid{background:var(--hero-accent);color:#0a0f1e;border:none;}
  .camp-btn-ghost{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.22);}
  .camp-btn-ghost:hover{background:rgba(255,255,255,.18);}
  .table-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px 0;}
  .link-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;}
</style>
@endsection

@push('scripts')
<script>
(function(){
  document.querySelectorAll('[data-edit-card]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var d = btn.dataset;
      document.getElementById('editCardTitle').value = d.title || '';
      document.getElementById('editCardMessage').value = d.message || '';
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
    sendSmsForm.addEventListener('submit', function(){
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

  document.querySelectorAll('tr[data-card-url]').forEach(function(tr){
    tr.style.cursor = 'pointer';
    tr.addEventListener('click', function(e){
      if (e.target.closest('.action-menu-wrap') || e.target.closest('button') || e.target.closest('a')) return;
      openCardDetails(tr.dataset.cardUrl);
    });
  });

  function openCardDetails(url){
    var holder = document.getElementById('cardDetailHolder');
    if(!holder) return;
    fetch(url + (url.indexOf('?') > -1 ? '&' : '?') + 'drawer=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r){ return r.text(); })
      .then(function(html){ holder.innerHTML = html; openDrawerById('cardDetailDrawer'); })
      .catch(function(){});
  }

  document.querySelectorAll('[data-card-details]').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      openCardDetails("{{ url('/digital-cards') }}/" + btn.dataset.id);
    });
  });
})();
</script>
@endpush