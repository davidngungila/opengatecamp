<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Fellowship;
use Illuminate\Http\Request;

class FellowshipLeaderController extends Controller
{
    private function myFellowships(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()->fellowships()
            ->with(['leaders'])
            ->withCount(['attendees', 'attendees as confirmed_count' => fn ($qr) => $qr->whereIn('status', ['confirmed', 'attended'])])
            ->withSum('attendees', 'amount_paid')
            ->orderBy('name')
            ->get();
    }

    private function ownsFellowship(Fellowship $fellowship): bool
    {
        return auth()->user()->fellowships()->where('fellowships.id', $fellowship->id)->exists();
    }

    private function ownsAttendee(EventAttendee $attendee): bool
    {
        if (! $attendee->fellowship_id) {
            return false;
        }

        $fellowship = $attendee->fellowship()->first();

        return $fellowship && $this->ownsFellowship($fellowship);
    }

    public function dashboard()
    {
        $fellowships = $this->myFellowships();

        return view('portal.fellowship.dashboard', [
            'fellowships' => $fellowships,
            'currentCamp' => Event::currentCamp(),
        ]);
    }

    public function members(Fellowship $fellowship)
    {
        if (! $this->ownsFellowship($fellowship)) {
            abort(403, 'You are not a leader of this fellowship.');
        }

        $attendees = $fellowship->attendees()
            ->with('event')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        if (! empty($fellowship->diocese)) {
            $commonPickup = strtolower($fellowship->diocese) === 'moshi' ? 'moshi' : 'arusha';
        } else {
            $commonPickup = EventAttendee::where('fellowship_id', $fellowship->id)->select('pickup_location')->groupBy('pickup_location')->selectRaw('pickup_location, COUNT(*) as c')->orderByDesc('c')->value('pickup_location');
            if (! $commonPickup) {
                $hay = strtolower($fellowship->university.' '.$fellowship->name);
                $commonPickup = str_contains($hay, 'moshi') ? 'moshi' : 'arusha';
            }
        }

        return view('portal.fellowship.members', [
            'fellowship' => $fellowship,
            'attendees' => $attendees,
            'currentCamp' => Event::currentCamp(),
            'fee' => (float) (Event::currentCamp()?->registration_fee) ?: 10000,
            'defaultPickup' => $commonPickup,
        ]);
    }

    public function storeMember(Request $request, Fellowship $fellowship)
    {
        if (! $this->ownsFellowship($fellowship)) {
            abort(403, 'You are not a leader of this fellowship.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'pickup_location' => 'required|in:arusha,moshi',
            'notes' => 'nullable|string',
        ]);

        $event = Event::currentCamp();

        if (! $event) {
            return back()->with('error', 'No event configured yet. Registration is currently closed.');
        }

        $attendee = EventAttendee::create([
            'event_id' => $event->id,
            'fellowship_id' => $fellowship->id,
            'fellowship' => $fellowship->name,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'pickup_location' => $data['pickup_location'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'amount_paid' => 0,
            'fee_amount' => (float) $event->registration_fee > 0 ? $event->registration_fee : 10000,
            'registered_on' => now()->toDateString(),
            'registered_by' => auth()->user()?->name,
        ]);

        AuditLog::record('Registered delegation member', 'Fellowships', "Recorded by ".(auth()->user()?->name ?? '—')." from {$fellowship->name} — {$attendee->name} ({$attendee->phone})");

        return back()->with('success', "{$attendee->name} added to the {$fellowship->name} delegation.");
    }

    public function updateMember(Request $request, EventAttendee $attendee)
    {
        if (! $this->ownsAttendee($attendee)) {
            abort(403, 'You cannot edit this attendee.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'pickup_location' => 'required|in:arusha,moshi',
            'notes' => 'nullable|string',
        ]);

        $attendee->update($data);

        AuditLog::record('Updated delegation member', 'Fellowships', "{$attendee->fellowship()->value('name')} — {$attendee->name}");

        return back()->with('success', "{$attendee->name}'s details updated.");
    }

    public function destroyMember(Request $request, EventAttendee $attendee)
    {
        if (! $this->ownsAttendee($attendee)) {
            abort(403, 'You cannot remove this attendee.');
        }

        if ((float) $attendee->amount_paid > 0 || $attendee->status === 'attended') {
            return back()->with('error', "{$attendee->name} has payments or arrival records — contact the committee to remove them.");
        }

        AuditLog::record('Removed delegation member', 'Fellowships', "{$attendee->fellowship()->value('name')} — {$attendee->name}");

        $attendee->delete();

        return back()->with('success', "{$attendee->name} removed from the delegation.");
    }
}