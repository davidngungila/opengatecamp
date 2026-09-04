<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DigitalCard;
use App\Models\DigitalCardContribution;
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

        $raw = $data['phones'];
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $phones = array_values(array_filter(array_map('trim', $decoded)));
        } else {
            $phones = preg_split('/[\s,;]+/', $raw) ?: [];
            $phones = array_values(array_filter(array_map('trim', $phones)));
        }

        if (empty($phones)) {
            return back()->with('error', 'No valid phone numbers provided.');
        }

        $sms = new SmsService;
        if (! $sms->isConfigured()) {
            return back()->with('error', 'SMS API token not configured.');
        }

        $link = $card->public_url;
        $msg = $card->sms_text ?: "You are invited! View your digital card and contribute: {$link}";

        $result = $sms->sendBulk($phones, $msg);

        $recipients = implode(', ', array_slice($phones, 0, 5));
        if (count($phones) > 5) {
            $recipients .= '... (+'.(count($phones) - 5).' more)';
        }

        Message::create([
            'channel' => 'sms',
            'recipients' => $recipients,
            'phone' => $phones[0] ?? '',
            'subject' => "Digital card SMS — {$card->card_no}",
            'message' => $msg,
            'status' => $result['success_count'] > 0 ? 'sent' : 'failed',
            'api_message_id' => null,
            'api_response' => $result,
            'created_by' => auth()->user()?->name,
        ]);

        AuditLog::record(
            'Sent digital card SMS',
            'Digital Cards',
            "{$card->card_no} — {$result['success_count']} sent, {$result['fail_count']} failed"
        );

        $notice = "{$result['success_count']} SMS sent successfully.";
        if ($result['fail_count'] > 0) {
            $notice .= " {$result['fail_count']} failed.";
        }

        return back()->with($result['success_count'] > 0 ? 'success' : 'error', $notice);
    }

    public function downloadPdf(DigitalCard $card)
    {
        $qrData = app(QrCodeService::class)->pngDataUri(
            "OGCM|CARD|{$card->card_no}|{$card->hash}"
        );

        $html = view('digital-cards.pdf', compact('card', 'qrData'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'tempDir' => storage_path('app/private/mpdf'),
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output("DigitalCard-{$card->card_no}.pdf", 'D');
    }

    public function preview(DigitalCard $card)
    {
        return view('digital-cards.public', ['card' => $card, 'preview' => true]);
    }

    public function show(string $hash)
    {
        $card = DigitalCard::where('hash', $hash)
            ->whereIn('status', ['active', 'closed'])
            ->withCount(['contributions' => fn ($q) => $q->where('status', 'confirmed')])
            ->firstOrFail();

        return view('digital-cards.public', compact('card'));
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
