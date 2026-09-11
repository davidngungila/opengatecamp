<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventSession;
use App\Models\Fellowship;
use App\Models\Member;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Services\AccountingPostingService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    // ── Attendees ───────────────────────────────────────
    public function attendees(Request $request)
    {
        $eventSlug = (string) $request->query('event');
        $eventId = $eventSlug !== ''
            ? (Event::where('slug', $eventSlug)->value('id') ?? (ctype_digit($eventSlug) ? (int) $eventSlug : null))
            : null;
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = EventAttendee::with(['event', 'member', 'fellowship']);

        $user = auth()->user();
        if ($user?->isCommitteeMember()) {
            $query->where('registered_by', $user->name);
        } elseif ($user?->isFellowshipLeader()) {
            $fIds = $user->fellowships()->pluck('fellowships.id');
            // Fellowship leaders see only registrations for their assigned fellowship(s) — others remain general
            $query->whereIn('fellowship_id', $fIds);
        }

        $query->when($eventId, fn ($qr) => $qr->where('event_id', $eventId))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")));

        $attendees = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $totalsQuery = EventAttendee::query();
        if ($user?->isCommitteeMember()) {
            $totalsQuery->where('registered_by', $user->name);
        } elseif ($user?->isFellowshipLeader()) {
            $fIds2 = $user->fellowships()->pluck('fellowships.id');
            $totalsQuery->whereIn('fellowship_id', $fIds2);
        }

        $isFellowshipLeader = $user?->isFellowshipLeader() && ! in_array($user?->role?->name, ['Super Administrator', 'Chairperson'], true);
        if ($isFellowshipLeader) {
            $myFellowships = $user->fellowships()->orderBy('name')->get(['id', 'name', 'university']);
            $fellowshipsForSelect = $myFellowships->pluck('name')->all();
        } else {
            $myFellowships = collect();
            $fellowshipsForSelect = $this->fellowshipList();
        }
        // Build pickup auto-fill map: diocese (Arusha/Moshi) determines Coming From; fallback to most common pickup or heuristic
        $settingsDioceseMap = [];
        $rawDiocese = (string) \App\Models\Setting::get('fellowships.dioceses', '');
        if ($rawDiocese !== '') {
            $decoded = json_decode($rawDiocese, true);
            if (is_array($decoded)) $settingsDioceseMap = $decoded;
        }
        $pickupMap = [];
        foreach ($fellowshipsForSelect as $fname) {
            $fellow = \App\Models\Fellowship::where('name', $fname)->first(['id', 'name', 'university', 'diocese']);
            $diocese = $fellow?->diocese ?: ($settingsDioceseMap[$fname] ?? null);
            if ($diocese) {
                $pickupMap[$fname] = strtolower($diocese) === 'moshi' ? 'moshi' : 'arusha';
                continue;
            }
            if ($fellow) {
                $common = EventAttendee::where('fellowship_id', $fellow->id)->select('pickup_location')->groupBy('pickup_location')->selectRaw('pickup_location, COUNT(*) as c')->orderByDesc('c')->value('pickup_location');
                if (! $common) {
                    $hay = strtolower($fellow->university.' '.$fellow->name);
                    $common = str_contains($hay, 'moshi') ? 'moshi' : 'arusha';
                }
                $pickupMap[$fname] = $common ?: 'arusha';
            } else {
                $hay = strtolower($fname);
                $pickupMap[$fname] = str_contains($hay, 'moshi') ? 'moshi' : 'arusha';
            }
        }

        return view('attendees.index', [
            'attendees' => $attendees,
            'events' => Event::orderByDesc('start_date')->get(),
            'members' => Member::active()->orderBy('name')->get(),
            'statuses' => EventAttendee::statuses(),
            'defaultFee' => 10000,
            'pickupLocations' => ['arusha' => 'Arusha', 'moshi' => 'Moshi'],
            'fellowships' => $fellowshipsForSelect,
            'filters' => compact('eventSlug', 'status', 'q'),
            'totals' => [
                'registered' => (clone $totalsQuery)->count(),
                'confirmed' => (clone $totalsQuery)->whereIn('status', ['confirmed', 'attended'])->count(),
                'attended' => (clone $totalsQuery)->where('status', 'attended')->count(),
            ],
            'isFellowshipLeader' => $isFellowshipLeader,
            'myFellowships' => $myFellowships,
            'pickupMap' => $pickupMap,
        ]);
    }

    public function exportAttendeesPdf(Request $request)
    {
        $eventSlug = (string) $request->query('event');
        $eventId = $eventSlug !== ''
            ? (Event::where('slug', $eventSlug)->value('id') ?? (ctype_digit($eventSlug) ? (int) $eventSlug : null))
            : null;
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = EventAttendee::with(['event', 'member', 'fellowship']);

        $user = auth()->user();
        if ($user?->isCommitteeMember()) {
            $query->where('registered_by', $user->name);
        } elseif ($user?->isFellowshipLeader()) {
            $fIds = $user->fellowships()->pluck('fellowships.id');
            $query->whereIn('fellowship_id', $fIds);
        }

        $query->when($eventId, fn ($qr) => $qr->where('event_id', $eventId))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")));

        $attendees = $query->orderByDesc('created_at')->get();

        $statusLabel = EventAttendee::statuses()[$status] ?? null;
        $filters = array_filter([
            'Event' => Event::find($eventId)?->title ?? 'All',
            'Status' => $statusLabel ?? 'All',
            'Search' => $q !== '' ? $q : null,
        ]);

        $rows = $attendees->map(function ($a) {
            $bal = ($a->fee_amount !== null) ? max(0, $a->fee_amount - ($a->amount_paid ?? 0)) : null;
            return [
                'name' => $a->name ?? '—',
                'phone' => $a->phone ?? '—',
                'event' => $a->event?->title ?? '—',
                'paid' => number_format($a->amount_paid ?? 0),
                'balance' => $bal !== null ? number_format($bal) : '—',
                'status' => $a->getStatusLabel(),
                'registered' => $a->registered_on?->format('d M Y') ?? '—',
            ];
        })->all();

        $columns = [
            ['label' => 'Attendee', 'key' => 'name'],
            ['label' => 'Phone', 'key' => 'phone'],
            ['label' => 'Event', 'key' => 'event'],
            ['label' => 'Paid (TZS)', 'key' => 'paid', 'align' => 'right'],
            ['label' => 'Balance', 'key' => 'balance', 'align' => 'right'],
            ['label' => 'Status', 'key' => 'status'],
            ['label' => 'Registered', 'key' => 'registered'],
        ];

        $totals = [
            ['label' => 'Attendees', 'value' => number_format($attendees->count())],
            ['label' => 'Total Paid', 'value' => 'TZS '.number_format($attendees->sum('amount_paid'))],
        ];

        $mpdf = app(\App\Services\ReportPdfService::class)->generate(
            ['title' => 'Attendee Registrations Report', 'subtitle' => 'Complete registration list', 'filters' => $filters],
            $columns,
            $rows,
            $totals
        );

        $filename = 'Attendee-Registrations-Report-'.now()->format('Ymd-His').'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
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
            'fellowship_id' => 'nullable|exists:fellowships,id',
            'is_paid' => 'nullable|in:0,1',
            'amount_paid' => 'nullable|numeric|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank,mobile',
            'pickup_location' => 'nullable|in:arusha,moshi',
            'status' => 'required|in:pending,confirmed,attended,no_show,cancelled',
            'notes' => 'nullable|string',
            'send_sms' => 'nullable|in:1,on,true',
        ]);

        $sendSms = ($data['send_sms'] ?? '') ? true : false;
        $isPaid = ((string) ($data['is_paid'] ?? '0')) === '1';
        unset($data['send_sms'], $data['is_paid']);

        if (empty($data['name']) && ! $request->filled('name')) {
            return back()->with('error', 'Attendee name is required.');
        }

        if (! empty($data['fellowship_id']) && empty($data['fellowship'])) {
            $data['fellowship'] = Fellowship::find($data['fellowship_id'])?->name;
        }

        $event = Event::find($data['event_id'] ?? null) ?? Event::currentCamp();

        if (! $event) {
            return back()->with('error', 'No event configured. Set the current event under Settings → General → Event Settings.');
        }

        $currentUser = auth()->user();
        // Fellowship leaders: enforce fellowship scoping (only Registrations are per-fellowship)
        if ($currentUser?->isFellowshipLeader() && ! in_array($currentUser?->role?->name, ['Super Administrator', 'Chairperson'], true)) {
            $myFIds = $currentUser->fellowships()->pluck('fellowships.id');
            if ($myFIds->isEmpty()) {
                return back()->with('error', 'You are not assigned to any fellowship — contact the committee.')->withInput();
            }
            if (empty($data['fellowship_id'])) {
                if ($myFIds->count() === 1) {
                    $data['fellowship_id'] = $myFIds->first();
                    $data['fellowship'] = Fellowship::find($data['fellowship_id'])?->name;
                } else {
                    return back()->with('error', 'Please select a fellowship for this registration.')->withInput();
                }
            } elseif (! $myFIds->contains((int) $data['fellowship_id'])) {
                return back()->with('error', 'You can only register members for your assigned fellowship(s).')->withInput();
            }
            if (! empty($data['fellowship_id']) && empty($data['fellowship'])) {
                $data['fellowship'] = Fellowship::find($data['fellowship_id'])?->name;
            }
        }

        $data['event_id'] = $event->id;
        $data['registered_on'] = now()->toDateString();
        $data['registered_by'] = $currentUser?->name;

        if (empty($data['fee_amount'])) {
            $data['fee_amount'] = (float) $event->registration_fee > 0 ? $event->registration_fee : 10000;
        }

        if ($isPaid) {
            if ((float) ($data['amount_paid'] ?? 0) <= 0 || empty($data['payment_method'])) {
                return back()->with('error', 'When marking the attendee as paid, enter the amount paid and the payment method.')->withInput();
            }
        } else {
            $data['amount_paid'] = 0;
            $data['payment_method'] = null;
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

        $fellowshipName = $data['fellowship'] ?? Fellowship::find($data['fellowship_id'] ?? null)?->name;
        $byFrom = $fellowshipName ? "Recorded by ".($currentUser?->name ?? '—')." from {$fellowshipName} — " : "";
        AuditLog::record('Registered attendee', 'Events', $byFrom."{$event->title} — {$data['name']}");

        if ($sendSms && ! empty($attendee->phone)) {
            $sms = new SmsService();
            if ($sms->isConfigured()) {
                $regMsg = MessageTemplate::forUsage('attendee_registered', [
                    'name'  => $attendee->name,
                    'event' => $event->title,
                    'year'  => $event->start_date?->format('Y') ?: date('Y'),
                ]) ?? "Hello ".MessageTemplate::firstName($attendee->name).",\nYou are registered for \"{$event->title}\" at {$event->venue}. We look forward to seeing you! — OpenGate Camp Connect";
                $result = $sms->send($attendee->phone, $regMsg);
                Message::create([
                    'channel'          => 'sms',
                    'recipients'       => $attendee->name,
                    'phone'            => $attendee->phone,
                    'subject'          => null,
                    'message'          => $regMsg,
                    'status'           => $result['success'] ? 'sent' : 'failed',
                    'api_message_id'   => $result['api_message_id'],
                    'api_response'     => $result['raw'],
                    'created_by'       => auth()->user()?->name,
                ]);
                AuditLog::record($result['success'] ? 'Sent registration SMS' : 'Failed registration SMS', 'Communication', "{$attendee->name} ({$attendee->phone})");

                if ($isPaid) {
                    $payMsg = MessageTemplate::forUsage('attendee_payment', [
                        'name'   => $attendee->name,
                        'event'  => $event->title,
                        'year'   => $event->start_date?->format('Y') ?: date('Y'),
                        'amount' => number_format((float) $data['amount_paid']),
                    ]) ?? "Hello ".MessageTemplate::firstName($attendee->name).",\nWe have received your payment of TZS ".number_format((float) $data['amount_paid'])
                        ." for \"{$event->title}\". Thank you for your support and generosity! — OpenGate Camp Connect";
                    $result = $sms->send($attendee->phone, $payMsg);
                    Message::create([
                        'channel'          => 'sms',
                        'recipients'       => $attendee->name,
                        'phone'            => $attendee->phone,
                        'subject'          => null,
                        'message'          => $payMsg,
                        'status'           => $result['success'] ? 'sent' : 'failed',
                        'api_message_id'   => $result['api_message_id'],
                        'api_response'     => $result['raw'],
                        'created_by'       => auth()->user()?->name,
                    ]);
                    AuditLog::record($result['success'] ? 'Sent payment-received SMS' : 'Failed payment-received SMS', 'Communication', "{$attendee->name} ({$attendee->phone})");
                }
            }
        }

        $notice = "Attendee {$data['name']} registered.";
        if ($isPaid) {
            $notice .= ' Payment of TZS '.number_format((float) $data['amount_paid']).' recorded.';
        }
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
                        ? number_format($balance)
                        : '';
                    $msg = MessageTemplate::forUsage('attendee_payment', [
                        'name'   => $attendee->name,
                        'event'  => $attendee->event?->title,
                        'year'   => $attendee->event?->start_date?->format('Y') ?: date('Y'),
                        'amount' => number_format($amount),
                    ]) ?? "Hello ".MessageTemplate::firstName($attendee->name).",\nWe have received your payment of TZS ".number_format($amount)
                        ." for \"{$attendee->event?->title}\". Thank you for your support and generosity!".($remaining !== '' ? " Your remaining balance is TZS {$remaining}." : '')
                        ." — OpenGate Camp Connect";

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

    public function updateAttendeeStatus(Request $request, EventAttendee $attendee)
    {
        $user = auth()->user();
        if ($user?->isFellowshipLeader() && ! in_array($user?->role?->name, ['Super Administrator', 'Chairperson'], true)) {
            $fIds = $user->fellowships()->pluck('fellowships.id');
            if (! $attendee->fellowship_id || ! $fIds->contains((int) $attendee->fellowship_id)) {
                abort(403, 'You can only update status for your own fellowship registrations.');
            }
        } elseif ($user?->isCommitteeMember()) {
            if ($attendee->registered_by !== $user->name) {
                abort(403, 'You can only update your own registrations.');
            }
        }

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,attended,no_show,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $attendee->status;
        $newStatus = $data['status'];
        $notes = $data['notes'] ?? null;
        $notesChanged = $request->has('notes') && $notes !== $attendee->notes;

        if ($oldStatus === $newStatus && ! $notesChanged) {
            return back()->with('info', "Status is already ".EventAttendee::statuses()[$newStatus].".");
        }

        $updateData = ['status' => $newStatus];
        if ($request->has('notes')) {
            $updateData['notes'] = $notes;
        }
        $attendee->update($updateData);

        if ($newStatus === 'attended') {
            $attendee->update(['checked_in_at' => now(), 'checked_in_by' => $user?->name]);
        } elseif ($oldStatus === 'attended' && $newStatus !== 'attended') {
            $attendee->update(['checked_in_at' => null, 'checked_in_by' => null]);
        }

        // If marked attended and fully paid, ensure ticket exists
        if ($newStatus === 'attended' && $attendee->hasCompletedContribution()) {
            $this->ensureTicket($attendee);
        }

        $auditDetails = "{$attendee->name} — {$oldStatus} → {$newStatus}".($attendee->fellowship ? " ({$attendee->fellowship})" : "");
        if ($notesChanged) {
            $auditDetails .= $notes !== null && $notes !== '' ? " — note updated" : " — note cleared";
        }
        AuditLog::record('Updated attendee status', 'Events', $auditDetails);

        return back()->with('success', "Status for {$attendee->name} updated to ".EventAttendee::statuses()[$newStatus].".");
    }

    public function apiAttendeeTransactions(Request $request, EventAttendee $attendee)
    {
        $user = auth()->user();
        if ($user?->isFellowshipLeader() && ! in_array($user?->role?->name, ['Super Administrator', 'Chairperson'], true)) {
            $fIds = $user->fellowships()->pluck('fellowships.id');
            if (! $attendee->fellowship_id || ! $fIds->contains((int) $attendee->fellowship_id)) {
                abort(403, 'You can only view transactions for your own fellowship registrations.');
            }
        } elseif ($user?->isCommitteeMember()) {
            if ($attendee->registered_by !== $user->name) {
                abort(403, 'You can only view your own registrations.');
            }
        }

        $attendee->loadMissing('event');

        // Find all posted journal entries that belong to this attendee
        // Primary link is journal_entry_id (latest), plus search by description containing attendee name + event
        $entries = \App\Models\JournalEntry::where('status', 'posted')
            ->where(function ($q) use ($attendee) {
                $q->where('id', $attendee->journal_entry_id)
                  ->orWhere(function ($qq) use ($attendee) {
                      $qq->where('description', 'like', '%'.addcslashes($attendee->name, '%_').'%');
                      if ($attendee->event?->title) {
                          $qq->where('description', 'like', '%'.addcslashes($attendee->event->title, '%_').'%');
                      }
                  });
            })
            ->with(['lines.account'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        // Deduplicate by id (in case both conditions match same entry)
        $entries = $entries->unique('id')->values();

        // If no entries found but attendee has amount_paid, at least show a synthetic entry for display
        $transactions = $entries->map(function ($entry) use ($attendee) {
            $amount = max((float) $entry->lines->sum('debit'), (float) $entry->lines->sum('credit'));
            // Fallback to attendee amount if entry amount is 0
            if ($amount == 0) $amount = (float) $attendee->amount_paid;
            return [
                'id' => $entry->id,
                'entry_no' => $entry->entry_no,
                'entry_date' => $entry->entry_date?->format('d M Y'),
                'description' => $entry->description,
                'reference' => $entry->reference,
                'amount' => round($amount, 2),
                'amount_formatted' => number_format($amount),
                'status' => $entry->status,
                'receipt_url' => route('accounting.transactions.receipt', $entry).'?inline=1',
                'lines' => $entry->lines->map(fn ($l) => [
                    'account' => $l->account?->name ?? '—',
                    'code' => $l->account?->code ?? '—',
                    'debit' => (float) $l->debit,
                    'credit' => (float) $l->credit,
                ]),
            ];
        });

        return response()->json([
            'attendee' => [
                'id' => $attendee->hashed_id,
                'name' => $attendee->name,
                'phone' => $attendee->phone,
                'amount_paid' => (float) $attendee->amount_paid,
                'fee_amount' => $attendee->fee_amount !== null ? (float) $attendee->fee_amount : null,
            ],
            'transactions' => $transactions,
            'count' => $transactions->count(),
        ]);
    }

    // ── Tickets ─────────────────────────────────────────
    private function ensureTicket(EventAttendee $attendee): string
    {
        return $attendee->getTicketNo(); // lazily issues + persists a short 6-char code
    }

    public function ticketPdf(EventAttendee $attendee)
    {
        $attendee->loadMissing('event');
        $this->ensureTicket($attendee);

        $payload = 'OGCM|TICKET|'.$attendee->getTicketNo().'|'.$attendee->event?->slug;
        $qrData = route('verify', ['code' => $payload], true);
        $qr = app(\App\Services\QrCodeService::class)->pngDataUri($qrData, 3);

        $org = \App\Models\Setting::get('church.name', 'OpenGate Camp Connect');

        $html = view('accounting.ticket', [
            'attendee' => $attendee,
            'event'    => $attendee->event,
            'qr'       => $qr,
            'org'      => $org,
            'logoPath' => public_path('logo.png'),
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 350],
            'margin_left'  => 4,
            'margin_right' => 4,
            'margin_top'   => 3,
            'margin_bottom' => 3,
            'tempDir' => storage_path('app/private/mpdf'),
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('Ticket-'.$attendee->getTicketNo().'.pdf', 'I');
    }

    public function sendTicketSms(EventAttendee $attendee)
    {
        $attendee->loadMissing('event');
        $this->ensureTicket($attendee);

        if (empty($attendee->phone)) {
            return back()->with('error', 'No phone number on file for this attendee — SMS not sent.');
        }

        $sms = new SmsService();
        $msg = "Hello ".MessageTemplate::firstName($attendee->name).", your ticket for {$attendee->event?->title} is ready.\nTicket: {$attendee->getTicketNo()}\nComing from: {$attendee->getRegionLabel()}\nPresent this ticket at the gate. — OpenGate Camp Connect";
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

        // Time-slotted agenda items (sessions) for the displayed month.
        $sessions = EventSession::with('event')
            ->whereBetween('session_date', [$start, $end])
            ->orderBy('session_date')->orderBy('start_time')->get();

        $sessionsByDay = [];
        foreach ($sessions as $s) {
            $sessionsByDay[$s->session_date->format('Y-m-d')][] = $s;
        }

        return view('calendar.index', [
            'sessionsByDay' => $sessionsByDay,
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
            'end_date'       => 'nullable|date|after_or_equal:session_date',
            'title'          => 'required|string|max:255',
            'start_time'     => 'required',
            'end_time'       => 'required|after:start_time',
            'venue'          => 'nullable|string|max:255',
            'speaker'        => 'nullable|string|max:255',
            'facilitator'    => 'nullable|string|max:255',
            'category'       => 'nullable|string|max:255',
            'description'    => 'nullable|string',
        ]);

        $event = Event::currentCamp();

        if (! $event) {
            $event = Event::create([
                'title'      => 'Open Gate Camp',
                'event_type' => 'camp',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date'   => now()->endOfMonth()->toDateString(),
                'status'     => 'planned',
                'featured'   => true,
            ]);
            AuditLog::record('Auto-created default camp event', 'Calendar', $event->title);
        }

        $start = Carbon::parse($data['session_date'])->startOfDay();
        $end = ! empty($data['end_date'])
            ? Carbon::parse($data['end_date'])->startOfDay()
            : (clone $start);
        unset($data['end_date']);

        if ($end->lt($start)) {
            $end = clone $start;
        }

        $lastSort = (int) EventSession::where('event_id', $event->id)->max('sort_order');
        $created = 0;
        for ($day = clone $start; $day->lte($end); $day->addDay()) {
            $data['event_id'] = $event->id;
            $data['sort_order'] = ++$lastSort;
            $data['session_date'] = $day->format('Y-m-d');
            EventSession::create($data);
            $created++;
        }

        AuditLog::record('Planned calendar activity', 'Calendar',
            $data['title']
            .' — '.$start->format('Y-m-d').($created > 1 ? ' to '.$end->format('Y-m-d') : '')
            .($data['start_time'] ? ' '.$data['start_time'].'-'.($data['end_time'] ?? '') : '')
            .($created > 1 ? " ({$created} days)" : ''));

        return back()->with('success', 'Activity planned for '.$created.' day'.($created > 1 ? 's' : '')
            .' ('.$start->format('d M Y').($created > 1 ? ' → '.$end->format('d M Y') : '').').');
    }

    public function updateCalendarSession(Request $request, EventSession $session)
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
        ]);

        $session->update($data);
        AuditLog::record('Updated calendar activity', 'Calendar',
            $session->title.' — '.$session->session_date?->format('Y-m-d')
            .($session->start_time ? ' '.$session->start_time.'-'.($session->end_time ?? '') : ''));

        return back()->with('success', 'Activity updated for '.$session->session_date?->format('d M Y').'.');
    }

    public function destroyCalendarSession(Request $request, EventSession $session)
    {
        AuditLog::record('Removed calendar activity', 'Calendar',
            $session->title.' — '.$session->session_date?->format('Y-m-d'));

        $session->delete();

        return back()->with('success', 'Activity removed from the calendar.');
    }

    /**
     * Day activity planner — schedule an activity for a specific day and hours.
     */
    public function planner(Request $request)
    {
        $parsed = $request->query('date') ? date_create($request->query('date')) : now();
        $date = ($parsed ?: now())->setTime(0, 0, 0);

        $sessions = EventSession::with('event')
            ->whereDate('session_date', $date->format('Y-m-d'))
            ->orderBy('start_time')
            ->get();

        $sessionsJson = $sessions->mapWithKeys(fn ($s) => [$s->id => [
            'id'          => $s->id,
            'session_date'=> $s->session_date?->format('Y-m-d'),
            'title'       => $s->title,
            'start_time'  => $s->start_time ? substr($s->start_time, 0, 5) : '',
            'end_time'    => $s->end_time ? substr($s->end_time, 0, 5) : '',
            'venue'       => $s->venue,
            'category'    => $s->category,
            'speaker'     => $s->speaker,
            'facilitator' => $s->facilitator,
            'description' => $s->description,
            'event_title' => $s->event?->title,
        ],
        ])->values()->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);

        return view('calendar.planner', [
            'date' => $date,
            'sessions' => $sessions,
            'sessionsJson' => $sessionsJson,
            'prevDate' => (clone $date)->modify('-1 day')->format('Y-m-d'),
            'nextDate' => (clone $date)->modify('+1 day')->format('Y-m-d'),
            'today' => now(),
        ]);
    }

    private function fellowshipList(): array
    {
        $fellowships = Fellowship::active()->orderBy('name')->pluck('name')->all();

        if ($fellowships) {
            return $fellowships;
        }

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
}

    