<style>
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px;}
.kpi-card{
  background:var(--glass-bg);backdrop-filter:blur(16px);border:1px solid var(--glass-border);
  border-radius:var(--radius-lg);box-shadow:var(--shadow-glass);padding:20px;position:relative;overflow:hidden;
  transition:transform .2s, box-shadow .2s;
}
.kpi-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg);}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;}
.kpi-icon{width:44px;height:44px;border-radius:13px;display:flex;align-items:center;justify-content:center;}
.kpi-trend{font-size:11.5px;font-weight:800;padding:4px 8px;border-radius:8px;display:flex;align-items:center;gap:3px;}
.kpi-trend.up{background:var(--success-bg);color:var(--success);}
.kpi-trend.down{background:var(--danger-bg);color:var(--danger);}
.kpi-value{font-size:26px;font-weight:800;margin:0 0 2px;letter-spacing:-.5px;}
.kpi-label{font-size:12.5px;color:var(--text-secondary);font-weight:600;margin-bottom:10px;}
.kpi-sub{font-size:11px;color:var(--text-tertiary);font-weight:600;}
table.mini-table{width:100%;border-collapse:collapse;min-width:0;}
table.mini-table thead th{text-align:left;font-size:10.5px;text-transform:uppercase;letter-spacing:.6px;color:var(--text-tertiary);padding:8px 10px;border-bottom:1px solid var(--border-strong);}
table.mini-table tbody td{padding:9px 10px;border-bottom:1px solid var(--border);font-size:12.5px;color:var(--text-primary);}
table.mini-table tbody tr:last-child td{border-bottom:none;}
.spark{height:32px;width:100%;display:block;}
.spark-box{position:relative;height:32px;width:100%;}

.two-col{display:grid;grid-template-columns:1.6fr 1fr;gap:20px;margin-bottom:22px;align-items:start;}
.chart-tabs{display:flex;gap:6px;background:rgba(15,23,42,.05);padding:4px;border-radius:10px;}
.chart-tabs button{border:none;background:transparent;padding:6px 12px;border-radius:7px;font-size:12px;font-weight:700;color:var(--text-secondary);}
.chart-tabs button.active{background:var(--white);color:var(--blue-accent);box-shadow:var(--shadow-sm);}
.chart-wrap{height:280px;position:relative;}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:22px;align-items:start;}
@media (max-width:1180px){
  .grid-3{grid-template-columns:1fr 1fr;}
}
@media (max-width:860px){
  .grid-3{grid-template-columns:1fr;}
}

.quick-actions-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;}
.qa-btn{
  display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 8px;border-radius:14px;
  border:1px solid var(--border);background:rgba(255,255,255,.6);transition:.18s;text-align:center;
}
.qa-btn:hover{background:var(--white);border-color:rgba(37,99,235,.3);transform:translateY(-2px);box-shadow:var(--shadow-md);}
.qa-btn .qa-ico{width:42px;height:42px;border-radius:12px;background:var(--blue-light);color:var(--blue-accent);display:flex;align-items:center;justify-content:center;}
.qa-btn span{font-size:12px;font-weight:700;color:var(--text-primary);}

.list-card{margin-bottom:0;}
.mini-row{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--border);}
.mini-row:last-child{border-bottom:none;}
.mini-row .m-ico{width:38px;height:38px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.mini-row .m-body{flex:1;min-width:0;}
.mini-row .m-body p{margin:0;font-size:13px;font-weight:700;color:var(--text-primary);}
.mini-row .m-body span{font-size:11.5px;color:var(--text-tertiary);font-weight:600;}

.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:10px 18px;border-radius:11px;font-size:13.5px;font-weight:700;border:1px solid transparent;
  transition:.15s;white-space:nowrap;
}
.btn-primary{background:var(--navy-900);color:#fff;box-shadow:0 4px 14px rgba(11,31,58,.25);}
.btn-primary:hover{background:var(--navy-800);transform:translateY(-1px);}
.btn-accent{background:var(--blue-accent);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3);}
.btn-accent:hover{background:var(--blue-accent-dark);transform:translateY(-1px);}
.btn-secondary{background:var(--white);color:var(--text-primary);border-color:var(--border-strong);}
.btn-secondary:hover{background:var(--blue-light);border-color:rgba(37,99,235,.3);}
.btn-danger{background:var(--danger);color:#fff;}
.btn-danger:hover{background:#B91C1C;}
.btn-ghost{background:transparent;color:var(--text-secondary);}
.btn-ghost:hover{background:rgba(15,23,42,.05);color:var(--text-primary);}
.btn-sm{padding:7px 12px;font-size:12.5px;border-radius:9px;}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none !important;}

.toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
.toolbar .grow{flex:1;min-width:180px;}
.tfield{position:relative;}
.tfield input, .tfield select{
  width:100%;height:40px;border-radius:11px;border:1px solid var(--border);background:var(--white);
  padding:0 14px 0 38px;font-size:13px;font-weight:600;color:var(--text-primary);
}
.tfield.no-icon input,.tfield.no-icon select{padding-left:14px;}
.tfield svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);}
.filter-select{height:40px;border-radius:11px;border:1px solid var(--border);background:var(--white);padding:0 12px;font-size:13px;font-weight:600;color:var(--text-secondary);min-width:130px;}

