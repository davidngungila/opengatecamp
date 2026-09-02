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
        return redirect()->route('events.show', $event)->with('success', "Event {$event->title} created successfully.");
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
        return redirect()->route('dashboard')->with('success', "Event {$title} deleted successfully.");
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
        $eventSlug = (string) $request->query('event');
        $eventId = $eventSlug !== ''
            ? (Event::where('slug', $eventSlug)->value('id') ?? (ctype_digit($eventSlug) ? (int) $eventSlug : null))
            : null;
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
            'fellowships' => $this->fellowshipList(),
            'filters' => compact('eventSlug', 'status', 'q'),
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
            'fellowship' => 'nullable|string|max:255',
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
                $attendee->loadMissing('event');
                $this->ensureTicket($attendee);
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

        if ($attendee->hasCompletedContribution()) {
            $attendee->loadMissing('event');
            $this->ensureTicket($attendee);
        }

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
                $attendee->loadMissing('event');
                $this->ensureTicket($attendee);
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

    // ── Tickets ─────────────────────────────────────────
    private function ensureTicket(EventAttendee $attendee): string
    {
        return $attendee->getTicketNo(); // lazily issues + persists a short 6-char code
    }

    public function ticketPdf(EventAttendee $attendee)
    {
        abort_unless($attendee->hasCompletedContribution(), 422);

        $attendee->loadMissing('event');
        $this->ensureTicket($attendee);

        $payload = 'OGCM|TICKET|'.$attendee->getTicketNo().'|'.$attendee->event?->slug;
        $qrData = route('verify', ['code' => $payload], true);
        $qr = app(\App\Services\QrCodeService::class)->pngDataUri($qrData, 3);

        $org = \App\Models\Setting::get('church.name', 'Open Gate Camp Mission');

        $html = view('accounting.ticket', [
            'attendee' => $attendee,
            'event'    => $attendee->event,
            'qr'       => $qr,
            'org'      => $org,
            'logoPath' => public_path('logo.png'),
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 350],         // 80mm wide, generous height so content is never clipped
            'margin_left'  => 4,
            'margin_right' => 4,
            'margin_top'   => 3,
            'margin_bottom' => 3,
            'fontDir' => [__DIR__.'/../../vendor/mpdf/mpdf/ttfonts', storage_path('fonts')],
            'tempDir' => storage_path('app/private/mpdf'),
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('Ticket-'.$attendee->getTicketNo().'.pdf', 'I');
    }

    public function sendTicketSms(EventAttendee $attendee)
    {
        abort_unless($attendee->hasCompletedContribution(), 422);

        $attendee->loadMissing('event');
        $this->ensureTicket($attendee);

        if (empty($attendee->phone)) {
            return back()->with('error', 'No phone number on file for this attendee — SMS not sent.');
        }

        $sms = new SmsService();
        $msg = "Hello {$attendee->name}, your ticket for {$attendee->event?->title} is ready.\nTicket: {$attendee->getTicketNo()}\nComing from: {$attendee->getRegionLabel()}\nPresent this ticket at the gate. — Open Gate Camp Mission";
        $result = $sms->send($attendee->phone, $msg);

        $attendee->update([
            'ticket_sent_at' => $result['success'] ? now() : $attendee->ticket_sent_at,
        ]);

        Message::create([
            'channel'        => 'sms',
            'recipients'     => $attendee->name,
            'phone'          => $attendee->phone,
            'subject'        => null,
            'message'        => $msg,
            'status'         => $result['success'] ? 'sent' : 'failed',
            'api_message_id' => $result['api_message_id'],
            'api_response'   => $result['raw'],
            'created_by'     => auth()->user()?->name,
        ]);

        AuditLog::record($result['success'] ? 'Sent ticket SMS' : 'Failed ticket SMS', 'Events', "{$attendee->getTicketNo()} — {$attendee->phone}");

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success'] ? "Ticket ({$attendee->getTicketNo()}) SMS sent to {$attendee->name}." : 'Ticket SMS failed to send.');
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

        // Time-slotted agenda items (sessions) for the displayed month.
        $sessions = EventSession::with('event')
            ->whereBetween('session_date', [$start, $end])
            ->orderBy('session_date')->orderBy('start_time')->get();

        $sessionsByDay = [];
        foreach ($sessions as $s) {
            $sessionsByDay[$s->session_date->format('Y-m-d')][] = $s;
        }

        return view('calendar.index', [
            'eventsByDay' => $eventsByDay,
            'sessionsByDay' => $sessionsByDay,
            'campEvents' => $events,
            'today' => now()->startOfDay(),
            'monthDate' => $date,
            'prevMonth' => (clone $date)->modify('-1 month')->format('Y-m'),
            'nextMonth' => (clone $date)->modify('+1 month')->format('Y-m'),
        ]);
    }

    /**
     * Plan a time-slotted agenda item for a specific day & hours on the calendar.
     */
    public function storeCalendarSession(Request $request)
    {
        $data = $request->validate([
            'session_date'   => 'required|date',
            'title'          => 'required|string|max:255',
            'start_time'     => 'required',
            'end_time'       => 'required|after:start_time',
            'venue'          => 'nullable|string|max:255',
            'speaker'        => 'nullable|string|max:255',
            'facilitator'    => 'nullable|string|max:255',
            'category'       => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'event_id'       => 'nullable|exists:events,id',
        ]);

        $event = ($data['event_id'] ?? null)
            ? Event::find($data['event_id'])
            : Event::where('event_type', 'camp')->latest('id')->first();

        $data['event_id'] = $event?->id;
        $data['sort_order'] = (((int) EventSession::where('event_id', $event?->id)->max('sort_order')) + 1);
        $data['session_date'] = $data['session_date'];

        $session = EventSession::create($data);
        AuditLog::record('Planned calendar activity', 'Calendar',
            $session->title.' — '.$session->session_date?->format('Y-m-d')
            .($session->start_time ? ' '.$session->start_time.'-'.($session->end_time ?? '') : ''));

        return back()->with('success', 'Activity planned for '.$session->session_date?->format('d M Y').'.');
    }

    /**
     * Printable timetable — scope: month (default), a single day, or the whole programme.
     */
    public function timetable(Request $request)
    {
        $scope = $request->query('scope', 'month');
        $date  = $request->query('date') ? date_create($request->query('date')) : now();
        $month = $request->query('month', $date->format('Y-m'));

        if ($scope === 'day') {
            $sessions = EventSession::with('event')
                ->whereDate('session_date', $date->format('Y-m-d'))
                ->orderBy('start_time')->get();
            $groups = [$date->format('Y-m-d') => $sessions];
        } elseif ($scope === 'programme') {
            $sessions = EventSession::with('event')
                ->orderBy('session_date')->orderBy('start_time')->get();
            $groups = $sessions->groupBy(fn ($s) => $s->session_date?->format('Y-m-d'))->all();
        } else {
            $start = date_create($month.'-01') ?: now()->startOfMonth();
            $end = (clone $start)->modify('last day of this month');
            $sessions = EventSession::with('event')
                ->whereBetween('session_date', [$start, $end])
                ->orderBy('session_date')->orderBy('start_time')->get();
            $groups = $sessions->groupBy(fn ($s) => $s->session_date?->format('Y-m-d'))->all();
        }

        return view('calendar.timetable', [
            'scope' => $scope,
            'groups' => $groups,
            'monthDate' => date_create($month.'-01') ?: now()->startOfMonth(),
            'today' => now(),
        ]);
    }

    private function fellowshipList(): array
    {
        $raw = (string) \App\Models\Setting::get('fellowships.list', '');
        $list = collect(explode("\n", $raw))
            ->map(fn ($f) => trim($f))
            ->filter()
            ->values()
            ->all();

        return $list ?: [
            'MoCU',
            'MWECAU',
            'KCMC University',
            'SMMUCo',
            'Mwenge Catholic University – Hedaru Campus',
            'SMMUCo – Mwika Centre',
            'KICHAS',
            'Northern College of Health and Allied Sciences',
            'Kilimanjaro School of Pharmacy',
            'Kilema College of Health Sciences',
            'Kibosho Institute of Health and Allied Sciences',
            'Faraja Health Training Institute',
            'Zawadi Memorial Health Training Institute',
            'Moshi Regional Vocational Training Centre',
            'FITI',
            'Kilimanjaro Agricultural Training Centre',
            'College of African Wildlife Management – Mweka',
            'Kilacha Agriculture and Livestock Training Institute',
            'TRITA – Moshi',
            'Tanzania Police School – Moshi',
            'Mwika Training College',
            'Moshi Institute of Technology (TAREO)',
            'Saint Luke Foundation – Kilimanjaro School of Pharmacy',
            'KCMC School of Nursing',
            'KCMC School of Physiotherapy',
            'Udzungwa Mountains College Trust',
            'Ewaso Maasai College Trust',
            'Northern Highlands Teachers\' College',
            'YCS College',
            'The Amazon College',
            'NM-AIST',
            'UoA',
            'TUMA',
            'SAUT – Arusha Centre',
            'ATC',
            'Arusha Adventist College',
            'Arusha Institute of Business Studies',
            'Arusha East African Training Institute',
            'National College of Tourism – Arusha',
            'Forestry Training Institute – Olmotonyi',
            'ESAMI',
            'IAA',
            'Kilimanjaro Institute of Health Sciences',
            'Centre for Educational Development in Health – Arusha',
            'City College of Health and Allied Sciences – Arusha',
            'Medical Missionaries of Mary School of Pharmaceutical Sciences',
            'Legacy College of Tourism and Business Studies',
            'MEGA College',
            'Ecassa Institute of Social Protection',
            'Fanikiwa Journalism School',
            'Tanzania Gemological Centre',
            'VHTTI',
            'Jr Institute of Information Technology',
            'Baptist Bible College and Community Center',
            'Savanna Bridge College',
            'Starlink Vocational Training Center – Arusha',
            'Dr. Richard Raj College of Health and Allied Sciences – Arusha',
            'Excellent College of Health and Allied Sciences – Arusha',
            'Arusha Lutheran Medical Training Centre',
            'Kilimanjaro International Institute for Telecommunications, Electronics and Computers',
            'Habari Maalum College',
            'Arusha College of Administration',
            'Other',
        ];
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