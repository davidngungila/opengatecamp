<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DigitalCard;
use App\Models\DigitalCardContribution;
use App\Models\DigitalCardRecipient;
use App\Models\Message;
use App\Models\Pledge;
use App\Models\Setting;
use App\Services\AccountingPostingService;
use App\Services\QrCodeService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;

class DigitalCardController extends Controller
{
    public function index(Request $request)
    {
        $card = $this->currentCard();

        $status = $request->query('status');
        $delivery = $request->query('delivery');
        $q = trim((string) $request->query('q'));

        $query = $this->applyInviteFilters(
            $card->recipients(),
            $status,
            $delivery,
            $q
        );

        $recipients = $query->latest('created_at')->paginate(15)->withQueryString();

        $totals = [
            'total' => $card->recipients()->count(),
            'invited' => $card->recipients()->where('status', 'invited')->count(),
            'failed' => $card->recipients()->where('status', 'failed')->count(),
            'pending' => $card->recipients()
                ->where(fn ($w) => $w->whereNull('status')->orWhere('status', 'pending'))
                ->count(),
            'delivered' => $card->recipients()->where('delivery_status', 'delivered')->count(),
        ];

        $filters = ['q' => $q, 'status' => $status, 'delivery' => $delivery];

        $inviteStatuses = DigitalCardRecipient::inviteStatuses();

        $currentEventName = (string) Setting::get('event.name', 'Open Gate Camp');
        $currentEventDate = Setting::get('event.start_date');
        $currentEventVenue = (string) Setting::get('event.venue', '');

        return view('digital-cards.index', compact(
            'card', 'recipients', 'totals', 'filters',
            'inviteStatuses', 'currentEventName', 'currentEventDate', 'currentEventVenue',
        ));
    }

    private function applyInviteFilters($query, $status, $delivery, $q)
    {
        if (in_array($status, ['invited', 'failed', 'pending'], true)) {
            if ($status === 'pending') {
                $query->where(fn ($w) => $w->whereNull('status')->orWhere('status', 'pending'));
            } else {
                $query->where('status', $status);
            }
        }

        if (in_array($delivery, ['delivered', 'undelivered', 'pending', 'unknown'], true)) {
            $query->where('delivery_status', $delivery);
        }

        if ($q !== '') {
            $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"));
        }

        return $query;
    }

    private function currentCard(): DigitalCard
    {
        $eventName = (string) Setting::get('event.name', 'Open Gate Camp');

        $data = [
            'title' => (string) Setting::get('digital_card.title', $eventName),
            'message' => (string) Setting::get('digital_card.message', 'Thank you for supporting Open Gate Camp. Contribute using the link below.'),
            'target_amount' => (float) (Setting::get('digital_card.target_amount') ?: 0),
            'background_color' => (string) (Setting::get('digital_card.background_color') ?: '#ffffff'),
            'accent_color' => (string) (Setting::get('digital_card.accent_color') ?: '#ffd700'),
            'cta_text' => (string) (Setting::get('digital_card.cta_text') ?: 'Contribute Now'),
            'sms_text' => (string) (Setting::get('digital_card.sms_text') ?: 'You are invited! View your digital card and contribute: {link}'),
            'image_path' => (string) (Setting::get('digital_card.background_image') ?: ''),
            'status' => (string) (Setting::get('digital_card.status') ?: 'active'),
            'card_type' => 'camp_invitation',
            'event_id' => null,
            'is_published' => (string) (Setting::get('digital_card.status') ?: 'active') === 'active',
        ];

        $card = DigitalCard::latest('id')->first();

        if (! $card) {
            $data['card_no'] = DigitalCard::nextCardNo();
            $data['hash'] = Str::random(32);
            $data['contributions_count'] = 0;
            $data['total_contributions'] = 0;
            $data['created_by'] = 'System (settings)';

            return DigitalCard::create($data);
        }

        $card->update($data);

        return $card->fresh();
    }

