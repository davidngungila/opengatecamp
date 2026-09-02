<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Group;
use App\Models\Member;
use App\Models\Message;
use App\Models\Ministry;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Http\Request;

class MessagingController extends Controller
{
    private function sharedData(): array
    {
        return [
            'messages'      => Message::latest()->take(20)->get(),
            'messageCount'  => Message::count(),
            'smsConfigured' => (new SmsService())->isConfigured(),
            'smsToken'      => Setting::get('sms.api_token', ''),
            'smsSenderId'   => Setting::get('sms.sender_id', 'TMCS MoCU'),
            'groups'        => Group::orderBy('name')->get(),
            'ministries'    => Ministry::orderBy('name')->get(),
        ];
    }

    public function sms()
    {
        return view('messaging.sms', $this->sharedData());
    }

    public function email()
    {
        return view('messaging.email', $this->sharedData());
    }

    public function notifications()
    {
        $notifications = AuditLog::latest()->take(30)->get();

        return view('messaging.notifications', $this->sharedData() + [
            'notifications' => $notifications,
        ]);
    }

    public function templates()
    {
        return view('messaging.templates', $this->sharedData());
    }

    public function history(Request $request)
    {
        $channel = $request->query('channel', 'all');
        $status  = $request->query('status', 'all');
        $q       = $request->query('q', '');

        $messages = Message::query()
            ->when($channel !== 'all', fn ($query) => $query->where('channel', $channel))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('recipients', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('message', 'like', '%'.$q.'%');
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('messaging.history', $this->sharedData() + [
            'messages' => $messages,
            'channel'  => $channel,
            'status'   => $status,
            'q'        => $q,
        ]);
    }

    public function settings()
    {
        return view('messaging.settings', $this->sharedData());
    }

    /**
     * AJAX: Get phone numbers for a given recipient filter.
     */
    public function getRecipients(Request $request)
    {
        $filter = $request->input('filter', 'all_active');
        $value  = $request->input('value');

        $query = Member::whereNotNull('phone')->where('phone', '!=', '');

        switch ($filter) {
            case 'all_active':
                $query->where('status', 'Active');
                break;
            case 'all':
                break;
            case 'status':
                if ($value) { $query->where('status', $value); }
                break;
            case 'member_type':
                if ($value) { $query->where('member_type', $value); }
                break;
            case 'staff_type':
                if ($value) { $query->where('staff_type', $value); }
                break;
            case 'group':
                if ($value) { $query->where('group_id', $value); }
                break;
            case 'ministry':
                if ($value) { $query->where('ministry_id', $value); }
                break;
            case 'students_activated':
                $fy = \App\Models\FinancialYear::current();
                if ($fy) {
                    $query->where('member_type', 'student')
                        ->whereHas('activations', fn ($q) => $q->where('financial_year_id', $fy->id));
                } else {
                    $query->whereRaw('0=1');
                }
                break;
            case 'students_not_activated':
                $fy = \App\Models\FinancialYear::current();
                if ($fy) {
                    $query->where('member_type', 'student')
                        ->whereDoesntHave('activations', fn ($q) => $q->where('financial_year_id', $fy->id));
                } else {
                    $query->where('member_type', 'student');
                }
                break;
            case 'inactive':
                $query->where('status', 'Inactive');
                break;
            case 'new':
                $query->where('status', 'New');
                break;
        }

        $members = $query->select('id', 'name', 'phone', 'member_type', 'status', 'group_id', 'ministry_id')
            ->with(['group:name', 'ministry:name'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'count'   => $members->count(),
            'members' => $members->map(fn ($m) => [
                'name'     => $m->name,
                'phone'    => $m->phone,
                'type'     => $m->member_type,
                'status'   => $m->status,
                'group'    => $m->group?->name ?? '—',
                'ministry' => $m->ministry?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel'    => 'required|in:sms,email',
            'recipients' => 'required|string|max:255',
            'recipient_filter' => 'nullable|string',
            'recipient_value'  => 'nullable|string',
            'phone'      => 'nullable|string|max:20',
            'phones_json' => 'nullable|string',
            'subject'    => 'nullable|string|max:255|required_if:channel,email',
            'message'    => 'required|string|max:2000',
        ]);

        $isSend = $request->input('action') === 'send';
        $status = $isSend ? 'sending' : 'draft';
        $apiMessageId = null;
        $apiResponse  = null;
        $sentCount = 0;
        $failCount = 0;
        $phoneList = '';

        if ($data['channel'] === 'sms' && $isSend) {
            $sms = new SmsService();

            if (! $sms->isConfigured()) {
                return back()->withInput()->with('error',
                    'SMS API token is not configured. Go to Messaging â†’ Settings to add your API token.');
            }

            $phones = [];
            if (! empty($data['phones_json'])) {
                $decoded = json_decode($data['phones_json'], true);
                if (is_array($decoded)) {
                    $phones = array_filter(array_map('trim', $decoded));
                }
            } elseif (! empty($data['phone'])) {
                $phones = [$data['phone']];
            }

            if (empty($phones)) {
                return back()->withInput()->with('error', 'No valid phone numbers found. Select recipients first.');
            }

            $phoneList = implode(', ', array_slice($phones, 0, 10)).(count($phones) > 10 ? '... (+'.(count($phones)-10).')' : '');

            if (count($phones) === 1) {
                $result = $sms->send($phones[0], $data['message']);
                $apiMessageId = $result['api_message_id'];
                $apiResponse  = $result['raw'];
                $result['success'] ? $sentCount++ : $failCount++;
            } else {
                $bulkResult = $sms->sendBulk($phones, $data['message']);
                $sentCount  = $bulkResult['success_count'];
                $failCount  = $bulkResult['fail_count'];
                $apiResponse = $bulkResult['results'];
            }

            $status = $sentCount > 0 ? 'sent' : 'failed';
        } elseif ($isSend) {
            $status = 'sent';
        }

        $data['phone'] = $phoneList ?: ($data['phone'] ?? null);
        $data['status']         = $status;
        $data['api_message_id'] = $apiMessageId;
        $data['api_response']   = $apiResponse;
        $data['created_by']     = auth()->user()?->name ?? 'Daniel Mwinuka';

        Message::create($data);

        AuditLog::record(
            $status === 'sent' ? 'Sent message' : ($status === 'failed' ? 'Failed to send message' : 'Saved message draft'),
            'Communication — Messaging',
            ucfirst($data['channel']).' to '.$data['recipients'].($phoneList ? ' ('.$phoneList.')' : '')
        );

        if ($status === 'failed' && $failCount > 0) {
            return redirect()->route('messaging.'.$data['channel'])
                ->with('error', "SMS failed for all {$failCount} recipients.");
        }

        $countText = ($sentCount + $failCount) > 1
            ? "Bulk SMS: {$sentCount} sent, {$failCount} failed out of ".($sentCount + $failCount).' recipients.'
            : 'SMS sent successfully to '.$phoneList.'.';

        return redirect()->route('messaging.'.$data['channel'])
            ->with('success', $status === 'sent' ? $countText : 'Draft saved successfully.');
    }

    public function saveToken(Request $request)
    {
        $data = $request->validate([
            'api_token' => 'required|string|max:255',
            'sender_id' => 'nullable|string|max:30',
        ]);

        Setting::put('sms.api_token', $data['api_token']);
        Setting::put('sms.sender_id', $data['sender_id'] ?: 'TMCS MoCU');

        AuditLog::record('Updated SMS API settings', 'Communication — Settings', 'Sender: '.($data['sender_id'] ?: 'TMCS MoCU'));

        return back()->with('success', 'SMS API settings saved successfully. You can now send SMS messages.');
    }

    public function useTemplate(Request $request)
    {
        $template = $request->input('template');
        $name = $request->input('name', '');

        return redirect()->route('messaging.sms')->with('template', $template)->with('templateName', $name);
    }
}
