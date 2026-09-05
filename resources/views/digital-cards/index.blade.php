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
    @if(!$isCommittee)
    <button type="button" class="btn btn-accent" data-drawer-open="cardNewDrawer">+ New Card</button>
    @endif
  </div>

  <div class="table-card">
    <div class="empty-state" style="padding:56px 24px">
      <h3>Create a digital card for {{ $currentEventName }}</h3>
      <p>Cards are person invitations for the current event. After creating a card you invite people by their full name and phone number — each invitation is sent by SMS and its delivery status is tracked.</p>
      @if(!$isCommittee)
      <button type="button" class="btn btn-accent" data-drawer-open="cardNewDrawer">+ Create New Card</button>
      @endif
    </div>
  </div>

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
          <div class="field"><label>Background Color</label><input type="color" name="background_color" value="{{ old('background_color', '#1a237e') }}"></div>
          <div class="field"><label>Accent Color</label><input type="color" name="accent_color" value="{{ old('accent_color', '#ffd700') }}"></div>
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
@endif

@endsection