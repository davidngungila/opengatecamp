<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Member;
use App\Models\Pledge;
use App\Models\PledgePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PledgeController extends Controller
{
    public function index(Request $request)
    {
        $eventId = $request->query('event_id');
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = Pledge::with(['event', 'member']);

        $query->when($eventId, fn ($qr) => $qr->where('event_id', $eventId))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('pledge_no', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")));

        $pledges = $query->orderByDesc('pledge_date')->paginate(15)->withQueryString();

        $totals = [
            'pledged' => (clone $query)->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('amount'),
            'paid' => (clone $query)->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('paid_amount'),
            'outstanding' => Pledge::whereIn('status', ['pending', 'partial'])
                ->get()->sum(fn ($p) => $p->getRemainingAttribute()),
        ];

        return view('pledges.index', [
            'pledges' => $pledges,
            'events' => Event::orderByDesc('start_date')->get(),
            'members' => Member::active()->orderBy('name')->get(),
            'statuses' => Pledge::statuses(),
            'frequencies' => Pledge::frequencies(),
            'filters' => compact('eventId', 'status', 'q'),
            'totals' => $totals,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'member_id' => 'nullable|exists:members,id',
            'name' => 'required_without:member_id|nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:one_time,monthly,weekly',
            'notes' => 'nullable|string',
            'pledge_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:pledge_date',
        ]);

        if (empty($data['name']) && ! empty($data['member_id'])) {
            $member = Member::findOrFail($data['member_id']);
            $data['name'] = $member->name;
            $data['email'] = $data['email'] ?? $member->email;
            $data['phone'] = $data['phone'] ?? $member->phone;
        }

        $data['pledge_no'] = Pledge::nextPledgeNo();
        $data['paid_amount'] = 0;
        $data['status'] = 'pending';
        $data['created_by'] = auth()->user()?->name;

        $pledge = Pledge::create($data);
        AuditLog::record('Created pledge', 'Pledges', "{$pledge->pledge_no} — {$pledge->name} ({$pledge->amount})");
        return back()->with('success', "Pledge {$pledge->pledge_no} recorded.");
    }

    public function recordPayment(Request $request, Pledge $pledge)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:cash,bank,mobile',
            'reference' => 'nullable|string|max:255',
            'pay_date' => 'required|date',
        ]);

        if ($data['amount'] > $pledge->getRemainingAttribute() && $pledge->status !== 'fulfilled') {
            $data['amount'] = min($data['amount'], $pledge->getRemainingAttribute());
        }

        $payment = DB::transaction(function () use ($data, $pledge) {
            $data['pledge_id'] = $pledge->id;
            $data['recorded_by'] = auth()->user()?->name;
            $payment = PledgePayment::create($data);

            $paid = $pledge->payments()->sum('amount');
            $pledge->update([
                'paid_amount' => $paid,
                'status' => $paid >= $pledge->amount ? 'fulfilled' : ($paid > 0 ? 'partial' : 'pending'),
            ]);

            return $payment;
        });

        AuditLog::record('Recorded pledge payment', 'Pledges', "{$pledge->pledge_no} — {$payment->amount}");
        return back()->with('success', "Payment of {$payment->amount} recorded.");
    }

    public function update(Request $request, Pledge $pledge)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'status' => 'required|in:pending,partial,fulfilled,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (in_array($data['status'], ['pending', 'partial']) && $data['amount'] <= $pledge->paid_amount) {
            $data['status'] = 'fulfilled';
        }

        $pledge->update($data);
        AuditLog::record('Updated pledge', 'Pledges', "{$pledge->pledge_no} — {$pledge->name}");
        return back()->with('success', "Pledge {$pledge->pledge_no} updated.");
    }

    public function destroy(Pledge $pledge)
    {
        AuditLog::record('Deleted pledge', 'Pledges', "{$pledge->pledge_no} — {$pledge->name}");
        $no = $pledge->pledge_no;
        $pledge->delete();
        return back()->with('success', "Pledge {$no} deleted.");
    }
}