@extends('layouts.app')

@section('title', 'New Journal Entry â€” Open Gate Camp Mission')
@section('crumb', 'Finance / Financial Accounting / New Journal Entry')
@section('page_title', 'New Journal Entry')

@section('content')
<div class="fade-in">
  <a class="btn btn-ghost btn-sm" style="margin-bottom:14px" href="{{ route('accounting.journal') }}">â† Back to Journal Entries</a>

  <div class="glass-card">
    <div class="section-head"><h2>Record a Double-Entry Transaction</h2></div>

    <form method="POST" action="{{ route('accounting.journal.store') }}" id="journalForm"
          data-confirm data-confirm-title="Post journal entry?"
          data-confirm-message="The entry will be posted to the ledger."
          data-confirm-label="Post Entry">
      @csrf
      <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:18px">
        <div class="field"><label>Entry Date *</label><input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required></div>
        <div class="field"><label>Reference</label><input name="reference" value="{{ old('reference') }}" placeholder="e.g. RPT-AUG"></div>
        <div class="field"><label>Status</label>
          <select name="status"><option value="posted">Posted</option><option value="draft">Draft</option></select>
        </div>
        <div class="field full"><label>Description</label><input name="description" value="{{ old('description') }}" placeholder="Narration for this transaction..."></div>
      </div>

      <div style="border:1px solid var(--border);border-radius:14px;overflow:hidden">
        <div class="table-scroll">
          <table class="data-table" id="linesTable" style="min-width:0">
            <thead><tr><th style="width:34px"></th><th style="min-width:220px">Account *</th><th>Description</th><th style="width:160px;text-align:right">Debit (TZS)</th><th style="width:160px;text-align:right">Credit (TZS)</th><th style="width:44px">Del</th></tr></thead>
            <tbody id="linesBody"></tbody>
            <tfoot>
              <tr style="background:rgba(37,99,235,.06)">
                <td colspan="3" style="font-weight:800">TOTALS</td>
                <td style="text-align:right;font-weight:800" id="totalDr">0</td>
                <td style="text-align:right;font-weight:800" id="totalCr">0</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="flex gap-8" style="margin-top:12px;justify-content:space-between;flex-wrap:wrap">
        <button type="button" class="btn btn-secondary btn-sm" onclick="addLine()">+ Add Line</button>
        <span id="balanceState" class="badge badge-neutral badge-dotted">Not balanced</span>
      </div>

      <div class="flex gap-8" style="justify-content:flex-end;margin-top:18px">
        <button type="submit" class="btn btn-accent">Post Entry</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
var accounts=@json($accounts->map(fn($a)=>['id'=>$a->id,'code'=>$a->code,'name'=>$a->name]));
var lineIdx=0;

function accountOptions(selected){
  return '<option value="">â€” Select â€”</option>'+accounts.map(function(a){
    var sel=Number(selected)===a.id?' selected':'';
    return '<option value="'+a.id+'"'+sel+'>'+a.code+' â€” '+a.name+'</option>';
  }).join('');
}
function addLine(pref){
  var idx=lineIdx++;
  var tr=document.createElement('tr');
  tr.dataset.line='1';
  tr.innerHTML=
    '<td style="color:var(--text-tertiary)">'+(idx+1)+'</td>'+
    '<td><select name="lines['+idx+'][account_id]" required style="width:100%;height:36px;border-radius:9px;border:1px solid var(--border-strong);padding:0 10px;font-weight:600">'+accountOptions(pref&&pref.account_id)+'</select></td>'+
    '<td><input name="lines['+idx+'][description]" placeholder="Line note..." value="'+(pref&&pref.description?pref.description.replace(/"/g,'&quot;'):'')+'" style="width:100%"></td>'+
    '<td><input type="number" step="0.01" min="0" name="lines['+idx+'][debit]" value="'+(pref&&pref.debit?pref.debit:'')+'" oninput="recalc()" style="width:100%;text-align:right"></td>'+
    '<td><input type="number" step="0.01" min="0" name="lines['+idx+'][credit]" value="'+(pref&&pref.credit?pref.credit:'')+'" oninput="recalc()" style="width:100%;text-align:right"></td>'+
    '<td><button type="button" class="btn btn-ghost btn-sm" onclick="this.closest(\'tr\').remove();recalc()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></td>';
  document.getElementById('linesBody').appendChild(tr);
}
function recalc(){
  var dr=0, cr=0;
  document.querySelectorAll('#linesBody tr').forEach(function(tr){
    dr+=parseFloat(tr.querySelector('[name$="[debit]"]').value)||0;
    cr+=parseFloat(tr.querySelector('[name$="[credit]"]').value)||0;
  });
  document.getElementById('totalDr').textContent=dr.toLocaleString();
  document.getElementById('totalCr').textContent=cr.toLocaleString();
  var state=document.getElementById('balanceState');
  var diff=Math.round((dr-cr)*100)/100;
  if(diff===0 && dr>0){ state.className='badge badge-success badge-dotted'; state.innerHTML='Balanced <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M20 6L9 17l-5-5"/></svg>'; }
  else { state.className='badge badge-warning badge-dotted'; state.textContent='Difference: '+Math.abs(diff).toLocaleString(); }
}
window.__beforeConfirm=function(form){
  var dr=parseFloat(document.getElementById('totalDr').textContent.replace(/,/g,''))||0;
  var cr=parseFloat(document.getElementById('totalCr').textContent.replace(/,/g,''))||0;
  var diff=Math.round((dr-cr)*100)/100;
  if(dr<=0){ toast('Enter at least one amount.','error'); return false; }
  if(diff!==0){ toast('Not balanced â€” debits TZS '+dr.toLocaleString()+' vs credits TZS '+cr.toLocaleString()+'.','error'); return false; }
  form.setAttribute('data-confirm-message','Posting double entry of TZS '+dr.toLocaleString()+' (Dr = Cr). Continue?');
  return true;
};
(function(){ addLine(); addLine(); })();
</script>
@endpush
