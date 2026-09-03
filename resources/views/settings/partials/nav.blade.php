<div class="settings-nav">
  <a href="{{ route('settings.page.general') }}" class="{{ ($active ?? '') === 'general' ? 'active' : '' }}">General / Church Profile</a>
  <a href="{{ route('settings.page.notifications') }}" class="{{ ($active ?? '') === 'notifications' ? 'active' : '' }}">Notifications</a>
  <a href="{{ route('settings.page.accounting') }}" class="{{ ($active ?? '') === 'accounting' ? 'active' : '' }}">Accounting</a>
  <a href="{{ route('settings.page.years') }}" class="{{ ($active ?? '') === 'financial-years' ? 'active' : '' }}">Financial Years</a>
  <a href="{{ route('settings.page.fellowships') }}" class="{{ ($active ?? '') === 'fellowships' ? 'active' : '' }}">University Fellowships</a>
  <a href="{{ route('settings.page.security') }}" class="{{ ($active ?? '') === 'security' ? 'active' : '' }}">Security</a>
  <a href="{{ route('settings.page.backup') }}" class="{{ ($active ?? '') === 'backup' ? 'active' : '' }}">Backup</a>
  <a href="{{ route('settings.page.audit') }}" class="{{ ($active ?? '') === 'audit' ? 'active' : '' }}">Audit Logs</a>
</div>
