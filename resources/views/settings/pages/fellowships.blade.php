@extends('layouts.app')

@section('title', 'University Fellowships — Settings — Open Gate Camp Mission')
@section('crumb', 'System / Settings / University Fellowships')
@section('page_title', 'University Fellowships')

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">University Fellowships</p></div>

  <div class="settings-layout">
    @include('settings.partials.nav', ['active' => 'fellowships'])

    <div>
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
    </div>
  </div>
</div>
@endsection
