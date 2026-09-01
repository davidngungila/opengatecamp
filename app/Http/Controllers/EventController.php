<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventSession;
use App\Models\Member;
use App\Models\Message;
use App\Models\Pledge;
use App\Services\AccountingPostingService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type');
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = Event::withCount([
            'attendees as registered_count',
            'attendees as confirmed_count' => fn ($qr) => $qr->whereIn('status', ['confirmed', 'attended']),
            'pledges as pledge_count',
        ]);

        $query->when($type, fn ($qr) => $qr->where('event_type', $type))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('venue', 'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")));

        $events = $query->orderByDesc('start_date')->paginate(9)->withQueryString();

        $upcoming = Event::whereIn('status', ['planned', 'open_registration', 'ongoing'])
            ->whereDate('start_date', '>=', now()->today()->subDay())
            ->count();

        return view('events.index', [
            'events' => $events,
            'types' => Event::types(),
            'statuses' => Event::statuses(),
            'upcomingCount' => $upcoming,
            'totalAttendees' => EventAttendee::count(),
            'filters' => compact('type', 'status', 'q'),
        ]);
    }

    public function show(Event $event)
    {
        $event->load(['sessions', 'attendees', 'pledges']);

        $attendees = $event->attendees()->with('member')->latest()->paginate(15);
        $pledges = $event->pledges()->latest()->take(10)->get();

        $stats = [
            'registered' => $event->attendees()->count(),
            'confirmed' => $event->attendees()->whereIn('status', ['confirmed', 'attended'])->count(),
            'attended' => $event->attendees()->where('status', 'attended')->count(),
            'pledged' => $event->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('amount'),
            'pledgedPaid' => $event->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('paid_amount'),
        ];

        return view('events.show', [
            'event' => $event,
            'attendees' => $attendees,
            'pledges' => $pledges,
            'stats' => $stats,
            'members' => Member::active()->orderBy('name')->get(),
            'eventStatuses' => Event::statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['slug'] = Str::slug($data['title']) ?: Str::random(8);
        $event = Event::create($data);

        AuditLog::record('Created event', 'Events', "{$event->title} ({$event->event_type})");
        return redirect()->route('events.index')->with('success', "Event {$event->title} created successfully.");
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validated($request, $event->id);

        $event->update($data);
        AuditLog::record('Updated event', 'Events', "{$event->title} ({$event->event_type})");
        return redirect()->route('events.show', $event)->with('success', "Event {$event->title} updated successfully.");
    }

    public function destroy(Event $event)
    {
        AuditLog::record('Deleted event', 'Events', $event->title);
        $title = $event->title;
        $event->delete();
        return redirect()->route('events.index')->with('success', "Event {$title} deleted successfully.");
    }

    public function toggleStatus(Event $event, Request $request)
    {
        $status = $request->input('status');
        if (! array_key_exists($status, Event::statuses())) {
            return back()->with('error', 'Invalid event status.');
        }
        $event->update(['status' => $status]);
        AuditLog::record('Changed event status', 'Events', "{$event->title} → {$status}");
        return back()->with('success', "Event status updated to {$status}.");
    }

    // ── Sessions ────────────────────────────────────────
    public function storeSession(Request $request, Event $event)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'session_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'venue' => 'nullable|string|max:255',
            'speaker' => 'nullable|string|max:255',
            'facilitator' => 'nullable|string|max:255',
        ]);
        $data['event_id'] = $event->id;
        $data['sort_order'] = $event->sessions()->max('sort_order') + 1;
        $event->sessions()->create($data);
        AuditLog::record('Added event session', 'Events', "{$event->title} — {$data['title']}");
        return back()->with('success', 'Session added to event.');
    }

    public function destroySession(Event $event, EventSession $session)
    {
        AuditLog::record('Removed event session', 'Events', "{$event->title} — {$session->title}");
        $session->delete();
        return back()->with('success', 'Session removed.');
    }

    // ── Attendees ───────────────────────────────────────
    public function attendees(Request $request)
    {
        $eventId = $request->query('event_id');
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = EventAttendee::with(['event', 'member']);

        $query->when($eventId, fn ($qr) => $qr->where('event_id', $eventId))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")));

        $attendees = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('attendees.index', [
            'attendees' => $attendees,
            'events' => Event::orderByDesc('start_date')->get(),
            'members' => Member::active()->orderBy('name')->get(),
            'statuses' => EventAttendee::statuses(),
            'defaultFee' => 10000,
            'pickupLocations' => ['arusha' => 'Arusha', 'moshi' => 'Moshi'],
            'filters' => compact('eventId', 'status', 'q'),
            'totals' => [
                'registered' => EventAttendee::count(),
                'confirmed' => EventAttendee::whereIn('status', ['confirmed', 'attended'])->count(),
                'attended' => EventAttendee::where('status', 'attended')->count(),
            ],
        ]);
    }

    public function storeAttendeeGlobal(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'member_id' => 'nullable|exists:members,id',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'amount_paid' => 'nullable|numeric|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank,mobile',
            'pickup_location' => 'nullable|in:arusha,moshi',
            'status' => 'required|in:pending,confirmed,attended,no_show,cancelled',
            'notes' => 'nullable|string',
            'send_sms' => 'nullable|in:1,on,true',
        ]);

        $sendSms = ($data['send_sms'] ?? '') ? true : false;
        unset($data['send_sms']);

        if (empty($data['name']) && ! $request->filled('name')) {
            return back()->with('error', 'Attendee name is required.');
        }

        $event = null;
        if (! empty($data['event_id'])) {
            $event = Event::find($data['event_id']);
        }
        if (! $event) {
            $event = Event::where('event_type', 'camp')->orderByDesc('start_date')->first()
                ?? Event::latest()->first();
        }

        $data['event_id'] = $event->id;
        $data['registered_on'] = now()->toDateString();

        if (empty($data['fee_amount'])) {
            $data['fee_amount'] = (float) $event->registration_fee > 0 ? $event->registration_fee : 10000;
        }

        $attendee = DB::transaction(function () use ($data, $event) {
            $attendee = $event->attendees()->create($data);

            if ((float) ($data['amount_paid'] ?? 0) > 0 && ! empty($data['payment_method'])) {
                $posting = app(AccountingPostingService::class);
                $entry = $posting->postMoneyIn([
                    'date' => now()->toDateString(),
                    'description' => 'Attendee registration payment — '.$data['name'].' ('.$event->title.')',
                    'amount' => $data['amount_paid'],
                    'method' => $data['payment_method'],
                    'incomeAccount' => $posting->incomeAccount('acct.attendee_income', '4040'),
                ]);

                $attendee->update(['journal_entry_id' => $entry->id]);
            }

            return $attendee;
        });

        AuditLog::record('Registered attendee', 'Events', "{$event->title} — {$data['name']}");

        if ($sendSms && ! empty($attendee->phone)) {
            $sms = new SmsService();
            if ($sms->isConfigured()) {
                $msg = "Hello {$attendee->name},\nYou are registered for \"{$event->title}\" at {$event->venue}. We look forward to seeing you! — Open Gate Camp Mission";
                $result = $sms->send($attendee->phone, $msg);
                Message::create([
                    'channel'          => 'sms',
                    'recipients'       => $attendee->name,
                    'phone'            => $attendee->phone,
                    'subject'          => null,
                    'message'          => $msg,
                    'status'           => $result['success'] ? 'sent' : 'failed',
                    'api_message_id'   => $result['api_message_id'],
                    'api_response'     => $result['raw'],
                    'created_by'       => auth()->user()?->name,
                ]);
                AuditLog::record($result['success'] ? 'Sent registration SMS' : 'Failed registration SMS', 'Communication', "{$attendee->name} ({$attendee->phone})");
            }
        }

        $notice = "Attendee {$data['name']} registered.";
        if ($sendSms && empty($attendee->phone)) {
            $notice .= ' SMS was skipped — no phone number on file.';
        }

        return back()->with('success', $notice);
    }

    public function recordAttendeePayment(Request $request, EventAttendee $attendee)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:cash,bank,mobile',
            'reference' => 'nullable|string|max:255',
            'pay_date' => 'required|date',
            'notify_sms' => 'nullable|in:1,on,true',
        ]);

        $notifySms = ($data['notify_sms'] ?? '') ? true : false;
        unset($data['notify_sms']);

        $amount = (float) $data['amount'];

        DB::transaction(function () use ($attendee, $data, $amount) {
            $posting = app(AccountingPostingService::class);
            $entry = $posting->postMoneyIn([
                'date' => $data['pay_date'],
                'description' => 'Attendee payment — '.$attendee->name
                    .($attendee->event ? ' ('.$attendee->event->title.')' : ''),
                'reference' => $data['reference'] ?? null,
                'amount' => $amount,
                'method' => $data['method'],
                'incomeAccount' => $posting->incomeAccount('acct.attendee_income', '4040'),
            ]);

            $attendee->increment('amount_paid', $amount);
            $attendee->update(['payment_method' => $data['method'], 'journal_entry_id' => $entry->id]);
        });

        $attendee->refresh();

        AuditLog::record('Recorded attendee payment', 'Events', "{$attendee->event?->title} — {$attendee->name} (+{$amount})");

        $notice = "Payment of TZS ".number_format($amount)." recorded for {$attendee->name}.";

        if ($notifySms) {
            if (! empty($attendee->phone)) {
                $sms = new SmsService();

                if ($sms->isConfigured()) {
                    $balance = null;
                    if ($attendee->fee_amount !== null) {
                        $balance = max(0, (float) $attendee->fee_amount - (float) $attendee->amount_paid);
                    }

                    $remaining = $balance !== null
                        ? " Your remaining balance is TZS ".number_format($balance)."."
                        : '';
                    $msg = "Hello {$attendee->name},\nWe have received your payment of TZS ".number_format($amount)
                        ." for \"{$attendee->event?->title}\". Thank you for your support and generosity!".$remaining
                        ." — Open Gate Camp Mission";

                    $result = $sms->send($attendee->phone, $msg);
                    Message::create([
                        'channel'          => 'sms',
                        'recipients'       => $attendee->name,
                        'phone'            => $attendee->phone,
                        'subject'          => null,
                        'message'          => $msg,
                        'status'           => $result['success'] ? 'sent' : 'failed',
                        'api_message_id'   => $result['api_message_id'],
                        'api_response'     => $result['raw'],
                        'created_by'       => auth()->user()?->name,
                    ]);
                    AuditLog::record($result['success'] ? 'Sent payment thank-you SMS' : 'Failed payment SMS', 'Communication', "{$attendee->name} ({$attendee->phone})");

                    if ($result['success']) {
                        $notice .= " Thank-you SMS sent to {$attendee->name} at {$attendee->phone}.";
                    } else {
                        $notice .= ' SMS failed ('.($result['status'] ?? 'error').').';
                    }
                } else {
                    $notice .= ' SMS skipped — SMS API token not configured.';
                }
            } else {
                $notice .= ' SMS skipped — no phone number on file.';
            }
        } else {
            $notice .= ' No SMS sent (notify disabled).';
        }

        return back()->with('success', $notice);
    }

    public function sendAttendeeSms(Request $request, EventAttendee $attendee)
    {
        $data = $request->validate([
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:1600',
        ]);

        $phone = $data['phone'] ?: $attendee->phone;
        if (! $phone) {
            return back()->with('error', "No phone number on file for {$attendee->name}. Add one first.");
        }

        $sms = new SmsService();
        if (! $sms->isConfigured()) {
            return back()->with('error', 'SMS API token is not configured. Go to Communication → Settings to add it.');
        }

        $result = $sms->send($phone, $data['message']);

        $status = $result['success'] ? 'sent' : 'failed';
        Message::create([
            'channel' => 'sms',
            'recipients' => $attendee->name ?? 'Attendee',
            'phone' => $phone,
            'subject' => null,
            'message' => $data['message'],
            'status' => $status,
            'api_message_id' => $result['api_message_id'],
            'api_response' => $result['raw'],
            'created_by' => auth()->user()?->name,
        ]);

        AuditLog::record($status === 'sent' ? 'Sent SMS' : 'Failed SMS', 'Communication', "{$attendee->name} ({$phone})");

        if ($result['success']) {
            return back()->with('success', "SMS sent to {$attendee->name} at {$phone}.");
        }

        return back()->with('error', "SMS failed ({$result['status']}). Check the number and API settings.");
    }

    public function storeAttendee(Request $request, Event $event)
    {
        $data = $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'name' => 'required_without:member_id|nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'amount_paid' => 'nullable|numeric|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank,mobile',
            'pickup_location' => 'nullable|in:arusha,moshi',
            'status' => 'required|in:pending,confirmed,attended,no_show,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (empty($data['name']) && ! empty($data['member_id'])) {
            $member = Member::findOrFail($data['member_id']);
            $data['name'] = $member->name;
            $data['phone'] = $data['phone'] ?? $member->phone;
            $data['email'] = $data['email'] ?? $member->email;
        }

        $data['event_id'] = $event->id;
        $data['registered_on'] = now()->toDateString();

        if (empty($data['fee_amount'])) {
            $data['fee_amount'] = (float) $event->registration_fee > 0 ? $event->registration_fee : 10000;
        }

        $attendee = DB::transaction(function () use ($data, $event) {
            $attendee = $event->attendees()->create($data);

            if ((float) ($data['amount_paid'] ?? 0) > 0 && ! empty($data['payment_method'])) {
                $posting = app(AccountingPostingService::class);
                $entry = $posting->postMoneyIn([
                    'date' => now()->toDateString(),
                    'description' => 'Attendee registration payment — '.$data['name'].' ('.$event->title.')',
                    'amount' => $data['amount_paid'],
                    'method' => $data['payment_method'],
                    'incomeAccount' => $posting->incomeAccount('acct.attendee_income', '4040'),
                ]);

                $attendee->update(['journal_entry_id' => $entry->id]);
            }

            return $attendee;
        });

        AuditLog::record('Registered attendee', 'Events', "{$event->title} — {$data['name']}");
        return back()->with('success', "Attendee {$data['name']} registered.");
    }

    public function updateAttendee(Request $request, Event $event, EventAttendee $attendee)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,attended,no_show,cancelled',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank,mobile',
            'notes' => 'nullable|string',
        ]);

        if ($data['status'] === 'attended' && empty($attendee->checked_in_at)) {
            $data['checked_in_at'] = now();
            $data['checked_in_by'] = auth()->user()?->name;
        }

        $attendee->update($data);
        AuditLog::record('Updated attendee', 'Events', "{$event->title} — {$attendee->name}");
        return back()->with('success', 'Attendee status updated.');
    }

    public function destroyAttendee(Event $event, EventAttendee $attendee)
    {
        $name = $attendee->name;
        $attendee->delete();
        AuditLog::record('Removed attendee', 'Events', "{$event->title} — {$name}");
        return back()->with('success', 'Attendee removed.');
    }

    // ── Calendar ────────────────────────────────────────
    public function calendar(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $date = date_create($month.'-01') ?: now()->startOfMonth();

        $start = (clone $date)->modify('first day of this month');
        $end = (clone $date)->modify('last day of this month');

        $events = Event::where('event_type', 'camp')
            ->whereBetween('start_date', [$start, $end])
            ->withCount('attendees as registered_count')
            ->orderBy('start_date')
            ->get();

        $eventsByDay = [];
        foreach ($events as $e) {
            $eventsByDay[$e->start_date->format('Y-m-d')][] = $e;
        }

        return view('calendar.index', [
            'eventsByDay' => $eventsByDay,
            'today' => now()->startOfDay(),
            'monthDate' => $date,
            'prevMonth' => (clone $date)->modify('-1 month')->format('Y-m'),
            'nextMonth' => (clone $date)->modify('+1 month')->format('Y-m'),
        ]);
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'required|in:camp,conference,mission_trip,training,worship,other',
            'description' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'status' => 'required|in:draft,planned,open_registration,ongoing,completed,cancelled',
            'capacity' => 'nullable|integer|min:0',
            'registration_fee' => 'nullable|numeric|min:0',
            'featured' => 'nullable|boolean',
            'organizer' => 'nullable|string|max:255',
        ]);
    }
}