{{-- Ticket PDF preview drawer — embedded iframe so tickets can be seen before printing --}}
<div class="drawer-overlay" id="ticketPrevDrawer">
  <div class="drawer-panel drawer-panel-lg">
    <div class="drawer-head">
      <div><h3 id="tpTitle">Ticket Preview</h3><p class="cu-sub" id="tpMeta" style="color:var(--text-tertiary);font-size:12px">—</p></div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="drawer-body ticket-prev-body">
      <iframe id="tpFrame" style="width:100%;flex:1;border:none;background:#fff" title="Ticket PDF"></iframe>
      <div class="tp-overlay" id="tpLoading"><span class="tp-spinner"></span><b>Loading ticket…</b></div>
    </div>
    <div class="drawer-foot">
      <a id="tpNewTab" class="btn btn-secondary" href="#" target="_blank">Open in New Tab</a>
      <a id="tpDownload" class="btn btn-secondary" href="#" style="display:none">Download PDF</a>
      <button type="button" class="btn btn-secondary" data-drawer-close>Close</button>
      <button type="button" class="btn btn-accent" onclick="tpPrint()">Print</button>
    </div>
  </div>
</div>

<style>
.ticket-prev-body{position:relative;display:flex;flex-direction:column;padding:0;background:#e8ecf3;}
.ticket-prev-body iframe{min-height:60vh;margin:0 auto;}
.tp-overlay{position:absolute;inset:0;display:flex;flex-direction:column;gap:10px;align-items:center;justify-content:center;background:rgba(232,236,243,.92);font-size:13px;color:var(--text-secondary);z-index:2;transition:opacity .2s;}
.tp-overlay.hidden{opacity:0;pointer-events:none;}
.tp-spinner{width:26px;height:26px;border-radius:50%;border:3px solid var(--border-strong);border-top-color:var(--blue-accent);animation:tpSpin .7s linear infinite;}
@keyframes tpSpin{to{transform:rotate(360deg);}}
</style>
<script>
function openTicketPreview(url, label){
  openPdfPreview(url, label, 'Ticket Preview', url);
}
function openPdfPreview(url, label, title, downloadUrl){
  var frame = document.getElementById('tpFrame');
  var overlay = document.getElementById('tpLoading');
  document.getElementById('tpTitle').textContent = title || 'Preview';
  document.getElementById('tpMeta').textContent = label || '';
  document.getElementById('tpNewTab').href = url;
  var dl = document.getElementById('tpDownload');
  if(downloadUrl){ dl.href = downloadUrl; dl.style.display = 'inline-flex'; }
  else { dl.style.display = 'none'; }
  frame.onload = function(){ overlay.classList.add('hidden'); };
  frame.src = url;
  overlay.classList.remove('hidden');
  openDrawerById('ticketPrevDrawer');
}
function tpPrint(){
  var frame = document.getElementById('tpFrame');
  try { frame.contentWindow.focus(); frame.contentWindow.print(); } catch(e){ window.open(frame.src, '_blank'); }
}
</script>