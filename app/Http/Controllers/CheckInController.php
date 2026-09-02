<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EventAttendee;
use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    /**
     * Admission desk: scan a ticket (QR) or enter the 6-character ticket code.
     * Also shows the full list of admitted / not-yet-admitted attendees with search.
     */
    public function index(Request $request)
    {
        return view('admission.index', array_merge($this->listData($request), [
            'code' => (string) $request->query('code'),
            'attendee' => null,
            'result' => null,
        ]));
    }

    private function listData(Request $request): array
    {
        $tab = $request->query('tab', 'admitted');
        $q   = trim((string) $request->query('q'));

        $base = fn ($query) => $query->with('event')
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('ticket_no', 'like', "%{$q}%")
                ->orWhere('fellowship', 'like', "%{$q}%")));

        $admitted    = $base(EventAttendee::whereNotNull('checked_in_at'))->latest('checked_in_at')->paginate(15);
        $notAdmitted = $base(EventAttendee::whereNull('checked_in_at'))->latest()->paginate(15);

        return [
            'admitted'    => $admitted,
            'notAdmitted' => $notAdmitted,
            'tab' => in_array($tab, ['admitted', 'pending'], true) ? $tab : 'admitted',
            'q'   => $q,
        ];
    }

    /**
     * Look up an attendee from a scanned/entered ticket code.
     */
    public function lookup(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:255']);
        $attendee = $this->resolveAttendee($data['code']);

        $lists = $this->listData($request);

        if (! $attendee) {
            return view('admission.index', $lists + [
                'code' => $data['code'],
                'attendee' => null,
                'result' => 'not_found',
            ]);
        }

        return view('admission.index', $lists + [
            'code' => $data['code'],
            'attendee' => $attendee,
            'result' => 'found',
        ]);
    }

    /**
     * Admit the attendee (check-in) and send a welcome SMS.
     */
    public function admit(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:255']);
        $attendee = $this->resolveAttendee($data['code']);

        if (! $attendee) {
            return back()->with('error', 'Attendee not found for the given ticket code.');
        }

        if ($attendee->checked_in_at) {
            return back()->with('info', $attendee->name.' was already admitted at '.$attendee->checked_in_at?->format('d M Y H:i').'.');
        }

        $attendee->update([
            'status' => 'attended',
            'checked_in_at' => now(),
            'checked_in_by' => auth()->user()?->name,
        ]);

        AuditLog::record('Admitted attendee (scan)', 'Admission',
            "{$attendee->name} — ticket {$attendee->getTicketNo()} ({$attendee->event?->title})");

        return view('admission.index', $this->listData($request) + [
            'code' => $data['code'],
            'attendee' => $attendee->fresh()->load('event'),
            'result' => 'admitted',
            'sms' => $this->sendWelcomeSms($attendee),
        ]);
    }

    /**
     * Resolve an attendee from a ticket value in any of these forms:
     *  - verification URL (…/verify?code=OGCM%7CTICKET%7CCODE%7Cslug)
     *  - 6-char short code (e.g. 5R8DHY)
     *  - barcode form (*5R8DHY*)
     *  - QR payload form (OGCM|TICKET|CODE|slug)
     */
    private function resolveAttendee(string $input): ?EventAttendee
    {
        $value = trim((string) $input);

        // If it's a verification URL, pull the "code" query param (URL-decoded).
        if (preg_match('~[/?]verify\?~', $value) || (str_starts_with($value, 'http') && str_contains($value, 'code='))) {
            parse_str((string) parse_url($value, PHP_URL_QUERY), $q);
            if (! empty($q['code'])) {
                $value = urldecode($q['code']);
            }
        }

        $code = trim($value);

        // Strip barcode asterisks.
        if (str_starts_with($code, '*') && str_ends_with($code, '*')) {
            $code = substr($code, 1, -1);
        }

        // Extract ticket_no from a QR payload: OGCM|TICKET|<code>|<slug>
        if (str_contains($code, '|')) {
            $parts = explode('|', $code);
            if (($parts[0] ?? '') === 'OGCM' && ($parts[1] ?? '') === 'TICKET') {
                $code = $parts[2] ?? '';
            }
        }

        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return EventAttendee::where('ticket_no', $code)
            ->with('event')
            ->first();
    }

    /**
     * Send the welcome SMS on admission (best-effort; failures return notice text).
     */
    private function sendWelcomeSms(EventAttendee $attendee): array
    {
        if (empty($attendee->phone)) {
            return ['success' => false, 'reason' => 'no_phone'];
        }

        try {
            $sms = new SmsService();
            $msg = "Welcome {$attendee->name} to {$attendee->event?->title}! You have been admitted. Enjoy the camp! — Open Gate Camp Mission";
            $result = $sms->send($attendee->phone, $msg);

            Message::create([
                'channel'        => 'sms',
                'recipients'     => $attendee->name,
                'phone'          => $attendee->phone,
                'subject'        => null,
                'message'        => $msg,
                'status'         => $result['success'] ? 'sent' : 'failed',
                'api_message_id' => $result['api_message_id'] ?? null,
                'api_response'   => $result['raw'] ?? null,
                'created_by'     => auth()->user()?->name,
            ]);

            return $result;
        } catch (\Throwable $e) {
            return ['success' => false, 'reason' => 'error', 'error' => $e->getMessage()];
        }
    }
}