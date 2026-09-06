{{-- Shared "Plan Day Activity" drawer: schedule an activity for a specific day and hours --}}
<div class="drawer-overlay" id="sessionDrawer">
  <div class="drawer-panel">
    <div class="drawer-head">
      <div>
        <h3 id="sessTitle">Plan Day Activity</h3>
        <p id="sessSub">Schedule an activity for a specific day and hours</p>
      </div>
      <button type="button" class="modal-close" data-drawer-close><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" id="sessionForm" action="{{ route('calendar.sessions.store') }}">
      @csrf
      <input type="hidden" name="_method" id="sessMethod" value="POST">
      <input type="hidden" name="id" id="sessId" value="">
      <div class="drawer-body">
        <div class="form-grid">
          <div class="field full"><label>Date *</label><input type="date" name="session_date" id="sessDate" required></div>
          <div class="field full"><label>Activity / Title *</label><input name="title" id="sessTitleInput" placeholder="e.g. Opening Devotion, Group Games" required></div>
          <div class="field"><label>Start Time *</label><input type="time" name="start_time" id="sessStart" required></div>
          <div class="field"><label>End Time *</label><input type="time" name="end_time" id="sessEnd" required></div>
          <div class="field full"><label>Venue</label><input name="venue" id="sessVenue" placeholder="e.g. Main Hall"></div>
          <div class="field"><label>Category</label><input name="category" id="sessCategory" placeholder="e.g. Worship, Food, Committee"></div>
          <div class="field"><label>Speaker</label><input name="speaker" id="sessSpeaker" placeholder="e.g. Fr. Daniel"></div>
          <div class="field full"><label>Facilitator</label><input name="facilitator" id="sessFacilitator" placeholder="e.g. Grace Kileo"></div>
          <div class="field full"><label>Notes</label><textarea name="description" id="sessDescription" placeholder="Details..."></textarea></div>
        </div>
      </div>
      <div class="drawer-foot">
        <button type="button" class="btn btn-danger" id="sessDelete" style="margin-right:auto;display:none" onclick="deleteSession()">Delete</button>
        <button type="button" class="btn btn-secondary" data-drawer-close>Cancel</button>
        <button type="submit" class="btn btn-accent" id="sessSubmit">Plan Activity</button>
      </div>
    </form>
  </div>
</div>

<form method="POST" id="sessionDeleteForm" action="">
  @csrf
  @method('DELETE')
</form>

<script>
var todayLabel = @json($today->format('jS F Y'));
var planDate = '';

function fillSessionForm(s){
  document.getElementById('sessId').value = s.id;
  document.getElementById('sessDate').value = s.session_date || planDate;
  document.getElementById('sessTitleInput').value = s.title || '';
  document.getElementById('sessStart').value = s.start_time || '';
  document.getElementById('sessEnd').value = s.end_time || '';
  document.getElementById('sessVenue').value = s.venue || '';
  document.getElementById('sessCategory').value = s.category || '';
  document.getElementById('sessSpeaker').value = s.speaker || '';
  document.getElementById('sessFacilitator').value = s.facilitator || '';
  document.getElementById('sessDescription').value = s.description || '';
}

function openPlanDrawer(dateStr){
  planDate = dateStr || todayLabel || '';
  document.getElementById('sessTitle').textContent = 'Plan Day Activity';
  document.getElementById('sessSub').textContent = dateStr ? 'Scheduling activity for ' + dateStr : 'Schedule an activity for a specific day and hours';
  document.getElementById('sessMethod').value = 'POST';
  document.getElementById('sessionForm').action = @json(route('calendar.sessions.store'));
  document.getElementById('sessId').value = '';
  document.getElementById('sessDate').value = dateStr || @json($today->format('Y-m-d'));
  document.getElementById('sessTitleInput').value = '';
  document.getElementById('sessStart').value = '';
  document.getElementById('sessEnd').value = '';
  document.getElementById('sessVenue').value = '';
  document.getElementById('sessCategory').value = '';
  document.getElementById('sessSpeaker').value = '';
  document.getElementById('sessFacilitator').value = '';
  document.getElementById('sessDescription').value = '';
  document.getElementById('sessDelete').style.display = 'none';
  document.getElementById('sessSubmit').textContent = 'Plan Activity';
  openDrawerById('sessionDrawer');
}

function openEditDrawer(id){
  var s = SESSIONS.find(function(x){ return Number(x.id) === Number(id); });
  if(!s) return;
  planDate = s.session_date;
  fillSessionForm(s);
  document.getElementById('sessTitle').textContent = 'Edit Activity';
  document.getElementById('sessSub').textContent = (s.event_title || 'Calendar') + ' · ' + s.session_date;
  document.getElementById('sessMethod').value = 'PUT';
  document.getElementById('sessionForm').action = @json(url('/calendar/sessions')) + '/' + id;
  document.getElementById('sessDelete').style.display = '';
  document.getElementById('sessSubmit').textContent = 'Save Changes';
  openDrawerById('sessionDrawer');
}

function deleteSession(){
  var id = document.getElementById('sessId').value;
  if(!id) return;
  var f = document.getElementById('sessionDeleteForm');
  f.action = @json(url('/calendar/sessions')) + '/' + id;
  confirmAction(f, 'Delete this activity?', 'This activity will be removed from the calendar permanently.', 'Delete');
}
</script>