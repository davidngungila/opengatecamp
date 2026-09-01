<div class="glass-card" style="max-width:600px">
  <h2 style="font-size:14.5px;margin:0 0 18px">SMS API Settings</h2>
  <p style="color:var(--text-muted);font-size:13px;margin-bottom:18px">
    Configure your <b>messaging-service.co.tz</b> API credentials. Your token is stored securely in the database.
  </p>
  <form method="POST" action="{{ route('messaging.token') }}">
    @csrf
    <div class="form-grid">
      <div class="field full">
        <label>API Token *</label>
        <input name="api_token" required value="{{ $smsToken }}" placeholder="Paste your API token here" style="font-family:monospace;font-size:13px">
      </div>
      <div class="field full">
        <label>Sender ID</label>
        <input name="sender_id" value="{{ $smsSenderId }}" placeholder="e.g. TMCS MoCU">
        <small style="color:var(--text-muted);margin-top:4px;display:block">Registered sender ID on the SMS gateway. Default: TMCS MoCU</small>
      </div>
    </div>
    <div style="margin-top:16px">
      <button type="submit" class="btn btn-accent">Save Settings</button>
    </div>
  </form>
</div>