    public function addContribution(Request $request, DigitalCard $card)
    {
        $data = $request->validate([
            'contributor_name' => 'required|string|max:255',
            'contributor_phone' => 'nullable|string|max:20',
            'contributor_email' => 'nullable|email|max:255',
            'amount' => 'required|numeric|min:100',
            'method' => 'required|in:cash,bank,mobile',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data, $card) {
            $posting = app(AccountingPostingService::class);
            $entry = $posting->postMoneyIn([
                'date' => now()->format('Y-m-d'),
                'description' => "Digital card contribution — {$card->card_no}: {$card->title}",
                'reference' => $data['reference_no'] ?? null,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'incomeAccount' => $posting->incomeAccount('acct.pledge_income', '4020'),
            ]);

            DigitalCardContribution::create([
                ...$data,
                'digital_card_id' => $card->id,
                'journal_entry_id' => $entry->id,
                'status' => 'confirmed',
            ]);

            $card->increment('contributions_count');
            $card->increment('total_contributions', $data['amount']);
        });

        AuditLog::record(
            'Recorded digital card contribution',
            'Digital Cards',
            "{$card->card_no} — TZS ".number_format($data['amount']).' from '.$data['contributor_name']
        );

        return back()->with('success', 'TZS '.number_format($data['amount'])." contribution recorded for {$data['contributor_name']}.");
    }

    public function details(Request $request, DigitalCard $card)
    {
        $card->loadMissing(['event', 'contributions', 'recipients']);

        $contributions = $card->contributions->sortByDesc('created_at')->values();
        $confirmedTotal = $card->contributions->where('status', 'confirmed')->sum('amount');
        $recipients = $card->recipients->sortByDesc('created_at')->values();

        if ((string) $request->query('drawer') === '1') {
            return view('digital-cards.details-drawer', compact('card', 'contributions', 'confirmedTotal', 'recipients'));
        }

        return view('digital-cards.details', compact('card', 'contributions', 'confirmedTotal', 'recipients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'background_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'target_amount' => 'nullable|numeric|min:0',
            'contributor_note' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'sms_text' => 'nullable|string',
            'image_path' => 'nullable|image|mimes:jpeg,png,webp,gif|max:5120',
        ]);

        $data['card_no'] = DigitalCard::nextCardNo();
        $data['hash'] = Str::random(32);
        $data['card_type'] = 'camp_invitation';
        $data['event_id'] = null;
        $data['status'] = 'draft';
        $data['is_published'] = false;
        $data['contributions_count'] = 0;
        $data['total_contributions'] = 0;
        $data['created_by'] = auth()->user()?->name;

        $data['background_color'] = $data['background_color'] ?? '#1a237e';
        $data['accent_color'] = $data['accent_color'] ?? '#ffd700';
        $data['cta_text'] = $data['cta_text'] ?? 'Contribute Now';

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('digital-cards', 'public');
            unset($data['remove_image']);
        }

        $card = DigitalCard::create($data);
        AuditLog::record('Created digital card', 'Digital Cards', "{$card->card_no} — {$card->title}");

