@extends('layouts.app')

@section('title', 'Permissions — OpenGate Camp Connect')
@section('crumb', 'System / Permissions')
@section('page_title', 'Permissions')

@section('content')
<div class="fade-in">
  <div class="section-head">
    <div><h2>Role Permission Matrix</h2><div class="sub">Tick the permissions each role may use, then save.</div></div>
    <div class="flex gap-8">
      <a href="{{ route('users.index') }}" class="btn btn-secondary" style="text-decoration:none">Users</a>
      <a href="{{ route('users.roles') }}" class="btn btn-secondary" style="text-decoration:none">Roles</a>
    </div>
  </div>

  <div class="glass-card">
    <div class="table-scroll" style="max-height:480px;overflow-y:auto;border:1px solid var(--border);border-radius:14px">
      <table class="data-table">
        <thead><tr><th>Permission</th>@foreach($roles as $r)<th style="text-align:center">{{ Str::limit($r->name, 12) }}</th>@endforeach</tr></thead>
        <tbody>
          @foreach($permissions as $perm)
          <tr>
            <td><code style="font-size:12px">{{ $perm }}</code></td>
            @foreach($roles as $r)
              <td style="text-align:center">
                <input type="checkbox" class="checkbox perm-box"
                       data-role="{{ $r->id }}" data-perm="{{ $perm }}"
                       {{ ($r->is_super || in_array($perm, $r->permissions ?? [])) ? 'checked' : '' }}
                       {{ $r->is_super ? 'disabled' : '' }}>
              </td>
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="flex gap-8" style="justify-content:flex-end;margin-top:14px">
      <button type="button" class="btn btn-accent" onclick="savePermissions(this)">Save Permissions</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function savePermissions(btn){
  var boxes=document.querySelectorAll('.perm-box:checked:not([disabled])');
  var byRole={};
  boxes.forEach(function(cb){
    (byRole[cb.dataset.role]=byRole[cb.dataset.role]||[]).push(cb.dataset.perm);
  });
  var queue=Object.keys(byRole);
  if(queue.length===0){ toast('No changes detected','info'); return; }
  toast('Saving permissions for '+queue.length+' role(s)...','info');
  (function next(){
    var roleId=queue.shift();
    if(roleId===undefined){ setTimeout(function(){ location.reload(); },600); return; }
    fetch('{{ url('/roles') }}/'+roleId+'/permissions', {
      method:'PUT',
      headers:{
        'Content-Type':'application/json',
        'X-CSRF-TOKEN':'{{ csrf_token() }}',
        'Accept':'application/json'
      },
      body: JSON.stringify({ permissions: byRole[roleId] })
    }).then(next).catch(function(){ toast('Failed to save permissions','error'); });
  })();
}
</script>
@endpush