@extends('layouts.app')

@section('title', 'Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings')
@section('page_title', 'Settings')

@php
    $sections = [
        ['general', 'General / Church Profile'],
        ['notifications', 'Notifications'],
        ['accounting', 'Accounting'],
        ['financial-years', 'Financial Years'],
        ['fellowships', 'University Fellowships'],
        ['security', 'Security'],
        ['backup', 'Backup'],
        ['audit', 'Audit Logs'],
    ];
    $s = fn($key, $default = '') => old($key, \App\Models\Setting::get($key, $default));
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2></div>

  <div class="settings-layout">
    <div class="settings-nav">
      @foreach($sections as $sec)
        <a href="{{ route('settings.index', ['tab' => $sec[0]]) }}" class="{{ $tab===$sec[0] ? 'active' : '' }}">{{ $sec[1] }}</a>
      @endforeach
    </div>

    <div>
      @if($tab === 'general')
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 16px">Church Profile</h2>
        <form method="POST" action="{{ route('settings.general') }}">
          @csrf
          <div class="form-grid">
            <div class="field"><label>Organization Name *</label><input name="church_name" value="{{ $s('church.name') }}" required></div>
            <div class="field"><label>Event Name</label><input name="event_name" value="{{ $s('event.name', 'Open Gate Camp Season 3') }}" placeholder="Open Gate Camp Season 3"></div>
            <div class="field"><label>Chaplain *</label><input name="chaplain" value="{{ $s('church.chaplain') }}" required></div>
            <div class="field"><label>Phone</label><input name="church_phone" value="{{ $s('church.phone') }}"></div>
            <div class="field"><label>Email</label><input name="church_email" value="{{ $s('church.email') }}"></div>
            <div class="field"><label>Website</label><input name="church_website" value="{{ $s('church.website') }}"></div>
            <div class="field full"><label>Address</label><input name="church_address" value="{{ $s('church.address') }}"></div>
          </div>
          <div class="flex" style="justify-content:flex-end;margin-top:16px">
            <button type="submit" class="btn btn-accent">Save Changes</button>
          </div>
        </form>
      </div>

      @elseif($tab === 'notifications')
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 6px">Notification Preferences</h2>
        <form method="POST" action="{{ route('settings.notifications') }}">
          @csrf
          @foreach([['email','Email notifications'],['sms','SMS notifications'],['push','Push notifications'],['digest','Weekly digest email'],['payment_alerts','Payment alerts']] as [$key, $label])
          <div class="settings-row">
            <div class="sr-text"><p>{{ $label }}</p><span>Receive updates about this activity</span></div>
            <label class="switch"><input type="checkbox" name="{{ $key }}" {{ old($key, \App\Models\Setting::get("notify.$key")) === '1' ? 'checked' : '' }}><span class="slider"></span></label>
          </div>
          @endforeach
          <div class="flex" style="justify-content:flex-end;margin-top:16px">
            <button type="submit" class="btn btn-accent">Save Preferences</button>
          </div>
        </form>
      </div>

      @elseif($tab === 'accounting')
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 6px">Account Defaults for Automatic Double-Entry</h2>
        <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 14px">Pledge payments, attendee registration payments and attendee fee payments are posted automatically to the journal. Choose the cash / bank / mobile-money accounts (debit) and the income accounts (credit) used for each.</p>
        <form method="POST" action="{{ route('settings.accounting') }}">
          @csrf
          <div class="form-grid">
            <div class="field">
              <label>Cash on Hand (petty payments)</label>
              <select name="acct_default_cash">
                <option value="">— Select —</option>
                @foreach($cashAccounts as $a)
                <option value="{{ $a->code }}" @if($s('acct.default_cash')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label>Bank account (bank payments)</label>
              <select name="acct_default_bank">
                <option value="">— Select —</option>
                @foreach($cashAccounts as $a)
                <option value="{{ $a->code }}" @if($s('acct.default_bank')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label>Mobile money float (mobile payments)</label>
              <select name="acct_default_mobile">
                <option value="">— Select —</option>
                @foreach($cashAccounts as $a)
                <option value="{{ $a->code }}" @if($s('acct.default_mobile')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label>Pledge income account (credit)</label>
              <select name="acct_pledge_income">
                <option value="">— Select —</option>
                @foreach($incomeAccounts as $a)
                <option value="{{ $a->code }}" @if($s('acct.pledge_income')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label>Attendee fee income account (credit)</label>
              <select name="acct_attendee_income">
                <option value="">— Select —</option>
                @foreach($incomeAccounts as $a)
                <option value="{{ $a->code }}" @if($s('acct.attendee_income')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="flex" style="justify-content:flex-end;margin-top:16px">
            <button type="submit" class="btn btn-accent">Save Accounting Defaults</button>
          </div>
        </form>
      </div>

      @elseif($tab === 'financial-years')
      <div class="solid-card" style="margin-bottom:18px">
        <h2 style="font-size:14.5px;margin:0 0 6px">Define Financial Year</h2>
        <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 14px">The default financial year filters data across the whole system (use the selector in the top bar to switch).</p>
        <form method="POST" action="{{ route('settings.years.store') }}">
          @csrf
          <div class="form-grid">
            <div class="field"><label>Name *</label><input name="name" placeholder="e.g. FY {{ now()->year + 1 }}" required></div>
            <div class="field field-check" style="align-self:end;padding-bottom:10px">
              <input type="checkbox" class="checkbox" id="fy_default" name="is_default" value="1">
              <label for="fy_default">Set as default year</label>
            </div>
            <div class="field"><label>Start Date *</label><input type="date" name="start_date" required></div>
            <div class="field"><label>End Date *</label><input type="date" name="end_date" required></div>
          </div>
          <div class="flex" style="justify-content:flex-end;margin-top:8px">
            <button type="submit" class="btn btn-accent">Create Financial Year</button>
          </div>
        </form>
      </div>

      <div class="table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Name</th><th>Period</th><th>Status</th><th style="width:170px">Actions</th></tr></thead>
            <tbody>
              @forelse($years as $y)
              <tr>
                <td><b>{{ $y->name }}</b></td>
                <td>{{ $y->start_date->format('d M Y') }} â†’ {{ $y->end_date->format('d M Y') }}</td>
                <td>
                  @if($y->is_default)<span class="badge badge-success badge-dotted">Default</span>@endif
                  @if(session('fy_id')==$y->id)<span class="badge badge-info badge-dotted">Viewing</span>@endif
                  @if(! $y->is_default && session('fy_id')!=$y->id)<span class="badge badge-neutral badge-dotted">Available</span>@endif
                </td>
                <td>
                  <div class="flex gap-8 settings-actions-cell">
                    @if(! $y->is_default)
                    <a class="btn btn-secondary btn-sm" href="{{ route('settings.years.switch', $y->id) }}">Set Default</a>
                    @endif
                    @if(!$isCommittee)
                    <form method="POST" action="{{ route('settings.years.destroy', $y) }}"
                          data-confirm data-confirm-title="Delete this financial year?"
                          data-confirm-message="{{ $y->name }} will be permanently removed."
                          data-confirm-label="Delete Year">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">Delete</button>
                    </form>
                    @endif
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="4"><div class="empty-state"><h3>No financial years defined</h3><p>Create one above to enable system-wide filtering.</p></div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      @elseif($tab === 'fellowships')
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 6px">University Fellowships</h2>
        <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 12px">These fellowships are available as a selectable list at event registration. Enter one per line.</p>
        <form method="POST" action="{{ route('settings.fellowships') }}">
          @csrf
          <div class="field">
            <label>Fellowship List</label>
            <textarea name="fellowships" rows="10" placeholder="MoCU&#10;MWECAU&#10;KCMC University&#10;SMMUCo&#10;NM-AIST&#10;UoA&#10;TUMA&#10;ATC&#10;Other">{{ old('fellowships', implode("\n", $fellowships)) }}</textarea>
            <div class="field-hint">Each line becomes an option in the registration form's fellowship dropdown.</div>
          </div>
          <div class="flex" style="justify-content:flex-end;margin-top:16px">
            <button type="submit" class="btn btn-accent">Save Fellowship List</button>
          </div>
        </form>
      </div>

      @elseif($tab === 'security')
      <div class="solid-card" style="margin-bottom:18px">
        <h2 style="font-size:14.5px;margin:0 0 6px">Change Administrator Password</h2>
        <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 12px">Updates the password for the Super Administrator account.</p>
        <form method="POST" action="{{ route('settings.security') }}">
          @csrf
          <div class="form-grid">
            <div class="field full"><label>Current Password *</label><input type="password" name="current_password" required></div>
            <div class="field"><label>New Password *</label><input type="password" name="new_password" required minlength="8"></div>
            <div class="field"><label>Confirm New Password *</label><input type="password" name="new_password_confirmation" required minlength="8"></div>
          </div>
          <div class="flex" style="justify-content:flex-end;margin-top:12px">
            <button type="submit" class="btn btn-accent">Change Password</button>
          </div>
        </form>
      </div>
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 6px">Policies</h2>
        @foreach([['security.2fa','Two-Factor Authentication','Require a verification code at login'],['security.strong_password','Strong Password Policy','Minimum 8 characters with mixed case and numbers'],['security.auto_timeout','Auto Session Timeout','Log out automatically after 30 minutes idle']] as [$key,$title,$sub])
        <div class="settings-row">
          <div class="sr-text"><p>{{ $title }}</p><span>{{ $sub }}</span></div>
          <label class="switch"><input type="checkbox" checked disabled><span class="slider"></span></label>
        </div>
        @endforeach
      </div>

      @elseif($tab === 'backup')
      <div class="solid-card">
        <h2 style="font-size:14.5px;margin:0 0 6px">Backup &amp; Restore</h2>
        <div class="settings-row">
          <div class="sr-text"><p>Download Full Backup</p><span>All members, families, users, roles, settings and audit logs as JSON</span></div>
          <a class="btn btn-accent btn-sm" href="{{ route('settings.backup') }}">Backup Now</a>
        </div>
        <div class="settings-row">
          <div class="sr-text"><p>Last Action</p><span>Backups are downloaded instantly; keep them in a safe location</span></div>
          <span class="badge badge-info badge-dotted">JSON Export</span>
        </div>
      </div>

      @elseif($tab === 'audit')
      <div class="table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th><th>When</th></tr></thead>
            <tbody>
              @forelse($auditLogs as $log)
              <tr>
                <td>{{ $log->user_name }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->module ?? '—' }}</td>
                <td>{{ Str::limit($log->details ?? '—', 40) }}</td>
                <td>{{ $log->ip }}</td>
                <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
              </tr>
              @empty
              <tr><td colspan="6"><div class="empty-state"><h3>No activity yet</h3><p>System actions will be recorded here.</p></div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="table-footer">
          <span class="tf-info">Showing {{ $auditLogs->firstItem() ?? 0 }}“{{ $auditLogs->lastItem() ?? 0 }} of {{ $auditLogs->total() }} entries</span>
          <div class="flex gap-8 settings-actions-cell" style="align-items:center">
            {{ $auditLogs->links() }}
            @if(!$isCommittee)
            <form method="POST" action="{{ route('settings.audit.clear') }}"
                  data-confirm data-confirm-title="Clear audit log?"
                  data-confirm-message="All recorded activity will be permanently removed."
                  data-confirm-label="Clear Log">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm">Clear Log</button>
            </form>
            @endif
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