.table-card{background:var(--glass-bg);backdrop-filter:blur(16px);border:1px solid var(--glass-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-glass);overflow:hidden;}
.table-scroll{overflow-x:auto;}
table.data-table{width:100%;border-collapse:collapse;min-width:760px;}
.data-table thead th{
  text-align:left;font-size:11px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;
  color:var(--text-tertiary);padding:14px 18px;border-bottom:1px solid var(--border);white-space:nowrap;background:rgba(255,255,255,.4);
  cursor:pointer;user-select:none;
}
.data-table thead th:hover{color:var(--blue-accent);}
.data-table tbody td{padding:13px 18px;border-bottom:1px solid var(--border);font-size:13.5px;color:var(--text-primary);vertical-align:middle;}
.data-table tbody tr{transition:background .12s;}
.data-table tbody tr:hover{background:rgba(37,99,235,.035);}
.data-table tbody tr:last-child td{border-bottom:none;}
.cell-user{display:flex;align-items:center;gap:10px;}
.cell-avatar{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--navy-800),var(--blue-accent));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0;}
.cell-user .cu-name{font-weight:700;color:var(--text-primary);font-size:13.5px;}
.cell-user .cu-sub{font-size:11.5px;color:var(--text-tertiary);font-weight:600;}
.checkbox{width:17px;height:17px;border-radius:5px;accent-color:var(--blue-accent);cursor:pointer;}

.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:700;}
.badge-success{background:var(--success-bg);color:var(--success);}
.badge-warning{background:var(--warning-bg);color:var(--warning);}
.badge-danger{background:var(--danger-bg);color:var(--danger);}
.badge-info{background:var(--info-bg);color:var(--info);}
.badge-purple{background:var(--purple-bg);color:var(--purple);}
.badge-neutral{background:rgba(15,23,42,.06);color:var(--text-secondary);}
.badge-dotted::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;}

.action-menu-wrap{position:relative;display:inline-block;}
.action-trigger{width:32px;height:32px;border-radius:8px;border:1px solid transparent;background:transparent;display:flex;align-items:center;justify-content:center;color:var(--text-tertiary);}
.action-trigger:hover{background:rgba(15,23,42,.06);color:var(--text-primary);}
.action-menu{
  position:absolute;right:0;top:calc(100% + 4px);min-width:170px;background:var(--glass-bg-solid);
  backdrop-filter:blur(18px);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-lg);
  z-index:70;overflow:hidden;display:none;
}
.action-menu.open{display:block;}
.action-menu button,.action-menu a{width:100%;text-align:left;display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:13px;font-weight:600;background:none;border:none;color:var(--text-primary);}
.action-menu button:hover,.action-menu a:hover{background:rgba(37,99,235,.07);color:var(--blue-accent);}
.action-menu button.danger:hover{background:var(--danger-bg);color:var(--danger);}

.table-footer{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px;}
.table-footer .tf-info{font-size:12.5px;color:var(--text-tertiary);font-weight:600;}
.pagination{display:flex;align-items:center;gap:6px;}
.pagination nav ul{display:flex;gap:6px;list-style:none;margin:0;padding:0;flex-wrap:wrap;}
.pagination nav ul li a,.pagination nav ul li span{min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0 10px;border-radius:8px;border:1px solid var(--border);background:var(--white);font-size:12.5px;font-weight:700;color:var(--text-secondary);text-decoration:none;}
.pagination nav ul li span{opacity:.55;}
.pagination nav ul li.active span{background:var(--navy-900);color:#fff;border-color:var(--navy-900);opacity:1;}
.page-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:var(--white);font-size:12.5px;font-weight:700;color:var(--text-secondary);}
.page-btn.active{background:var(--navy-900);color:#fff;border-color:var(--navy-900);}
.page-btn:disabled{opacity:.4;}
.bulk-bar{display:flex;align-items:center;gap:12px;padding:10px 18px;background:var(--blue-light);border-bottom:1px solid rgba(37,99,235,.15);font-size:13px;font-weight:700;color:var(--navy-900);}

.empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:70px 24px;}
.empty-state .es-ico{width:72px;height:72px;border-radius:20px;background:var(--blue-light);color:var(--blue-accent);display:flex;align-items:center;justify-content:center;margin-bottom:18px;}
.empty-state h3{font-size:16px;font-weight:800;margin:0 0 6px;}
.empty-state p{font-size:13.5px;color:var(--text-secondary);margin:0 0 18px;max-width:340px;}

