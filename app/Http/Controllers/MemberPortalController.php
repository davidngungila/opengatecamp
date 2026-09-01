<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\FinancialYear;
use App\Models\Member;
use App\Models\Pledge;
use App\Models\ReceiptPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberPortalController extends Controller
{
    private function getMember(): ?Member
    {
        return Auth::user()?->member;
    }

    public function dashboard()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $fy = FinancialYear::current();
        $activated = $member->isActivatedFor($fy?->id);
        $family = $member->family;
        $group = $member->group;
        $ministry = $member->ministry;

        $totalContributions = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->sum('amount');

        $fyContributions = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->when($fy, fn($q) => $q->whereBetween('pay_date', [$fy->start_date, $fy->end_date]))
            ->sum('amount');

        $myRegistrations = $member->eventAttendees()->with('event')->latest()->take(5)->get();
        $myPledges = $member->pledges()->with('event')->latest()->take(5)->get();

        $pledgeTotal = $member->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('amount');
        $pledgePaid = $member->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('paid_amount');
        $pledgeOutstanding = $member->pledges()->whereIn('status', ['pending', 'partial'])
            ->get()->sum(fn($p) => (float) $p->getRemainingAttribute());

        $currentCamp = Event::where('event_type', 'camp')->orderByDesc('start_date')->first();

        return view('portal.dashboard', compact(
            'member', 'fy', 'activated', 'family', 'group', 'ministry',
            'totalContributions', 'fyContributions',
            'myRegistrations', 'myPledges', 'pledgeTotal', 'pledgePaid', 'pledgeOutstanding',
            'currentCamp'
        ));
    }

    public function profile()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        return view('portal.profile', ['member' => $member->load(['group', 'ministry', 'family'])]);
    }

    public function updateProfile(Request $request)
    {
        $member = $this->getMember();
        if (! $member) {
            return back()->with('error', 'No member record linked to your account.');
        }

        $data = $request->validate([
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:255',
            'emergency_name'  => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $member->update($data);

        AuditLog::record('Updated profile via member portal', 'Member Portal', $member->name);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function family()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $family = $member->family?->load('members');

        return view('portal.family', ['member' => $member, 'family' => $family]);
    }

    public function contributions()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $contributions = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->orderByDesc('pay_date')
            ->paginate(15);

        $totalAll = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->sum('amount');

        $fy = FinancialYear::current();
        $totalFY = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->when($fy, fn($q) => $q->whereBetween('pay_date', [$fy->start_date, $fy->end_date]))
            ->sum('amount');

        return view('portal.contributions', compact('contributions', 'totalAll', 'totalFY', 'fy'));
    }

    public function registrations()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $registrations = $member->eventAttendees()->with('event')->latest()->paginate(15);
        $totalPaid = $member->eventAttendees()->sum('amount_paid');

        $currentCamp = Event::where('event_type', 'camp')->orderByDesc('start_date')->first();

        return view('portal.registrations', compact(
            'member', 'registrations', 'totalPaid', 'currentCamp'
        ));
    }

    public function storeAttendee(Request $request)
    {
        $member = $this->getMember();
        if (! $member) {
            return back()->with('error', 'No member record linked to your account.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'pickup_location' => 'required|in:arusha,moshi',
            'notes' => 'nullable|string',
        ]);

        $event = Event::where('event_type', 'camp')->orderByDesc('start_date')->first()
            ?? Event::latest()->first();

        $attendee = $event->attendees()->create([
            'member_id' => $member->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'pickup_location' => $data['pickup_location'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'amount_paid' => 0,
            'fee_amount' => 10000,
            'registered_on' => now()->toDateString(),
        ]);

        AuditLog::record('Registered attendee via portal', 'Member Portal', "{$attendee->name} ({$event->title})");

        return back()->with('success', "{$attendee->name} registered for {$event->title}. Fee: TZS 10,000.");
    }

    public function pledges()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $pledges = $member->pledges()->with('event')->latest()->paginate(15);

        $totals = [
            'pledged' => $member->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('amount'),
            'paid' => $member->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('paid_amount'),
            'outstanding' => $member->pledges()->whereIn('status', ['pending', 'partial'])
                ->get()->sum(fn($p) => (float) $p->getRemainingAttribute()),
        ];

        $currentCamp = Event::where('event_type', 'camp')->orderByDesc('start_date')->first();

        return view('portal.pledges', compact('member', 'pledges', 'totals', 'currentCamp'));
    }

    public function storePledge(Request $request)
    {
        $member = $this->getMember();
        if (! $member) {
            return back()->with('error', 'No member record linked to your account.');
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:one_time,monthly,weekly',
            'pledge_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:pledge_date',
            'notes' => 'nullable|string',
        ]);

        $event = Event::where('event_type', 'camp')->orderByDesc('start_date')->first()
            ?? Event::latest()->first();

        $pledge = Pledge::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'pledge_no' => Pledge::nextPledgeNo(),
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'amount' => $data['amount'],
            'paid_amount' => 0,
            'status' => 'pending',
            'frequency' => $data['frequency'],
            'pledge_date' => $data['pledge_date'],
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->user()?->name,
        ]);

        AuditLog::record('Created pledge via portal', 'Member Portal', "{$pledge->pledge_no} — {$pledge->amount}");

        return back()->with('success', "Pledge {$pledge->pledge_no} of TZS ".number_format($pledge->amount)." recorded.");
    }

    public function activations()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $activations = $member->activations()->with('financialYear')->get();
        $fy = FinancialYear::current();
        $activated = $member->isActivatedFor($fy?->id);

        return view('portal.activations', compact('member', 'activations', 'fy', 'activated'));
    }

    public function settings()
    {
        return view('portal.settings', ['user' => Auth::user()]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::record('Changed password via member portal', 'Member Portal', $user->name);

        return back()->with('success', 'Password changed successfully.');
    }
}
