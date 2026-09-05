@php
    $types = \App\Models\DigitalCard::types();
    $statuses = \App\Models\DigitalCard::statuses();
    $methodLabels = \App\Models\DigitalCardContribution::methods();
    $contributionStatuses = \App\Models\DigitalCardContribution::statuses();
@endphp
<div class="drawer-overlay" id="cardDetailDrawer">
  <div class="drawer-panel">

    <div class="drawer-head">
      <div>
        <h3>{{ $card->card_no }}</h3>
        <p style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
          <span class="badge badge-dotted" style="background:{{ $card->background_color }};color:#fff;border:1px solid {{ $card->accent_color }}">{{ $types[$card->card_type] ?? $card->card_type }}</span>
          <span class="badge badge-{{ $card->getStatusColor() }} badge-dotted">{{ $statuses[$card->status] ?? $card->status }}</span>
        </p>
      </div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>

    <div class="drawer-body" style="padding:18px 20px">

      <div style="background:{{ $card->background_color ?: '#1a237e' }};border:2px solid {{ $card->accent_color }};border-radius:14px;text-align:center;padding:18px 16px;margin-bottom:16px">
        <span class="badge badge-dotted" style="color:#fff;border-color:{{ $card->accent_color }};background:rgba(255,255,255,.08)">{{ $types[$card->card_type] ?? $card->card_type }}</span>
        @if($card->title)
        <div style="color:#fff;font-weight:800;font-size:19px;margin-top:8px;line-height:1.25">{{ $card->title }}</div>
        <div style="width:40px;height:2px;background:{{ $card->accent_color }};margin:9px auto"></div>
        @endif
        @if($card->message)
        <div style="color:rgba(255,255,255,.92);font-size:12.5px;line-height:1.65;margin-top:4px;white-space:pre-line">{{ $card->message }}</div>
        @endif
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
        <div class="cd-stat"><span>Confirmed Collections</span><b>TZS {{ number_format($confirmedTotal) }}</b></div>
        <div class="cd-stat"><span>Target</span><b>TZS {{ number_format($card->target_amount) }}</b></div>
        <div class="cd-stat"><span>Contributions</span><b>{{ number_format($contributions->count()) }}</b></div>
        <div class="cd-stat"><span>Recipients (SMS)</span><b>{{ number_format($recipients->count()) }}</b></div>
      </div>

      @if($card->target_amount > 0)
      <div style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;align-items:baseline;font-size:12px">
          <b>Campaign Progress</b>
          <span class="text-muted">{{ number_format($card->progress_percent, 1) }}%</span>
        </div>
        <div class="dp-track" style="background:#e2e8f0;margin-top:7px"><div class="dp-fill" style="width:{{ $card->progress_percent }}%;background:{{ $card->accent_color }};border-radius:10px"></div></div>
      </div>
      @endif

      <div class="cd-block">
        <div class="cd-title">Details</div>
        <div class="meta-list">
          <div class="meta-row"><span class="meta-k">Public URL</span><span class="meta-v"><a href="{{ route('cards.show', $card->hash) }}" target="_blank">{{ $card->public_url }}</a></span></div>
          <div class="meta-row"><span class="meta-k">Issued</span><span class="meta-v">{{ $card->created_at?->format('d M Y, H:i') }}</span></div>
          <div class="meta-row"><span class="meta-k">Created By</span><span class="meta-v">{{ $card->created_by ?: '—' }}</span></div>
          <div class="meta-row"><span class="meta-k">Accent</span><span class="meta-v"><span class="swatch" style="background:{{ $card->accent_color }}"></span>{{ $card->accent_color }}</span></div>
          <div class="meta-row"><span class="meta-k">Background</span><span class="meta-v"><span class="swatch" style="background:{{ $card->background_color }}"></span>{{ $card->background_color }}</span></div>
        </div>
      </div>

      <div class="cd-block">
        <div class="cd-title">Event &amp; Messaging</div>
        <div class="meta-list">
          <div class="meta-row"><span class="meta-k">Event</span><span class="meta-v">{{ \App\Models\Setting::get('event.name', 'Open Gate Camp') }}</span></div>
          <div class="meta-row"><span class="meta-k">Event Date</span><span class="meta-v">{{ \App\Models\Setting::get('event.start_date') ? \Carbon\Carbon::parse(\App\Models\Setting::get('event.start_date'))->format('d M Y') : '—' }}</span></div>
          <div class="meta-row"><span class="meta-k">Venue</span><span class="meta-v">{{ \App\Models\Setting::get('event.venue') ?: '—' }}</span></div>
          <div class="meta-row"><span class="meta-k">CTA Text</span><span class="meta-v">{{ $card->cta_text ?: '—' }}</span></div>
          <div class="meta-row"><span class="meta-k">Contributor Note</span><span class="meta-v">{{ $card->contributor_note ?: '—' }}</span></div>
        </div>
        @if($card->sms_text)
        <div style="margin-top:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px">
          <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:5px">SMS Template</div>
          <div style="font-size:12px;color:#334155;line-height:1.6;white-space:pre-wrap">{{ $card->sms_text }}</div>
        </div>
        @endif
      </div>

      <div class="cd-block">
        <div class="cd-title">Contributions ({{ number_format($contributions->count()) }})</div>
        <div class="table-scroll cd-table">
          <table class="data-table">
            <thead><tr><th>Date</th><th>Contributor</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              @forelse($contributions as $c)
              <tr>
                <td style="white-space:nowrap">{{ $c->created_at?->format('d M Y') }}</td>
                <td><div class="cu-name">{{ $c->contributor_name ?: '—' }}</div>@if($c->contributor_phone)<div class="cu-sub">{{ $c->contributor_phone }}</div>@endif</td>
                <td><span class="badge badge-neutral badge-dotted">{{ $methodLabels[$c->method] ?? $c->method }}</span></td>
                <td><b>TZS {{ number_format($c->amount) }}</b></td>
                <td><span class="badge badge-{{ $c->getStatusColor() }} badge-dotted">{{ $contributionStatuses[$c->status] ?? $c->status }}</span></td>
              </tr>
              @empty
              <tr><td colspan="5"><div class="cd-empty">No contributions yet</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="cd-block">
        <div class="cd-title">SMS Recipients ({{ number_format($recipients->count()) }})</div>
        <div class="table-scroll cd-table">
          <table class="data-table">
            <thead><tr><th>Name</th><th>Phone</th><th>Invite</th><th>Delivery</th><th>Sent At</th></tr></thead>
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
                    <button type="button" class="btn btn-sm btn-secondary" style="height:24px;padding:0 7px;font-size:10.5px" data-check-recipient-delivery data-id="{{ $r->id }}" data-mid="{{ $r->message_id }}" title="Check delivery via API">Check</button>
                    @endif
                  </div>
                </td>
                <td style="white-space:nowrap">{{ $r->sent_at?->format('d M Y H:i') ?: 'Not sent' }}</td>
              </tr>
              @empty
              <tr><td colspan="5"><div class="cd-empty">No SMS recipients yet</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <div class="drawer-foot">
      <button type="button" class="btn btn-outline" data-drawer-close>Close</button>
      <a class="btn btn-outline" href="{{ route('cards.show', $card->hash) }}" target="_blank">Public Page</a>
      <a class="btn btn-accent" href="{{ route('cards.pdf', $card) }}">Pakua PDF</a>
    </div>
  </div>
</div>

<style>
  .cd-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;}
  .cd-stat span{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:4px;}
  .cd-stat b{font-size:15px;font-weight:800;color:#0f172a;}
  .cd-block{margin-bottom:18px;}
  .cd-title{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:8px;}
  .cd-table{border:1px solid #e2e8f0;border-radius:12px;}
  .cd-table .data-table th{font-size:10.5px;}
  .cd-empty{text-align:center;color:#94a3b8;font-size:12.5px;padding:20px 0;font-weight:600;}
  .meta-list{display:flex;flex-direction:column;}
  .meta-row{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:12.5px;align-items:center;}
  .meta-row:last-child{border-bottom:none;}
  .meta-k{color:#94a3b8;font-weight:600;}
  .meta-v{color:#0f172a;font-weight:600;text-align:right;word-break:break-all;}
  .meta-v a{color:#2563eb;text-decoration:none;}
  .swatch{display:inline-block;width:13px;height:13px;border-radius:4px;border:1px solid rgba(0,0,0,.12);vertical-align:-2px;margin-right:8px;}
</style>