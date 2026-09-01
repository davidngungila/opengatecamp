@extends('layouts.app')
@section('title', 'Templates — Open Gate Camp Mission')
@section('crumb', 'Communication / Messaging / Templates')
@section('page_title', 'Message Templates')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <h2>Message Templates</h2>
  </div>
  @include('messaging._tabs', ['active' => 'templates'])

  <div class="msg-templates">
    @php
    $tpls = [
      ['Welcome Message', 'Hi {name}, welcome to Open Gate Camp Mission! We are glad to have you.', 'welcome'],
      ['Event Reminder', 'Reminder: {event} takes place on {date} at {venue}.', 'event'],
      ['Pledge Reminder', 'Dear {name}, your pledge balance for {campaign} is {balance}.', 'pledge'],
      ['Birthday Wish', 'Happy birthday {name}! May God bless you abundantly.', 'birthday'],
      ['Follow-up', 'Hi {name}, thank you for visiting us. We would love to see you again.', 'followup'],
      ['Thank You', 'Thank you {name} for your generous contribution of {amount}.', 'thankyou'],
      ['Mass Intention', 'Dear {name}, we have received your mass intention for {date}. Please keep us in your prayers.', 'mass'],
      ['Sacrament Reminder', 'Dear {name}, this is a reminder about your upcoming {sacrament} on {date}.', 'sacrament'],
    ];
    @endphp
    @foreach($tpls as $t)
    <div class="tpl-card">
      <h5>{{ $t[0] }}</h5>
      <p>{{ $t[1] }}</p>
      <div style="display:flex;gap:8px;margin-top:10px">
        <form method="POST" action="{{ route('messaging.use-template') }}" style="display:inline">
          @csrf
          <input type="hidden" name="template" value="{{ $t[1] }}">
          <input type="hidden" name="name" value="{{ $t[0] }}">
          <button type="submit" class="btn btn-ghost btn-sm" style="padding:6px 12px">Use in SMS</button>
        </form>
        <button type="button" class="btn btn-ghost btn-sm" style="padding:6px 12px;color:var(--blue-accent)" onclick="copyTemplate(this)" data-text="{{ $t[1] }}">Copy</button>
      </div>
    </div>
    @endforeach
  </div>
</div>

<script>
function copyTemplate(btn) {
  var text = btn.getAttribute('data-text');
  navigator.clipboard.writeText(text).then(function() {
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = 'Copy'; }, 1500);
  });
}
</script>
@endsection