.progress-track{height:9px;border-radius:20px;background:rgba(15,23,42,.07);overflow:hidden;}
.progress-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,var(--blue-accent),#4C86F5);transition:width .5s ease;}

.tabs-bar{display:flex;gap:4px;border-bottom:1px solid var(--border);overflow-x:auto;margin-bottom:18px;}
.tab-btn{padding:11px 16px;font-size:13px;font-weight:700;color:var(--text-tertiary);border:none;background:none;border-bottom:2px solid transparent;white-space:nowrap;display:inline-block;}
.tab-btn:hover{color:var(--text-primary);}
.tab-btn.active{color:var(--blue-accent);border-bottom-color:var(--blue-accent);}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-grid .full{grid-column:1 / -1;}
.field label{display:block;font-size:12.5px;font-weight:700;color:var(--text-secondary);margin-bottom:6px;}
.field input,.field select,.field textarea{
  width:100%;padding:10px 13px;border-radius:10px;border:1px solid var(--border-strong);
  font-size:13.5px;font-weight:600;color:var(--text-primary);background:var(--white);transition:.15s;
}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--blue-accent);box-shadow:0 0 0 3px rgba(37,99,235,.12);}
.field textarea{resize:vertical;min-height:80px;font-weight:500;}
.field .hint{font-size:11px;color:var(--text-tertiary);margin-top:5px;font-weight:500;}
.field-check{display:flex;align-items:center;gap:9px;}
.field-check label{margin:0;font-size:13px;font-weight:600;color:var(--text-primary);}
.upload-box{border:1.5px dashed var(--border-strong);border-radius:14px;padding:26px;text-align:center;background:rgba(37,99,235,.02);}
.upload-box svg{color:var(--blue-accent);margin-bottom:8px;display:inline-block;}
.upload-box p{margin:0 0 3px;font-size:13px;font-weight:700;}
.upload-box span{font-size:11.5px;color:var(--text-tertiary);}
.stepper-mini{display:flex;align-items:center;gap:6px;margin-bottom:20px;}
.step-dot{flex:1;height:5px;border-radius:10px;background:rgba(15,23,42,.08);}
.step-dot.active{background:var(--blue-accent);}

.modal-overlay{
  position:fixed;inset:0;background:rgba(11,20,38,.55);backdrop-filter:blur(3px);
  display:flex;align-items:center;justify-content:center;z-index:200;padding:24px;
  opacity:0;pointer-events:none;transition:opacity .2s ease;
}
.modal-overlay.open{opacity:1;pointer-events:auto;}
.modal-box{
  background:var(--white);border-radius:var(--radius-xl);box-shadow:0 30px 80px rgba(0,0,0,.28);
  width:100%;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;
  transform:translateY(16px) scale(.98);transition:transform .22s cubic-bezier(.2,.8,.3,1);
}
.modal-overlay.open .modal-box{transform:translateY(0) scale(1);}
.modal-box.sm{max-width:420px;}
.modal-box.md{max-width:560px;}
.modal-box.lg{max-width:820px;}
.modal-box.full{max-width:96vw;width:1200px;max-height:94vh;}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border);flex-shrink:0;}
.modal-head h3{font-size:17px;font-weight:800;margin:0;}
.modal-head p{margin:2px 0 0;font-size:12.5px;color:var(--text-tertiary);font-weight:600;}
.modal-close{width:34px;height:34px;border-radius:10px;border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--text-secondary);flex-shrink:0;}
.modal-close:hover{background:var(--danger-bg);color:var(--danger);border-color:transparent;}
.modal-body{padding:24px;overflow-y:auto;flex:1;min-height:0;}
.modal-box form{display:flex;flex-direction:column;min-height:0;flex:1;overflow:hidden;}
.modal-foot{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--border);flex-shrink:0;background:rgba(248,250,252,.6);}
.modal-foot .foot-left{margin-right:auto;font-size:12px;color:var(--text-tertiary);font-weight:600;}
.confirm-icon{width:56px;height:56px;border-radius:16px;background:var(--danger-bg);color:var(--danger);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}

