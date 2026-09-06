@php
    $pickerSel = $pickerSelected ?? [];
@endphp
<div class="asg-picker" id="{{ $pickerId }}Picker" data-asg-picker>
  <button type="button" class="asg-trigger" data-asg-toggle aria-haspopup="listbox" aria-expanded="false">
    <span class="asg-values muted" data-asg-values>Select member(s)…</span>
    <span class="asg-chev" aria-hidden="true"></span>
  </button>
  <div class="asg-menu" data-asg-menu role="listbox">
    <input type="search" class="asg-search" data-asg-search placeholder="Search members…" autocomplete="off">
    <div class="asg-options" data-asg-options>
      @forelse($assignees as $u)
      <label class="asg-option">
        <input type="checkbox" name="assignee_ids[]" value="{{ $u->id }}" data-id="{{ $u->id }}" data-name="{{ $u->name }}"
          @if(in_array($u->id, $pickerSel, true)) checked @endif
          @if(isset($pickerRequired) && $pickerRequired) required @endif>
        <span class="asg-opt-name">{{ $u->name }}</span>
        <span class="asg-opt-role">{{ $u->role?->name ?? 'User' }}</span>
      </label>
      @empty
      <div class="asg-empty">No users available.</div>
      @endforelse
    </div>
    <div class="asg-empty" data-asg-empty style="display:none">No members match your search.</div>
  </div>
</div>
<div class="field-hint">{{ $pickerHint }}</div>