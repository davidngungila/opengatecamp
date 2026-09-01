@verbatim
<script>
function toast(msg, type, sub){
  var stack=document.getElementById('toastStack');
  var cfg={
    success:{bg:'var(--success-bg)',c:'var(--success)',ico:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>'},
    error:{bg:'var(--danger-bg)',c:'var(--danger)',ico:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'},
    info:{bg:'var(--info-bg)',c:'var(--info)',ico:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'},
    warning:{bg:'var(--warning-bg)',c:'var(--warning)',ico:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'}
  }[type]||{bg:'var(--info-bg)',c:'var(--info)',ico:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'};
  var el=document.createElement('div');
  el.className='toast';
  el.innerHTML='<div class="t-ico" style="background:'+cfg.bg+';color:'+cfg.c+'">'+cfg.ico+'</div><div><p>'+msg+'</p>'+(sub?'<span>'+sub+'</span>':'')+'</div>';
  stack.appendChild(el);
  setTimeout(function(){ el.classList.add('out'); setTimeout(function(){ el.remove(); },220); },4200);
}
</script>
@endverbatim
@if (session('success'))
@php $s = session('success'); @endphp
<script>document.addEventListener('DOMContentLoaded', function(){ toast({!! json_encode($s) !!}, 'success'); });</script>
@endif
@if (session('error'))
@php $e = session('error'); @endphp
<script>document.addEventListener('DOMContentLoaded', function(){ toast({!! json_encode($e) !!}, 'error'); });</script>
@endif
@verbatim
<script>
function toggleSidebarCollapse(){
  document.getElementById('sidebar').classList.toggle('collapsed');
  document.getElementById('mainWrap').classList.toggle('sidebar-collapsed');
}
function openMobileSidebar(){
  document.getElementById('sidebar').classList.add('mobile-open');
  document.getElementById('sidebarScrim').classList.add('open');
}
function closeMobileSidebar(){
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarScrim').classList.remove('open');
}
function onSidebarToggleClick(){
  if(window.innerWidth<=860){ openMobileSidebar(); } else { toggleSidebarCollapse(); }
}
function navChildrenOf(btn){
  var el=btn.parentElement.nextElementSibling;
  while(el && !el.classList.contains('nav-children')){ el=el.nextElementSibling; }
  return el;
}
function toggleNavGroup(btn){
  var children=navChildrenOf(btn);
  var wasOpen=btn.classList.contains('expanded');
  document.querySelectorAll('.sidebar .nav-parent.expanded').forEach(function(other){
    if(other===btn) return;
    other.classList.remove('expanded');
    var c=navChildrenOf(other);
    if(c) c.classList.remove('open');
  });
  btn.classList.toggle('expanded',!wasOpen);
  if(children) children.classList.toggle('open',!wasOpen);
}

function closeAllPanels(){ document.querySelectorAll('.dropdown-panel.open').forEach(function(p){p.classList.remove('open');}); }
function togglePanel(id){
  var el=document.getElementById(id);
  var isOpen=el.classList.contains('open');
  closeAllPanels();
  if(!isOpen) el.classList.add('open');
}
function toggleActionMenu(id){
  var el=document.getElementById(id);
  var isOpen=el.classList.contains('open');
  document.querySelectorAll('.action-menu.open').forEach(function(m){m.classList.remove('open');});
  if(!isOpen) el.classList.add('open');
}

function openModalById(id){
  var ov=document.getElementById(id);
  if(!ov) return;
  ov.classList.add('open');
}
function closeModalEl(el){ el.closest('.modal-overlay').classList.remove('open'); }

var __pendingForm=null;
function confirmAction(form, title, message, label){
  __pendingForm=form;
  var ov=document.getElementById('confirmModal');
  if(!ov){ form.submit(); return; }
  ov.querySelector('[data-confirm-title]').textContent=title||'Are you sure?';
  ov.querySelector('[data-confirm-message]').textContent=message||'This action cannot be undone.';
  ov.querySelector('[data-confirm-label]').textContent=label||'Confirm';
  openModalById('confirmModal');
}
document.addEventListener('submit', function(e){
  var form=e.target.closest('form[data-confirm]');
  if(!form || form===__pendingForm) return;
  if(window.__beforeConfirm && window.__beforeConfirm(form)===false){ e.preventDefault(); e.stopImmediatePropagation(); return; }
  e.preventDefault();
  confirmAction(form, form.dataset.confirmTitle, form.dataset.confirmMessage||form.dataset.confirm, form.dataset.confirmLabel);
}, true);
document.addEventListener('click', function(e){
  var btn=e.target.closest('[data-confirm-submit]');
  if(btn && __pendingForm){ e.preventDefault(); var f=__pendingForm; __pendingForm=null; f.submit(); }
});

document.addEventListener('click',function(e){
  var opener=e.target.closest('[data-modal-open]');
  if(opener){ e.preventDefault(); closeAllPanels(); openModalById(opener.getAttribute('data-modal-open')); return; }
  var closer=e.target.closest('[data-modal-close]');
  if(closer){ closeModalEl(closer); return; }
  var overlay=e.target.classList && e.target.classList.contains('modal-overlay') ? e.target : null;
  if(overlay) overlay.classList.remove('open');

  if(!e.target.closest('[data-panel-toggle]') && !e.target.closest('.dropdown-panel') && !e.target.closest('#globalSearch')){ closeAllPanels(); }
  if(!e.target.closest('.action-menu-wrap')){ document.querySelectorAll('.action-menu.open').forEach(function(m){m.classList.remove('open');}); }
});
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){
    document.querySelectorAll('.modal-overlay.open').forEach(function(m){m.classList.remove('open');});
    closeAllPanels();
  }
});

document.addEventListener('click',function(e){
  var tabBtn=e.target.closest('[data-tab-target]');
  if(!tabBtn) return;
  e.preventDefault();
  var bar=tabBtn.parentElement;
  bar.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('active');});
  tabBtn.classList.add('active');
  document.querySelectorAll('[data-tab-pane="'+tabBtn.dataset.tabGroup+'"]').forEach(function(p){p.classList.add('hidden');});
  var target=document.getElementById(tabBtn.dataset.tabTarget);
  if(target) target.classList.remove('hidden');
  var stepLbl=tabBtn.closest('.modal-box') ? tabBtn.closest('.modal-box').querySelector('.foot-left span') : null;
  if(stepLbl){ stepLbl.textContent=Array.prototype.indexOf.call(bar.querySelectorAll('.tab-btn'),tabBtn)+1; }
});

document.addEventListener('click',function(e){
  var attBtn=e.target.closest('.att-toggle button');
  if(!attBtn) return;
  Array.prototype.forEach.call(attBtn.parentElement.children,function(x){x.classList.remove('active');});
  attBtn.classList.add('active');
});

document.addEventListener('change',function(e){
  if(e.target.matches('input.select-all')){
    var tableId=e.target.dataset.table;
    document.querySelectorAll('input.row-check[data-table="'+tableId+'"]').forEach(function(cb){cb.checked=e.target.checked;});
  }
});
</script>
@endverbatim