.toast-stack{position:fixed;bottom:24px;right:24px;z-index:300;display:flex;flex-direction:column;gap:10px;align-items:flex-end;}
.toast{
  display:flex;align-items:center;gap:12px;background:var(--white);border:1px solid var(--border);
  border-radius:14px;box-shadow:var(--shadow-lg);padding:14px 18px;min-width:300px;max-width:380px;
  animation:toastIn .25s ease both;
}
@keyframes toastIn{from{opacity:0;transform:translateX(24px);}to{opacity:1;transform:translateX(0);}}
.toast.out{animation:toastOut .2s ease forwards;}
@keyframes toastOut{to{opacity:0;transform:translateX(24px);}}
.toast .t-ico{width:34px;height:34px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.toast p{margin:0;font-size:13px;font-weight:700;color:var(--text-primary);}
.toast span{font-size:11.5px;color:var(--text-tertiary);font-weight:500;}

.sidebar-scrim{position:fixed;inset:0;background:rgba(11,20,38,.5);z-index:55;opacity:0;pointer-events:none;transition:.2s;}
.sidebar-scrim.open{opacity:1;pointer-events:auto;}

.card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px;}
.entity-card{background:var(--glass-bg);border:1px solid var(--glass-border);backdrop-filter:blur(16px);border-radius:var(--radius-lg);box-shadow:var(--shadow-glass);padding:18px;transition:.18s;}
.entity-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg);}
.entity-card .ec-top{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
.entity-card .ec-ico{width:44px;height:44px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.entity-card h4{font-size:14.5px;font-weight:800;margin:0;}
.entity-card .ec-sub{font-size:11.5px;color:var(--text-tertiary);font-weight:600;}
.entity-card .ec-stats{display:flex;justify-content:space-between;padding-top:12px;border-top:1px solid var(--border);margin-top:12px;}
.entity-card .ec-stat b{display:block;font-size:15px;font-weight:800;}
.entity-card .ec-stat span{font-size:10.5px;color:var(--text-tertiary);font-weight:700;text-transform:uppercase;letter-spacing:.4px;}

.profile-head{display:flex;align-items:center;gap:18px;margin-bottom:20px;}
.profile-avatar{width:82px;height:82px;border-radius:20px;background:linear-gradient(135deg,var(--navy-800),var(--blue-accent));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:26px;flex-shrink:0;box-shadow:var(--shadow-md);}
.profile-meta h2{margin:0 0 4px;font-size:20px;font-weight:800;}
.profile-meta .p-line{display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:12.5px;color:var(--text-secondary);font-weight:600;}
.info-row{display:flex;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--border);font-size:13px;}
.info-row:last-child{border-bottom:none;}
.info-row span{color:var(--text-tertiary);font-weight:600;}
.info-row b{font-weight:700;color:var(--text-primary);}

.attendance-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.att-toggle{display:flex;border-radius:10px;overflow:hidden;border:1px solid var(--border-strong);}
.att-toggle button{flex:1;padding:8px 0;font-size:12px;font-weight:700;background:var(--white);border:none;color:var(--text-secondary);}
.att-toggle button.present.active{background:var(--success);color:#fff;}
.att-toggle button.absent.active{background:var(--danger);color:#fff;}
.att-toggle button.excused.active{background:var(--warning);color:#fff;}

.calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:8px;}
.cal-dow{text-align:center;font-size:10.5px;font-weight:800;color:var(--text-tertiary);text-transform:uppercase;padding-bottom:6px;}
.cal-cell{min-height:88px;border-radius:12px;border:1px solid var(--border);background:var(--white);padding:8px;font-size:11.5px;font-weight:700;color:var(--text-tertiary);position:relative;}
.cal-cell.today{border-color:var(--blue-accent);background:var(--blue-light);}
.cal-cell .cal-evt{margin-top:6px;background:var(--info-bg);color:var(--info);border-radius:6px;padding:3px 6px;font-size:10.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.cal-cell .cal-evt.type-mass{background:var(--purple-bg);color:var(--purple);}
.cal-cell .cal-evt.type-fin{background:var(--success-bg);color:var(--success);}
.cal-cell.pad{background:transparent;border:none;}
.cal-head{font-size:12px;color:var(--text-secondary);margin-bottom:2px;}
.cal-slot{display:flex;flex-direction:column;margin-top:4px;background:var(--info-bg);color:var(--info);border-radius:6px;padding:3px 6px;font-size:10px;font-weight:600;}
.cal-slot .cal-time{font-size:9px;font-weight:800;opacity:.85;letter-spacing:.3px;}
.cal-slot .cal-slot-t{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

.switch{position:relative;width:42px;height:24px;flex-shrink:0;display:inline-block;}
.switch input{opacity:0;width:0;height:0;}
.slider{position:absolute;inset:0;background:rgba(15,23,42,.15);border-radius:20px;transition:.2s;cursor:pointer;}
.slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.3);}
input:checked + .slider{background:var(--blue-accent);}
input:checked + .slider::before{transform:translateX(18px);}
.settings-row{display:flex;align-items:center;justify-content:space-between;padding:16px 0;border-bottom:1px solid var(--border);gap:20px;}
.settings-row:last-child{border-bottom:none;}
.settings-row .sr-text p{margin:0 0 2px;font-size:13.5px;font-weight:700;}
.settings-row .sr-text span{font-size:12px;color:var(--text-tertiary);font-weight:500;}
.settings-nav{display:flex;flex-direction:column;gap:2px;}
.settings-nav a,.settings-nav button{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:11px;border:none;background:transparent;text-align:left;font-size:13.5px;font-weight:700;color:var(--text-secondary);}
.settings-nav a:hover,.settings-nav button:hover{background:rgba(37,99,235,.06);}
.settings-nav a.active,.settings-nav button.active{background:var(--blue-light);color:var(--blue-accent);}
.settings-layout{display:grid;grid-template-columns:220px minmax(0, 1fr);gap:20px;}
.settings-layout > *{min-width:0;}
.settings-layout table.data-table{min-width:0;}
.settings-layout .solid-card,.settings-layout .table-card,.settings-layout .glass-card{max-width:100%;overflow-x:hidden;}
.settings-layout .settings-row{flex-wrap:wrap;}
.settings-actions-cell{display:flex;gap:8px;flex-wrap:wrap;}

.message-thread{border-radius:14px;border:1px solid var(--border);background:var(--white);padding:16px;margin-bottom:10px;}
.msg-templates{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
.tpl-card{border:1px solid var(--border);border-radius:12px;padding:14px;background:var(--white);}
.tpl-card h5{margin:0 0 6px;font-size:13px;font-weight:800;}
.tpl-card p{margin:0;font-size:12px;color:var(--text-secondary);line-height:1.5;}

.flex{display:flex;}
.gap-8{gap:8px;}
.mb-0{margin-bottom:0 !important;}
.text-muted{color:var(--text-tertiary);}
.hidden{display:none !important;}
.avatar-ring{box-shadow:0 0 0 3px var(--white),0 0 0 4px var(--border-strong);}

@media (max-width:1180px){
  .kpi-grid{grid-template-columns:repeat(2,1fr);}
  .two-col{grid-template-columns:1fr;}
  .quick-actions-grid{grid-template-columns:repeat(4,1fr);}
  .settings-layout{grid-template-columns:1fr;}
}
@media (max-width:860px){
  .sidebar{transform:translateX(-100%);width:280px !important;}
  .sidebar.mobile-open{transform:translateX(0);}
  .main-wrap,.main-wrap.sidebar-collapsed{margin-left:0;}
  .topbar,.main-wrap.sidebar-collapsed .topbar{left:0;}
  .kpi-grid{grid-template-columns:1fr 1fr;}
  .search-box{display:none;}
  .quick-actions-grid{grid-template-columns:repeat(2,1fr);}
  .form-grid{grid-template-columns:1fr;}
  .page-content{padding:18px 14px 50px;}
  .topbar{padding:0 14px;}
  .modal-box.lg,.modal-box.full{max-width:100%;}
  .user-chip .u-meta{display:none;}
}
@media (max-width:520px){
  .kpi-grid{grid-template-columns:1fr;}
  .dropdown-panel{width:92vw;right:-8px;}
  .attendance-grid{grid-template-columns:1fr;}
  .card-grid{grid-template-columns:1fr;}
}

.kpi-grid.cols-3{grid-template-columns:repeat(3,1fr);}
.kpi-grid.cols-2{grid-template-columns:repeat(2,1fr);}
@media (max-width:860px){
  .kpi-grid.cols-3{grid-template-columns:1fr 1fr;}
  .kpi-grid.cols-2{grid-template-columns:1fr 1fr;}
}
@media (max-width:520px){
  .kpi-grid.cols-3{grid-template-columns:1fr;}
  .kpi-grid.cols-2{grid-template-columns:1fr;}
}
</style>
