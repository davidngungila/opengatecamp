<div class="glass-card">
  @foreach([['New member registered','20 Aug 2026'],['Payment recorded','17 Aug 2026'],['Event reminder sent','12 Aug 2026'],['System backup completed','08 Aug 2026']] as $n)
  <div class="mini-row">
    <div class="m-ico" style="background:var(--info-bg);color:var(--info)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></div>
    <div class="m-body"><p>{{ $n[0] }}</p><span>{{ $n[1] }}</span></div>
  </div>
  @endforeach
</div>
