<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DigitalCard;
use App\Models\DigitalCardContribution;
use App\Models\DigitalCardRecipient;
use App\Models\Event;
use App\Models\Message;
use App\Models\Pledge;
use App\Services\AccountingPostingService;
use App\Services\QrCodeService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mpdf\Mpdf;

class DigitalCardController extends Controller
{
    public function index(Request $request)
    {
        $cardType = $request->query('type');
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $query = DigitalCard::with(['event', 'contributions']);

        $query->when($cardType, fn ($qr) => $qr->where('card_type', $cardType))
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('card_no', 'like', "%{$q}%")
                ->orWhere('message', 'like', "%{$q}%")));

        $cards = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $totals = [
            'total_cards' => DigitalCard::count(),
            'active_cards' => DigitalCard::where('status', 'active')->count(),
            'total_amount' => DigitalCard::sum('total_contributions'),
            'total_received' => DigitalCard::sum('contributions_count'),
        ];

        $events = Event::orderByDesc('start_date')->get();
        $types = DigitalCard::types();
        $statuses = DigitalCard::statuses();

        return view('digital-cards.index', compact(
            'cards', 'events', 'types', 'statuses', 'totals',
            'cardType', 'status', 'q'
        ));
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
            'card_type' => 'required|in:camp_invitation,fundraising,thank_you,birthday,christmas,general',
            'background_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'event_id' => 'nullable|exists:events,id',
            'target_amount' => 'nullable|numeric|min:0',
            'contributor_note' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'sms_text' => 'nullable|string',
        ]);

        $data['card_no'] = DigitalCard::nextCardNo();
        $data['hash'] = Str::random(32);
        $data['status'] = 'draft';
        $data['is_published'] = false;
        $data['contributions_count'] = 0;
        $data['total_contributions'] = 0;
        $data['created_by'] = auth()->user()?->name;

        $data['background_color'] = $data['background_color'] ?? '#1a237e';
        $data['accent_color'] = $data['accent_color'] ?? '#ffd700';
        $data['cta_text'] = $data['cta_text'] ?? 'Contribute Now';

        $card = DigitalCard::create($data);
        AuditLog::record('Created digital card', 'Digital Cards', "{$card->card_no} — {$card->title}");

        return back()->with('success', "Digital card {$card->card_no} created.");
    }

    public function update(Request $request, DigitalCard $card)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'card_type' => 'required|in:camp_invitation,fundraising,thank_you,birthday,christmas,general',
            'background_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'event_id' => 'nullable|exists:events,id',
            'target_amount' => 'nullable|numeric|min:0',
            'contributor_note' => 'nullable|string|max:500',
            'cta_text' => 'nullable|string|max:100',
            'sms_text' => 'nullable|string',
        ]);

        $data['title'] = (string) ($data['title'] ?? '');
        $data['message'] = (string) ($data['message'] ?? '');

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
            'phones' => 'required|string',
        ]);

        $recipients = $this->parseRecipients($data['phones']);

        if (empty($recipients)) {
            return back()->with('error', 'No valid phone numbers provided.');
        }

        $sms = app(SmsService::class);
        if (! $sms->isConfigured()) {
            return back()->with('error', 'SMS API token not configured.');
        }

        $template = $card->sms_text ?: 'You are invited! View your digital card and contribute: {link}';
        $success = 0;
        $fail = 0;

        foreach ($recipients as $recipient) {
            $token = Str::random(32);
            $link = route('cards.show', $card->hash).'?r='.$token;

            $msg = str_replace(
                ['{link}', '{name}'],
                [$link, $recipient['name'] ?? ''],
                $template
            );

            if (($recipient['name'] ?? '') !== '' && ! str_contains($template, '{name}')) {
                $msg = 'Shukurani '.$recipient['name'].', '.$msg;
            }

            $result = $sms->send($recipient['phone'], $msg);

            if ($result['success']) {
                $success++;
            } else {
                $fail++;
            }

            DigitalCardRecipient::create([
                'digital_card_id' => $card->id,
                'name' => $recipient['name'] ?? null,
                'phone' => $recipient['phone'],
                'token' => $token,
                'sent_at' => $result['success'] ? now() : null,
            ]);
        }

        $recipientsList = implode(', ', array_slice(array_column($recipients, 'phone'), 0, 5));
        if (count($recipients) > 5) {
            $recipientsList .= '... (+'.(count($recipients) - 5).' more)';
        }

        Message::create([
            'channel' => 'sms',
            'recipients' => $recipientsList,
            'phone' => $recipients[0]['phone'] ?? '',
            'subject' => "Digital card SMS — {$card->card_no}",
            'message' => $template,
            'status' => $success > 0 ? 'sent' : 'failed',
            'api_message_id' => null,
            'api_response' => ['success_count' => $success, 'fail_count' => $fail],
            'created_by' => auth()->user()?->name,
        ]);

        AuditLog::record(
            'Sent digital card SMS',
            'Digital Cards',
            "{$card->card_no} — {$success} sent, {$fail} failed"
        );

        $notice = "{$success} SMS sent successfully.";
        if ($fail > 0) {
            $notice .= " {$fail} failed.";
        }

        return back()->with($success > 0 ? 'success' : 'error', $notice);
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

    private function streamPdf(DigitalCard $card): void
    {
        $qrData = app(QrCodeService::class)->pngDataUri(
            "OGCM|CARD|{$card->card_no}|{$card->hash}"
        );

        $html = view('digital-cards.pdf', compact('card', 'qrData'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 350],
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 3,
            'margin_bottom' => 3,
            'tempDir' => storage_path('app/private/mpdf'),
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output("DigitalCard-{$card->card_no}.pdf", 'D');
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
            ->withCount(['contributions' => fn ($q) => $q->where('status', 'confirmed')])
            ->firstOrFail();

        $recipient = $this->recipientFromRequest($request, $card->id);

        return view('digital-cards.public', compact('card', 'recipient'));
    }

    private function recipientFromRequest(Request $request, int $cardId): ?DigitalCardRecipient
    {
        $token = trim((string) $request->query('r'));

        if ($token === '') {
            return null;
        }

        return DigitalCardRecipient::where('token', $token)
            ->where('digital_card_id', $cardId)
            ->first();
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