        return redirect()->route('cards.details', $card)
            ->with('success', "Digital card {$card->card_no} created. Invite people to it via SMS.");
    }

    public function update(Request $request, DigitalCard $card)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'background_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'target_amount' => 'nullable|numeric|min:0',
            'contributor_note' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'sms_text' => 'nullable|string',
            'image_path' => 'nullable|image|mimes:jpeg,png,webp,gif|max:5120',
            'remove_image' => 'nullable',
        ]);

        $data['title'] = (string) ($data['title'] ?? '');
        $data['message'] = (string) ($data['message'] ?? '');
        $data['card_type'] = 'camp_invitation';
        $data['event_id'] = null;

        if ($request->hasFile('image_path')) {
            if ($card->image_path) {
                Storage::disk('public')->delete($card->image_path);
            }
            $data['image_path'] = $request->file('image_path')->store('digital-cards', 'public');
        } elseif ($request->input('remove_image') && $card->image_path) {
            Storage::disk('public')->delete($card->image_path);
            $data['image_path'] = null;
        }

        unset($data['remove_image']);

        $card->update($data);
        AuditLog::record('Updated digital card', 'Digital Cards', "{$card->card_no} — {$card->title}");

        return back()->with('success', "Digital card {$card->card_no} updated.");
    }

    public function destroy(DigitalCard $card)
    {
        AuditLog::record('Deleted digital card', 'Digital Cards', "{$card->card_no} — {$card->title}");
        $no = $card->card_no;
        $card->delete();

        return back()->with('success', "Digital card {$no} deleted.");
    }

    public function updateStatus(Request $request, DigitalCard $card)
    {
        $data = $request->validate([
            'status' => 'required|in:draft,active,closed',
        ]);

        $card->update([
            'status' => $data['status'],
            'is_published' => $data['status'] === 'active',
        ]);

        AuditLog::record('Changed digital card status', 'Digital Cards', "{$card->card_no} — {$data['status']}");

        return back()->with('success', "Card {$card->card_no} is now {$card->getStatusLabel()}.");
    }

    public function sendSms(Request $request, DigitalCard $card)
    {
        $data = $request->validate([
            'invitees' => 'nullable|string',
            'phones' => 'nullable|string',
        ]);

        $invitees = $this->normalizeInvitees($data);

        if (empty($invitees)) {
            return back()->with('error', 'No valid names and phone numbers provided. Fill in Full Name and Phone for each person.');
        }

        $sms = app(SmsService::class);
        if (! $sms->isConfigured()) {
            return back()->with('error', 'SMS API token not configured.');
        }

        $template = $card->sms_text ?: 'You are invited! View your digital card and contribute: {link}';
        $success = 0;
        $fail = 0;
        $messageIds = [];
        $createdRecipients = [];

        foreach ($invitees as $invitee) {
            $token = Str::random(32);
            $link = route('cards.show', $card->hash).'?r='.$token;

            $msg = str_replace(
                ['{link}', '{name}'],
                [$link, $invitee['name'] ?? ''],
                $template
            );

            if (($invitee['name'] ?? '') !== '' && ! str_contains($template, '{name}')) {
                $msg = 'Shukurani '.$invitee['name'].', '.$msg;
            }

            $result = $sms->send($invitee['phone'], $msg);

            if ($result['success']) {
                $success++;
            } else {
                $fail++;
            }

            $recipient = DigitalCardRecipient::create([
                'digital_card_id' => $card->id,
                'name' => ($invitee['name'] ?? '') !== '' ? $invitee['name'] : null,
                'phone' => $invitee['phone'],
                'token' => $token,
                'sent_at' => $result['success'] ? now() : null,
                'status' => $result['success'] ? 'invited' : 'failed',
                'message_id' => $result['api_message_id'] ?? null,
            ]);
            $createdRecipients[] = $recipient;

            if (! empty($result['api_message_id'])) {
                $messageIds[] = $result['api_message_id'];
            }
        }

        $recipientsList = implode(', ', array_slice(array_column($invitees, 'phone'), 0, 5));
        if (count($invitees) > 5) {
            $recipientsList .= '... (+'.(count($invitees) - 5).' more)';
        }

        Message::create([
            'channel' => 'sms',
            'recipients' => $recipientsList,
            'phone' => $invitees[0]['phone'] ?? '',
            'subject' => "Digital card SMS — {$card->card_no}",
            'message' => $template,
            'status' => $success > 0 ? 'sent' : 'failed',
            'api_message_id' => $messageIds[0] ?? null,
            'api_response' => [
                'success_count' => $success,
                'fail_count' => $fail,
                'invitees' => array_map(fn ($i) => ['name' => $i['name'] ?? null, 'phone' => $i['phone']], $invitees),
                'message_ids' => $messageIds,
            ],
            'created_by' => auth()->user()?->name,
        ]);

        AuditLog::record(
            'Invited digital card recipients',
            'Digital Cards',
            "{$card->card_no} — {$success} invited, {$fail} failed"
        );

        $notice = "{$success} person(s) invited by SMS.";
        if ($fail > 0) {
            $notice .= " {$fail} failed.";
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => $success > 0,
                'success_count' => $success,
                'fail_count' => $fail,
                'message' => $notice,
                'recipients' => collect($createdRecipients)->map(fn (DigitalCardRecipient $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'phone' => $r->phone,
                    'status' => $r->status,
                    'status_label' => ucfirst((string) $r->status),
                    'status_color' => $r->getInviteStatusColor(),
                    'message_id' => $r->message_id,
                    'delivery_label' => $r->delivery_status ? ucfirst(str_replace('_', ' ', $r->delivery_status)) : ($r->message_id ? 'Unchecked' : '—'),
                    'delivery_color' => $r->getDeliveryStatusColor(),
                    'checked_at' => $r->delivery_checked_at?->format('d M Y H:i'),
                    'sent_at' => $r->sent_at?->format('d M Y H:i') ?: 'Not sent',
                    'link' => route('cards.show', $r->digitalCard?->hash).'?r='.$r->token,
                    'token' => $r->token,
                ])->values()->all(),
            ]);
        }

        return back()->with($success > 0 ? 'success' : 'error', $notice);
    }

    private function normalizeInvitees(array $data): array
    {
        if (! empty($data['invitees'])) {
            $decoded = json_decode($data['invitees'], true);

            if (is_array($decoded)) {
                return collect($decoded)
                    ->map(fn ($r) => [
                        'name' => (string) ($r['name'] ?? ''),
                        'phone' => preg_replace('/[^+\d]/', '', (string) ($r['phone'] ?? '')),
                    ])
                    ->filter(fn ($r) => $r['phone'] !== '')
                    ->values()
                    ->all();
            }
        }

        return array_map(fn ($r) => [
            'name' => $r['name'] ?? '',
            'phone' => $r['phone'],
        ], $this->parseRecipients((string) ($data['phones'] ?? '')));
    }

    public function checkRecipientDelivery(Request $request, DigitalCardRecipient $recipient)
    {
        if (! $recipient->message_id) {
            return response()->json([
                'ok' => false,
                'status' => 'unknown',
                'label' => 'No Message ID',
                'color' => 'neutral',
                'checked_at' => $recipient->delivery_checked_at?->format('d M Y H:i'),
            ]);
        }

        $report = app(SmsService::class)->getDelivery($recipient->message_id);

        $deliveryStatus = (string) ($report['status'] ?? 'unknown');
        $recipient->update([
            'delivery_status' => $deliveryStatus,
            'delivery_checked_at' => now(),
        ]);

        return response()->json([
            'ok' => (bool) ($report['success'] ?? false),
            'status' => $deliveryStatus,
            'label' => $this->deliveryLabel($deliveryStatus),
            'color' => $this->deliveryColor($deliveryStatus),
            'checked_at' => now()->format('d M Y H:i'),
            'raw' => $report['raw'] ?? null,
        ]);
    }

    private function deliveryLabel(string $status): string
    {
        return match ($status) {
            'delivered' => 'Delivered',
            'undelivered' => 'Not delivered',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    private function deliveryColor(string $status): string
    {
        return match ($status) {
            'delivered' => 'success',
            'undelivered' => 'danger',
            'pending' => 'warning',
            default => 'neutral',
        };
    }

    public function resendSms(Request $request, DigitalCardRecipient $recipient)
    {
        $card = $recipient->digitalCard;

        $sms = app(SmsService::class);
        if (! $sms->isConfigured()) {
            return back()->with('error', 'SMS API token not configured.');
        }

        $template = $card->sms_text ?: 'You are invited! View your digital card and contribute: {link}';
        $link = route('cards.show', $card->hash).'?r='.$recipient->token;

        $msg = str_replace(['{link}', '{name}'], [$link, $recipient->name ?? ''], $template);
        if (($recipient->name ?? '') !== '' && ! str_contains($template, '{name}')) {
            $msg = 'Shukurani '.$recipient->name.', '.$msg;
        }

        $result = $sms->send($recipient->phone, $msg);

        $recipient->update([
            'sent_at' => $result['success'] ? now() : $recipient->sent_at,
            'status' => $result['success'] ? 'invited' : 'failed',
            'message_id' => $result['api_message_id'] ?? null,
            'delivery_status' => null,
            'delivery_checked_at' => null,
        ]);

        Message::create([
            'channel' => 'sms',
            'recipients' => $recipient->name ?? $recipient->phone,
            'phone' => $recipient->phone,
            'subject' => "Digital card SMS — {$card->card_no}",
            'message' => $template,
            'status' => $result['success'] ? 'sent' : 'failed',
            'api_message_id' => $result['api_message_id'] ?? null,
            'api_response' => $result['raw'],
            'created_by' => auth()->user()?->name,
        ]);

        AuditLog::record(
            'Re-sent digital card SMS invite',
            'Digital Cards',
            "{$card->card_no} — {$recipient->name} ({$recipient->phone})"
        );

        $notice = $result['success']
            ? "Invitation SMS re-sent to {$recipient->name}."
            : 'SMS sending failed ('.$result['status'].').';

        return back()->with($result['success'] ? 'success' : 'error', $notice);
    }

    public function destroyRecipient(Request $request, DigitalCardRecipient $recipient)
    {
        AuditLog::record(
            'Removed digital card invite',
            'Digital Cards',
            "{$recipient->digitalCard?->card_no} — {$recipient->name} ({$recipient->phone})"
        );

        $recipient->delete();

        return redirect()->route('cards.index')->with('success', 'Invite removed.');
    }

    public function exportCsv(Request $request)
    {
        $card = $this->currentCard();

        $query = $this->applyInviteFilters(
            $card->recipients(),
            $request->query('status'),
            $request->query('delivery'),
            trim((string) $request->query('q'))
        );

        $rows = $query->latest('created_at')->get();

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Name', 'Phone', 'Invite Status', 'Delivery Status', 'Sent At', 'Delivery Checked At', 'Personalised Link']);

        foreach ($rows as $recipient) {
            fputcsv($csv, [
                $recipient->name ?? '',
                $recipient->phone,
                $recipient->status ?: 'pending',
                $recipient->delivery_status ?: '',
                $recipient->sent_at?->format('d M Y H:i') ?: '',
                $recipient->delivery_checked_at?->format('d M Y H:i') ?: '',
                route('cards.show', $recipient->digitalCard?->hash).'?r='.$recipient->token,
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=invitations-'.now()->format('Y-m-d-His').'.csv',
        ]);
    }

    private function parseRecipients(string $raw): array
    {
        $recipients = [];

        foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode(',', $line, 2));
            $isPhone = fn ($str) => (bool) preg_match('/^[+\d][\d\s\-()]*$/', $str);

            if (isset($parts[1]) && $parts[1] !== '' && ! $isPhone($parts[0])) {
                $name = $parts[0] !== '' ? $parts[0] : null;
                $phone = $parts[1];
            } else {
                $name = null;
                $phone = $parts[0];
            }

            $phone = preg_replace('/[^+\d]/', '', $phone) ?? '';

            if ($phone === '') {
                continue;
            }

            $recipients[] = ['name' => $name, 'phone' => $phone];
        }

        return $recipients;
    }

    public function downloadPdf(DigitalCard $card)
    {
        $this->streamPdf($card);
    }

    public function publicPdf(string $hash)
    {
        $card = DigitalCard::where('hash', $hash)
            ->whereIn('status', ['active', 'closed'])
            ->firstOrFail();

        $this->streamPdf($card);
    }

    private function streamPdf(DigitalCard $card, string $dest = 'D'): void
    {
        $qrData = app(QrCodeService::class)->pngDataUri(
            "OGCM|CARD|{$card->card_no}|{$card->hash}"
        );

        $html = view('digital-cards.pdf', compact('card', 'qrData'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [91.44, 114.3],
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'dpi' => 300,
            'tempDir' => storage_path('app/private/mpdf'),
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output("DigitalCard-{$card->card_no}.pdf", $dest);
    }

    public function preview(DigitalCard $card)
    {
        $qrData = app(QrCodeService::class)->pngDataUri(
            "OGCM|CARD|{$card->card_no}|{$card->hash}"
        );

        return view('digital-cards.preview', compact('card', 'qrData'));
    }

    public function show(Request $request, string $hash)
    {
        $card = DigitalCard::where('hash', $hash)
            ->whereIn('status', ['active', 'closed'])
            ->firstOrFail();

        $this->streamPdf($card, 'I');
    }

    public function contribute(Request $request, string $hash)
    {
        $card = DigitalCard::where('hash', $hash)
            ->where('status', 'active')
            ->firstOrFail();

        $mode = $request->input('mode', 'contribute');

        $baseRules = [
            'mode' => 'nullable|in:contribute,pledge',
            'contributor_name' => 'nullable|string|max:255',
            'contributor_phone' => 'nullable|string|max:20',
            'contributor_email' => 'nullable|email|max:255',
            'amount' => 'required|numeric|min:100',
            'note' => 'nullable|string|max:500',
        ];

        if ($mode === 'pledge') {
            $data = $request->validate(array_merge($baseRules, [
                'contributor_name' => 'required|string|max:255',
                'due_date' => 'nullable|date|after_or_equal:today',
            ]));

            $pledge = DB::transaction(function () use ($data, $card) {
                return Pledge::create([
                    'event_id' => $card->event_id,
                    'pledge_no' => Pledge::nextPledgeNo(),
                    'name' => $data['contributor_name'],
                    'email' => $data['contributor_email'] ?? null,
                    'phone' => $data['contributor_phone'] ?? null,
                    'amount' => (float) $data['amount'],
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'frequency' => 'one_time',
                    'notes' => "Digital card pledge — {$card->card_no} ({$card->title})"
                        .($data['note'] ? "\n".$data['note'] : ''),
                    'pledge_date' => now()->toDateString(),
                    'due_date' => $data['due_date'] ?? null,
                    'created_by' => $card->card_no,
                ]);
            });

            AuditLog::record(
                'Digital card pledge received',
                'Digital Cards',
                "{$card->card_no} — {$pledge->pledge_no} — TZS ".number_format($pledge->amount)." from {$pledge->name}"
            );

            return redirect()->route('cards.show', $card->hash)
                ->with('pledge_success', true)
                ->with('pledge_amount', number_format($pledge->amount));
        }

        $data = $request->validate(array_merge($baseRules, [
            'method' => 'required|in:cash,bank,mobile',
            'reference_no' => 'nullable|string|max:100',
        ]));

        DB::transaction(function () use ($data, $card) {
            $posting = app(AccountingPostingService::class);
            $entry = $posting->postMoneyIn([
                'date' => now()->format('Y-m-d'),
                'description' => "Digital card contribution — {$card->card_no}: {$card->title}",
                'reference' => $data['reference_no'] ?? null,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'incomeAccount' => $posting->incomeAccount('acct.pledge_income', '4020'),
            ]);

            DigitalCardContribution::create([
                ...$data,
                'digital_card_id' => $card->id,
                'journal_entry_id' => $entry->id,
                'status' => 'confirmed',
            ]);

            $card->increment('contributions_count');
            $card->increment('total_contributions', $data['amount']);
        });

        AuditLog::record(
            'Digital card contribution received',
            'Digital Cards',
            "{$card->card_no} — TZS ".number_format($data['amount']).($data['contributor_name'] ? " from {$data['contributor_name']}" : '')
        );

        return redirect()->route('cards.show', $card->hash)
            ->with('contribution_success', true)
            ->with('contribution_amount', number_format($data['amount']));
    }
}
