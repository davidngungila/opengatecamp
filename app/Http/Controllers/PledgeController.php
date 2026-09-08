<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Member;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Services\AccountingPostingService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PledgeController extends Controller
{
    public function index(Request $request)
    {
        $eventSlug = (string) $request->query('event');
        $eventId = $eventSlug !== ''
            ? (Event::where('slug', $eventSlug)->value('id') ?? (ctype_digit($eventSlug) ? (int) $eventSlug : null))
            : null;
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = Pledge::with(['event', 'member', 'payments']);

        $user = auth()->user();
        if ($user?->isCommitteeMember()) {
            $query->where('created_by', $user->name);
        }

        $query->when($eventId, fn ($qr) => $qr->where('event_id', $eventId))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('pledge_no', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")));

        $pledges = $query->orderByDesc('pledge_date')->paginate(15)->withQueryString();

        $totalsQuery = Pledge::query();
        if ($user?->isCommitteeMember()) {
            $totalsQuery->where('created_by', $user->name);
        }

        $totals = [
            'pledged' => (clone $totalsQuery)->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('amount'),
            'paid' => (clone $totalsQuery)->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('paid_amount'),
            'outstanding' => (clone $totalsQuery)->whereIn('status', ['pending', 'partial'])
                ->get()->sum(fn ($p) => $p->getRemainingAttribute()),
        ];

        $campEvent = Event::currentCamp();

        return view('pledges.index', [
            'pledges' => $pledges,
            'events' => Event::orderByDesc('start_date')->get(),
            'campEvent' => $campEvent,
            'members' => Member::active()->orderBy('name')->get(),
            'statuses' => Pledge::statuses(),
            'filters' => compact('eventSlug', 'status', 'q'),
            'totals' => $totals,
        ]);
    }

    public function exportPledgesPdf(Request $request)
    {
        $eventSlug = (string) $request->query('event');
        $eventId = $eventSlug !== ''
            ? (Event::where('slug', $eventSlug)->value('id') ?? (ctype_digit($eventSlug) ? (int) $eventSlug : null))
            : null;
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = Pledge::with(['event', 'member']);

        $user = auth()->user();
        if ($user?->isCommitteeMember()) {
            $query->where('created_by', $user->name);
        }

        $query->when($eventId, fn ($qr) => $qr->where('event_id', $eventId))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('pledge_no', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")));

        $pledges = $query->orderByDesc('pledge_date')->get();

        $statusLabel = Pledge::statuses()[$status] ?? null;
        $filters = array_filter([
            'Event' => Event::find($eventId)?->title ?? 'All',
            'Status' => $statusLabel ?? 'All',
            'Search' => $q !== '' ? $q : null,
        ]);

        $rows = $pledges->map(function ($p) {
            return [
                'no' => $p->pledge_no,
                'name' => $p->name ?? '—',
                'phone' => $p->phone ?? '—',
                'event' => $p->event?->title ?? '—',
                'amount' => number_format($p->amount),
                'paid' => number_format($p->paid_amount),
                'remaining' => number_format(max(0, (float) $p->amount - (float) $p->paid_amount)),
                'status' => $p->status,
                'date' => $p->pledge_date?->format('d M Y') ?? '—',
            ];
        })->all();

        $columns = [
            ['label' => 'Pledge #', 'key' => 'no'],
            ['label' => 'Name', 'key' => 'name'],
            ['label' => 'Phone', 'key' => 'phone'],
            ['label' => 'Event', 'key' => 'event'],
            ['label' => 'Amount', 'key' => 'amount', 'align' => 'right'],
            ['label' => 'Paid', 'key' => 'paid', 'align' => 'right'],
            ['label' => 'Remaining', 'key' => 'remaining', 'align' => 'right'],
            ['label' => 'Status', 'key' => 'status'],
            ['label' => 'Date', 'key' => 'date'],
        ];

        $totals = [
            ['label' => 'Pledges', 'value' => number_format($pledges->count())],
            ['label' => 'Total Pledged', 'value' => 'TZS '.number_format($pledges->sum('amount'))],
            ['label' => 'Total Paid', 'value' => 'TZS '.number_format($pledges->sum('paid_amount'))],
        ];

        $mpdf = app(\App\Services\ReportPdfService::class)->generate(
            ['title' => 'Pledges Report', 'subtitle' => 'Complete pledge list', 'filters' => $filters],
            $columns,
            $rows,
            $totals
        );

        $filename = 'Pledges-Report-'.now()->format('Ymd-His').'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'pledge_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:pledge_date',
        ]);

        $data['event_id'] = $data['event_id'] ?? Event::currentCamp()?->id;

        if (empty($data['event_id'])) {
            return back()->with('error', 'No event configured. Set the current event under Settings → General → Event Settings.');
        }

        $data['pledge_no'] = Pledge::nextPledgeNo();
        $data['paid_amount'] = 0;
        $data['status'] = 'pending';
        $data['frequency'] = $data['frequency'] ?? 'one_time';
        $data['created_by'] = auth()->user()?->name;

        $pledge = Pledge::create($data);
        AuditLog::record('Created pledge', 'Pledges', "{$pledge->pledge_no} — {$pledge->name} ({$pledge->amount})");

        $notice = "Pledge {$pledge->pledge_no} recorded.";
        $notice .= $this->sendPledgeSms($pledge, 'thanks');

        return back()->with('success', $notice);
    }

    public function remind(Pledge $pledge)
    {
        $notice = $this->sendPledgeSms($pledge, 'remind');

        return back()->with(str_starts_with($notice, 'Reminder SMS sent') || str_starts_with($notice, 'Thank-you SMS sent') ? 'success' : 'error', $notice);
    }

    public function sendThanks(Pledge $pledge)
    {
        $notice = $this->sendPledgeSms($pledge, 'thanks');

        return back()->with(str_starts_with($notice, 'Thank-you SMS sent') ? 'success' : 'error', $notice);
    }

    private function sendPledgeSms(Pledge $pledge, string $type): string
    {
        if (empty($pledge->phone)) {
            return ' SMS skipped — no phone number on this pledge.';
        }

        $sms = new SmsService();

        if (! $sms->isConfigured()) {
            return ' SMS skipped — SMS API token not configured.';
        }

        $event = $pledge->event?->title ?? 'Open Gate Camp';
        $remaining = max(0, (float) $pledge->amount - (float) $pledge->paid_amount);
        $fulfilled = empty($remaining) || $pledge->status === 'fulfilled';

        $placeholders = [
            'name'      => $pledge->name,
            'event'     => $event,
            'amount'    => number_format($pledge->amount),
            'paid'      => number_format($pledge->paid_amount),
            'remaining' => number_format($remaining),
        ];

        if ($type === 'remind') {
            $msg = MessageTemplate::forUsage(
                $fulfilled ? 'pledge_fulfilled' : 'pledge_reminder',
                $placeholders
            ) ?? ($fulfilled
                ? "Asante ".MessageTemplate::firstName($pledge->name)."! Umekamilisha ahadi yako ya TZS ".number_format($pledge->amount)." kwa \"{$event}\". Mungu akubariki, na asante kwa moyo wako wa kutoa. — OpenGate Camp Connect"
                : "Reminder ".MessageTemplate::firstName($pledge->name).": ahadi yako ya TZS ".number_format($pledge->amount)." kwa \"{$event}\" ina salio la TZS ".number_format($remaining).". Tunakuomba ukamilishe ahadi yako. Asante! — OpenGate Camp Connect");
        } else {
            $msg = MessageTemplate::forUsage(
                $fulfilled ? 'pledge_fulfilled' : 'pledge_received',
                $placeholders
            ) ?? "Shukrani ".MessageTemplate::firstName($pledge->name).", tumepokea ahadi yako ya TZS ".number_format($pledge->amount)." kwa \"{$event}\". Tunakushukuru kwa moyo wako wa kutoa! Mungu akubariki. — OpenGate Camp Connect";
        }

        $result = $sms->send($pledge->phone, $msg);

        Message::create([
            'channel'          => 'sms',
            'recipients'       => $pledge->name,
            'phone'            => $pledge->phone,
            'subject'          => $type === 'remind' ? 'Pledge reminder' : 'Pledge thank-you',
            'message'          => $msg,
            'status'           => $result['success'] ? 'sent' : 'failed',
            'api_message_id'   => $result['api_message_id'],
            'api_response'     => $result['raw'],
            'created_by'       => auth()->user()?->name,
        ]);
        AuditLog::record($result['success'] ? 'Sent pledge '.$type.' SMS' : 'Failed pledge '.$type.' SMS', 'Communication', "{$pledge->pledge_no} — {$pledge->name} ({$pledge->phone})");

        if ($result['success']) {
            return $type === 'remind' ? " Reminder SMS sent to {$pledge->name}." : " Thank-you SMS sent to {$pledge->name}.";
        }

        return ' SMS failed ('.($result['status'] ?? 'error').').';
    }

    public function recordPayment(Request $request, Pledge $pledge)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:cash,bank,mobile',
            'reference' => 'nullable|string|max:255',
            'pay_date' => 'required|date',
        ]);

        $remaining = $pledge->getRemainingAttribute();
        $isFulfilled = $pledge->status === 'fulfilled' || $remaining <= 0;

        if ($isFulfilled) {
            $data['amount'] = max(1, $data['amount']);
        } elseif ($data['amount'] > $remaining) {
            $data['amount'] = min($data['amount'], $remaining);
        }

        $payment = DB::transaction(function () use ($data, $pledge, $isFulfilled) {
            $data['pledge_id'] = $pledge->id;
            $data['recorded_by'] = auth()->user()?->name;

            $posting = app(AccountingPostingService::class);
            $entry = $posting->postMoneyIn([
                'date' => $data['pay_date'],
                'description' => 'Pledge payment '.$pledge->pledge_no.' — '.$pledge->name,
                'reference' => $data['reference'] ?? null,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'incomeAccount' => $posting->incomeAccount('acct.pledge_income', '4020'),
            ]);

            $data['journal_entry_id'] = $entry->id;
            $payment = PledgePayment::create($data);

            $paid = $pledge->payments()->sum('amount');

            $pledge->update([
                'paid_amount' => $paid,
                'status' => $paid >= $pledge->amount ? 'fulfilled' : ($paid > 0 ? 'partial' : 'pending'),
            ]);

            return $payment;
        });

        AuditLog::record('Recorded pledge payment', 'Pledges', "{$pledge->pledge_no} — {$payment->amount}");

        $notice = "Payment of {$payment->amount} recorded.";

        if (! empty($pledge->phone)) {
            $sms = new SmsService();

            if ($sms->isConfigured()) {
                $event = $pledge->event?->title ?? 'Open Gate Camp';
                $remaining = $pledge->getRemainingAttribute();
                $fulfilled = $pledge->status === 'fulfilled';

                $placeholders = [
                    'name'      => $pledge->name,
                    'event'     => $event,
                    'amount'    => number_format($payment->amount),
                    'remaining' => number_format($remaining),
                ];

                $msg = MessageTemplate::forUsage(
                    $fulfilled ? 'pledge_fulfilled' : 'pledge_received',
                    $placeholders
                ) ?? "Asante ".MessageTemplate::firstName($pledge->name).", tumepokea mchango wako wa TSH ".number_format($payment->amount)
                    ." kuongezea ahadi yako. Kwaajili ya \"{$event}\". Mungu akubariki. — OpenGate Camp Connect";

                $result = $sms->send($pledge->phone, $msg);

                Message::create([
                    'channel'          => 'sms',
                    'recipients'       => $pledge->name,
                    'phone'            => $pledge->phone,
                    'subject'          => 'Pledge payment received',
                    'message'          => $msg,
                    'status'           => $result['success'] ? 'sent' : 'failed',
                    'api_message_id'   => $result['api_message_id'],
                    'api_response'     => $result['raw'],
                    'created_by'       => auth()->user()?->name,
                ]);
                AuditLog::record($result['success'] ? 'Sent pledge payment SMS' : 'Failed pledge payment SMS', 'Communication', "{$pledge->pledge_no} — {$pledge->name} ({$pledge->phone})");

                $notice .= $result['success']
                    ? " Thank-you SMS sent to {$pledge->name}."
                    : " SMS failed (".($result['status'] ?? 'error').").";
            } else {
                $notice .= ' SMS skipped — SMS API token not configured.';
            }
        } else {
            $notice .= ' SMS skipped — no phone number on this pledge.';
        }

        return back()->with('success', $notice);
    }

    public function paymentReceipt(Request $request, PledgePayment $payment)
    {
        $mode = $request->boolean('download') ? 'D' : 'I';

        $entry = $payment->journalEntry;
        if ($entry && $entry->status === 'posted') {
            $delegate = Request::create('/_receipt', 'GET', ['inline' => $mode === 'I' ? '1' : null]);

            return app(AccountingController::class)->receiptPdf($delegate, $entry);
        }

        $org = \App\Models\Setting::get('org.name', 'OpenGate Camp Connect');
        $receiptNo = 'RCP-P'.str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT);

        $html = view('pledges.receipt', [
            'payment' => $payment,
            'pledge' => $payment->pledge,
            'receiptNo' => $receiptNo,
            'org' => $org,
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

        $filename = 'Pledge-Receipt-'.$payment->id.'.pdf';

        return $mpdf->Output($filename, $mode);
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