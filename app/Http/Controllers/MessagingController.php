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

        $data = array_replace($this->sharedData(), [
            'messages' => $messages,
            'channel'  => $channel,
            'status'   => $status,
            'q'        => $q,
        ]);

        return view('messaging.history', $data);
    }

    public function show(int $id)
    {
        $message = Message::findOrFail($id);

        return view('messaging.show', array_replace($this->sharedData(), [
            'message' => $message,
        ]));
    }

    public function settings()
    {
        return view('messaging.settings', $this->sharedData() + [
            'providers'  => $this->smsProviders(),
            'primaryKey' => $this->smsPrimaryKey(),
        ]);
    }

    public function emailSettings()
    {
        return view('messaging.settings-email', $this->sharedData() + [
            'providers'  => $this->emailProviders(),
            'primaryKey' => $this->emailPrimaryKey(),
        ]);
    }

    public function saveEmailSettings(Request $request)
    {
        $data = $request->validate([
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|between:1,65535',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_encryption'   => 'nullable|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name'    => 'nullable|string|max:255',
        ]);

        foreach ([
            'mail_host'         => 'mail.host',
            'mail_port'         => 'mail.port',
            'mail_username'     => 'mail.username',
            'mail_password'     => 'mail.password',
            'mail_encryption'   => 'mail.encryption',
            'mail_from_address' => 'mail.from_address',
            'mail_from_name'    => 'mail.from_name',
        ] as $field => $key) {
            Setting::put($key, $data[$field] ?? null);
        }

        AuditLog::record('Updated Email (SMTP) settings', 'Communication — Settings', 'Host: '.($data['mail_host'] ?: 'not set'));

        return back()->with('success', 'Email (SMTP) settings saved successfully.');
    }

    private function smsProviders(): array
    {
        $providers = json_decode((string) Setting::get('sms.providers', '[]'), true) ?: [];

        if (empty($providers)) {
            $token = Setting::get('sms.api_token', '');
            if ($token !== '') {
                $providers = [[
                    'key'       => 'default',
                    'name'      => 'Default Provider',
                    'api_token' => $token,
                    'sender_id' => Setting::get('sms.sender_id', 'TMCS MoCU'),
                ]];
                Setting::put('sms.providers', json_encode($providers));
                Setting::put('sms.primary', 'default');
                $this->syncSmsPrimary();
            }
        }

        $primary = $this->smsPrimaryKey();

        return array_values(array_map(function ($p) use ($primary) {
            $p['is_primary'] = ($p['key'] ?? null) === $primary;

            return $p;
        }, $providers));
    }

    private function smsPrimaryKey(): ?string
    {
        $key = Setting::get('sms.primary', '');

        return $key !== '' ? $key : null;
    }

    private function emailProviders(): array
    {
        $providers = json_decode((string) Setting::get('mail.providers', '[]'), true) ?: [];

        if (empty($providers)) {
            $host = Setting::get('mail.host', '');
            if ($host !== '') {
                $providers = [[
                    'key'          => 'default',
                    'name'         => 'Default Provider',
                    'host'         => $host,
                    'port'         => Setting::get('mail.port', ''),
                    'username'     => Setting::get('mail.username', ''),
                    'password'     => Setting::get('mail.password', ''),
                    'encryption'   => Setting::get('mail.encryption', 'tls'),
                    'from_address' => Setting::get('mail.from_address', ''),
                    'from_name'    => Setting::get('mail.from_name', ''),
                ]];
                Setting::put('mail.providers', json_encode($providers));
                Setting::put('mail.primary', 'default');
                $this->syncEmailPrimary();
            }
        }

        $primary = $this->emailPrimaryKey();

        return array_values(array_map(function ($p) use ($primary) {
            $p['is_primary'] = ($p['key'] ?? null) === $primary;

            return $p;
        }, $providers));
    }

    private function emailPrimaryKey(): ?string
    {
        $key = Setting::get('mail.primary', '');

        return $key !== '' ? $key : null;
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

    private function readSmsProviders(): array
    {
        return json_decode((string) Setting::get('sms.providers', '[]'), true) ?: [];
    }

    private function writeSmsProviders(array $providers): void
    {
        Setting::put('sms.providers', json_encode(array_values($providers)));
        $this->syncSmsPrimary();
    }

    private function syncSmsPrimary(): void
    {
        $providers = $this->readSmsProviders();
        $primary = $this->smsPrimaryKey() ?? ($providers[0]['key'] ?? null);

        if ($primary === null) {
            Setting::put('sms.primary', '');
            Setting::put('sms.api_token', '');
            Setting::put('sms.sender_id', 'TMCS MoCU');

            return;
        }

        $match = collect($providers)->firstWhere('key', $primary);
        if (! $match) {
            $match = $providers[0] ?? null;
            $primary = $match['key'] ?? null;
        }

        Setting::put('sms.primary', $primary);
        Setting::put('sms.api_token', $match['api_token'] ?? '');
        Setting::put('sms.sender_id', $match['sender_id'] ?: 'TMCS MoCU');
    }

    private function readEmailProviders(): array
    {
        return json_decode((string) Setting::get('mail.providers', '[]'), true) ?: [];
    }

    private function writeEmailProviders(array $providers): void
    {
        Setting::put('mail.providers', json_encode(array_values($providers)));
        $this->syncEmailPrimary();
    }

    private function syncEmailPrimary(): void
    {
        $providers = $this->readEmailProviders();
        $primary = $this->emailPrimaryKey() ?? ($providers[0]['key'] ?? null);

        if ($primary === null) {
            foreach (['host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name'] as $f) {
                Setting::put('mail.'.$f, '');
            }
            Setting::put('mail.primary', '');

            return;
        }

        $match = collect($providers)->firstWhere('key', $primary);
        if (! $match) {
            $match = $providers[0] ?? null;
            $primary = $match['key'] ?? null;
        }

        Setting::put('mail.primary', $primary);
        Setting::put('mail.host',         $match['host'] ?? '');
        Setting::put('mail.port',         $match['port'] ?? '');
        Setting::put('mail.username',     $match['username'] ?? '');
        Setting::put('mail.password',     $match['password'] ?? '');
        Setting::put('mail.encryption',   $match['encryption'] ?? '');
        Setting::put('mail.from_address', $match['from_address'] ?? '');
        Setting::put('mail.from_name',    $match['from_name'] ?? '');
    }

    public function smsProviderStore(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:120',
            'api_token' => 'required|string|max:255',
            'sender_id' => 'nullable|string|max:30',
            'set_primary' => 'nullable|boolean',
        ]);

        $providers = $this->readSmsProviders();
        $key = \Illuminate\Support\Str::random(6);
        $providers[] = [
            'key' => $key,
            'name' => $data['name'],
            'api_token' => $data['api_token'],
            'sender_id' => $data['sender_id'] ?: 'TMCS MoCU',
        ];

        if ($request->boolean('set_primary') || empty($this->smsPrimaryKey())) {
            Setting::put('sms.primary', $key);
        }

        $this->writeSmsProviders($providers);

        AuditLog::record('Added SMS provider', 'Communication — Settings', $data['name']);

        return back()->with('success', "SMS provider '{$data['name']}' added.");
    }

    public function smsProviderUpdate(Request $request, string $key)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:120',
            'api_token' => 'required|string|max:255',
            'sender_id' => 'nullable|string|max:30',
        ]);

        $providers = $this->readSmsProviders();
        $updated = false;

        foreach ($providers as &$p) {
            if (($p['key'] ?? null) === $key) {
                $p['name'] = $data['name'];
                $p['api_token'] = $data['api_token'];
                $p['sender_id'] = $data['sender_id'] ?: 'TMCS MoCU';
                $updated = true;
                break;
            }
        }
        unset($p);

        if (! $updated) {
            return back()->with('error', 'SMS provider not found.');
        }

        $this->writeSmsProviders($providers);

        AuditLog::record('Updated SMS provider', 'Communication — Settings', $data['name']);

        return back()->with('success', "SMS provider '{$data['name']}' updated.");
    }

    public function smsProviderDelete(string $key)
    {
        $providers = collect($this->readSmsProviders())->reject(fn ($p) => ($p['key'] ?? null) === $key)->values()->all();
        $this->writeSmsProviders($providers);

        AuditLog::record('Removed SMS provider', 'Communication — Settings', $key);

        return back()->with('success', 'SMS provider removed.');
    }

    public function smsProviderPrimary(string $key)
    {
        $providers = $this->readSmsProviders();

        if (! collect($providers)->contains('key', $key)) {
            return back()->with('error', 'SMS provider not found.');
        }

        Setting::put('sms.primary', $key);
        $this->syncSmsPrimary();

        AuditLog::record('Set primary SMS provider', 'Communication — Settings', $key);

        return back()->with('success', 'Primary SMS provider updated.');
    }

    public function emailProviderStore(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'host'          => 'required|string|max:255',
            'port'          => 'nullable|integer|between:1,65535',
            'username'      => 'nullable|string|max:255',
            'password'      => 'nullable|string|max:255',
            'encryption'    => 'nullable|in:tls,ssl,none',
            'from_address'  => 'nullable|email|max:255',
            'from_name'     => 'nullable|string|max:255',
        ]);

        $providers = $this->readEmailProviders();
        $key = \Illuminate\Support\Str::random(6);
        $providers[] = [
            'key' => $key,
            'name' => $data['name'],
            'host' => $data['host'],
            'port' => $data['port'] ?? null,
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'encryption' => $data['encryption'] ?? 'tls',
            'from_address' => $data['from_address'] ?? '',
            'from_name' => $data['from_name'] ?? '',
        ];

        if ($request->boolean('set_primary') || empty($this->emailPrimaryKey())) {
            Setting::put('mail.primary', $key);
        }

        $this->writeEmailProviders($providers);

        AuditLog::record('Added email provider', 'Communication — Settings', $data['name']);

        return back()->with('success', "Email provider '{$data['name']}' added.");
    }

    public function emailProviderUpdate(Request $request, string $key)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'host'          => 'required|string|max:255',
            'port'          => 'nullable|integer|between:1,65535',
            'username'      => 'nullable|string|max:255',
            'password'      => 'nullable|string|max:255',
            'encryption'    => 'nullable|in:tls,ssl,none',
            'from_address'  => 'nullable|email|max:255',
            'from_name'     => 'nullable|string|max:255',
        ]);

        $providers = $this->readEmailProviders();
        $updated = false;

        foreach ($providers as &$p) {
            if (($p['key'] ?? null) === $key) {
                $p['name'] = $data['name'];
                $p['host'] = $data['host'];
                $p['port'] = $data['port'] ?? null;
                $p['username'] = $data['username'] ?? '';
                $p['password'] = $data['password'] ?? '';
                $p['encryption'] = $data['encryption'] ?? 'tls';
                $p['from_address'] = $data['from_address'] ?? '';
                $p['from_name'] = $data['from_name'] ?? '';
                $updated = true;
                break;
            }
        }
        unset($p);

        if (! $updated) {
            return back()->with('error', 'Email provider not found.');
        }

        $this->writeEmailProviders($providers);

        AuditLog::record('Updated email provider', 'Communication — Settings', $data['name']);

        return back()->with('success', "Email provider '{$data['name']}' updated.");
    }

    public function emailProviderDelete(string $key)
    {
        $providers = collect($this->readEmailProviders())->reject(fn ($p) => ($p['key'] ?? null) === $key)->values()->all();
        $this->writeEmailProviders($providers);

        AuditLog::record('Removed email provider', 'Communication — Settings', $key);

        return back()->with('success', 'Email provider removed.');
    }

    public function emailProviderPrimary(string $key)
    {
        $providers = $this->readEmailProviders();

        if (! collect($providers)->contains('key', $key)) {
            return back()->with('error', 'Email provider not found.');
        }

        Setting::put('mail.primary', $key);
        $this->syncEmailPrimary();

        AuditLog::record('Set primary email provider', 'Communication — Settings', $key);

        return back()->with('success', 'Primary email provider updated.');
    }

    public function useTemplate(Request $request)
    {
        $template = $request->input('template');
        $name = $request->input('name', '');

        return redirect()->route('messaging.sms')->with('template', $template)->with('templateName', $name);
    }
}
