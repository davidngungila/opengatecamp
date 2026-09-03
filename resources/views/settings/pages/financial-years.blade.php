@extends('layouts.app')

@section('title', 'Financial Years — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / Financial Years')
@section('page_title', 'Financial Years')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Financial Years</p></div>

  <div class="settings-layout">
    @include('settings.partials.nav', ['active' => 'financial-years'])

    <div>
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
                <td>{{ $y->start_date->format('d M Y') }} → {{ $y->end_date->format('d M Y') }}</td>
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
    </div>
  </div>
</div>
@endsection
