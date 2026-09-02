@php
    $tabs = [
        ['sms', 'SMS', route('messaging.sms')],
        ['email', 'Email', route('messaging.email')],
        ['notifications', 'Notifications', route('messaging.notifications')],
        ['history', 'History', route('messaging.history')],
        ['templates', 'Templates', route('messaging.templates')],
        ['settings', 'Settings', route('messaging.settings')],
    ];
    $active = $active ?? 'sms';
@endphp
<div class="tabs-bar">
    @foreach($tabs as [$key, $label, $url])
    <a href="{{ $url }}" class="tab-btn {{ $active === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>
