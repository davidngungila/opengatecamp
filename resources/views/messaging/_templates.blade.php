<div class="msg-templates">
  @foreach([['Welcome Message','Hi {name}, welcome to Open Gate Camp Mission! We are glad to have you.'],['Event Reminder','Reminder: {event} takes place on {date} at {venue}.'],['Pledge Reminder','Dear {name}, your pledge balance for {campaign} is {balance}.'],['Birthday Wish','Happy birthday {name}! May God bless you abundantly.'],['Follow-up','Hi {name}, thank you for visiting us. We would love to see you again.'],['Thank You','Thank you {name} for your generous contribution of {amount}.']] as $t)
  <div class="tpl-card">
    <h5>{{ $t[0] }}</h5>
    <p>{{ $t[1] }}</p>
    <a class="btn btn-ghost btn-sm" style="margin-top:10px;padding:6px 0" href="{{ route('messaging.sms') }}">Use Template</a>
  </div>
  @endforeach
</div>
