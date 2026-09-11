<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('settings.page.general');
    }

    public function generalPage()
    {
        $eventUsers = User::with('role')->whereDoesntHave('role', function ($q) {
            $q->where('name', 'Super Administrator');
        })->orderBy('name')->get();

        return view('settings.pages.general', compact('eventUsers'));
    }

    public function notificationsPage()
    {
        return view('settings.pages.notifications');
    }

    public function accountingPage()
    {
        return view('settings.pages.accounting', [
            'cashAccounts' => Account::where('is_cash', true)->orderBy('code')->get(),
            'incomeAccounts' => Account::where('type', 'income')->orderBy('code')->get(),
            'expenseAccounts' => Account::where('type', 'expense')->orderBy('code')->get(),
        ]);
    }

    public function financialYearsPage()
    {
        return view('settings.pages.financial-years', [
            'years' => FinancialYear::orderByDesc('start_date')->get(),
        ]);
    }

    public function fellowshipsPage()
    {
        return view('settings.pages.fellowships', [
            'fellowships' => $this->fellowshipList(),
            'dioceseMap' => $this->fellowshipDioceseMap(),
            'dioceses' => $this->dioceseOptions(),
        ]);
    }

    private function dioceseOptions(): array
    {
        return [
            '' => '— Select Diocese —',
            'Arusha' => 'Arusha',
            'Moshi' => 'Moshi',
        ];
    }

    private function fellowshipDioceseMap(): array
    {
        $raw = (string) Setting::get('fellowships.dioceses', '');
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function saveFellowshipDioceseMap(array $map): void
    {
        Setting::put('fellowships.dioceses', json_encode($map, JSON_UNESCAPED_UNICODE));
    }

    public function securityPage(Request $request)
    {
        return view('settings.pages.security', [
            'adminUser' => User::whereHas('role', fn ($q) => $q->where('name', 'Super Administrator'))->first(),
        ]);
    }

    public function backupPage()
    {
        return view('settings.pages.backup');
    }

    public function auditPage(Request $request)
    {
        $selectedUserId = $request->integer('user_id');

        $query = AuditLog::query();
        if ($selectedUserId > 0) {
            $query->where('user_id', $selectedUserId);
        }

        return view('settings.pages.audit', [
            'auditLogs' => $query->latest()
                ->paginate(10, ['*'], 'page', $request->query('page', 1))
                ->withQueryString(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'selectedUserId' => $selectedUserId,
        ]);
    }

    public function updateFellowships(Request $request)
    {
        $data = $request->validate([
            'fellowships' => 'nullable|string',
        ]);

        $list = collect(explode("\n", $data['fellowships'] ?? ''))
            ->map(fn ($f) => trim($f))
            ->filter()
            ->unique()
            ->values()
            ->implode("\n");

        Setting::put('fellowships.list', $list);

        AuditLog::record('Updated university fellowship list', 'Settings — Fellowships', "{$this->fellowshipListCount()} fellowships configured");

        return back()->with('success', 'Fellowship list saved successfully.');
    }

    public function storeFellowship(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'diocese' => 'nullable|in:Arusha,Moshi',
        ]);

        $list = $this->fellowshipList();
        $name = trim($data['name']);
        $list[] = $name;

        $this->saveFellowshipList($list);

        $map = $this->fellowshipDioceseMap();
        $map[$name] = trim((string) ($data['diocese'] ?? ''));
        $this->saveFellowshipDioceseMap($map);

        AuditLog::record('Added university fellowship', 'Settings &mdash; Fellowships', $name.(!empty($data['diocese']) ? " ({$data['diocese']})" : ''));

        return back()->with('success', "Fellowship '{$name}' added.");
    }

    public function updateFellowship(Request $request, int $index)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'diocese' => 'nullable|in:Arusha,Moshi',
        ]);

        $list = $this->fellowshipList();
        if (! isset($list[$index])) {
            return back()->with('error', 'Fellowship not found.');
        }

        $oldName = $list[$index];
        $newName = trim($data['name']);
        $list[$index] = $newName;

        $this->saveFellowshipList(array_values($list));

        $map = $this->fellowshipDioceseMap();
        // Remove old entry if name changed
        if ($oldName !== $newName && isset($map[$oldName])) {
            unset($map[$oldName]);
        }
        $map[$newName] = trim((string) ($data['diocese'] ?? ''));
        $this->saveFellowshipDioceseMap($map);

        AuditLog::record('Updated university fellowship', 'Settings &mdash; Fellowships', $newName.(!empty($data['diocese']) ? " ({$data['diocese']})" : ''));

        return back()->with('success', "Fellowship '{$newName}' updated.");
    }

    public function destroyFellowship(int $index)
    {
        $list = $this->fellowshipList();
        if (! isset($list[$index])) {
            return back()->with('error', 'Fellowship not found.');
        }

        $name = $list[$index];
        unset($list[$index]);

        $this->saveFellowshipList(array_values($list));

        $map = $this->fellowshipDioceseMap();
        if (isset($map[$name])) {
            unset($map[$name]);
            $this->saveFellowshipDioceseMap($map);
        }

        AuditLog::record('Removed university fellowship', 'Settings &mdash; Fellowships', 'Removed 1 fellowship');

        return back()->with('success', 'Fellowship removed.');
    }

    private function saveFellowshipList(array $list): void
    {
        Setting::put('fellowships.list', implode("\n", array_values($list)));
    }

    private function fellowshipList(): array
    {
        $raw = (string) Setting::get('fellowships.list', '');
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

    private function fellowshipListCount(): int
    {
        return count($this->fellowshipList());
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'event_name' => 'required|string|max:255',
            'event_status' => 'nullable|string|max:50',
            'event_description' => 'nullable|string|max:2000',
            'event_venue' => 'nullable|string|max:255',
            'event_location' => 'nullable|string|max:255',
            'event_start_date' => 'nullable|date',
            'event_end_date' => 'nullable|date|after_or_equal:event_start_date',
            'event_start_time' => 'nullable',
            'event_end_time' => 'nullable',
            'event_capacity' => 'nullable|integer|min:0',
            'event_registration_fee' => 'nullable|numeric|min:0',
            'event_organizer' => 'nullable|string|max:255',
            'event_chairperson_id' => 'nullable|integer|exists:users,id',
            'event_secretary_id' => 'nullable|integer|exists:users,id',
            'event_treasurer_id' => 'nullable|integer|exists:users,id',
        ]);

        $officers = collect(['event_chairperson_id', 'event_secretary_id', 'event_treasurer_id'])
            ->map(fn ($f) => $data[$f] ?? null)
            ->filter()
            ->values();

        if ($officers->unique()->count() !== $officers->count()) {
            return back()->with('error', 'A user can hold only one of the three event leadership posts (Chairperson, Secretary, Treasurer).');
        }

        foreach ([
            'event_name' => 'event.name',
            'event_status' => 'event.status',
            'event_description' => 'event.description',
            'event_venue' => 'event.venue',
            'event_location' => 'event.location',
            'event_start_date' => 'event.start_date',
            'event_end_date' => 'event.end_date',
            'event_start_time' => 'event.start_time',
            'event_end_time' => 'event.end_time',
            'event_capacity' => 'event.capacity',
            'event_registration_fee' => 'event.registration_fee',
            'event_organizer' => 'event.organizer',
            'event_chairperson_id' => 'event.chairperson_id',
            'event_secretary_id' => 'event.secretary_id',
            'event_treasurer_id' => 'event.treasurer_id',
        ] as $field => $key) {
            Setting::put($key, $data[$field] ?? null);
        }

        $roleMap = [
            'event_chairperson_id' => 'Chairperson',
            'event_secretary_id' => 'Secretary',
            'event_treasurer_id' => 'Treasurer',
        ];

        foreach ($roleMap as $field => $roleName) {
            $userId = $data[$field] ?? null;
            if (! $userId) {
                continue;
            }

            $user = User::find($userId);
            $role = Role::where('name', $roleName)->first();

            if ($user && $role && $user->role_id !== $role->id) {
                $previous = $user->role?->name ?? 'no role';
                $user->update(['role_id' => $role->id]);

                AuditLog::record("Assigned {$roleName} of the event", 'Settings &mdash; Event', "{$user->name} role changed from '{$previous}' to '{$roleName}'");
            }
        }

        $chair = ! empty($data['event_chairperson_id']) ? User::find($data['event_chairperson_id'])?->name : null;
        Setting::put('event.organizer', $chair ?? '');

        AuditLog::record('Updated event settings', 'Settings &mdash; Event');

        return back()->with('success', 'Event settings saved successfully.');
    }

    public function updateDigitalCard(Request $request)
    {
        $data = $request->validate([
            'digital_card_title' => 'nullable|string|max:255',
            'digital_card_message' => 'nullable|string|max:2000',
            'digital_card_target_amount' => 'nullable|numeric|min:0',
            'digital_card_background_color' => 'nullable|string|max:7',
            'digital_card_accent_color' => 'nullable|string|max:7',
            'digital_card_cta_text' => 'nullable|string|max:100',
            'digital_card_sms_text' => 'nullable|string',
            'digital_card_status' => 'nullable|in:active,closed',
            'digital_card_background_image' => 'nullable|image|mimes:jpeg,png,webp|max:4096',
            'digital_card_leader_event_name' => 'nullable|string|max:255',
            'digital_card_leader_secretary_name' => 'nullable|string|max:255',
            'digital_card_leader_treasurer_name' => 'nullable|string|max:255',
            'digital_card_leader_secretary_phone' => 'nullable|string|max:30',
            'digital_card_leader_treasurer_phone' => 'nullable|string|max:30',
            'digital_card_org_main' => 'nullable|string|max:255',
            'digital_card_org_sub' => 'nullable|string|max:255',
            'digital_card_campaign_small' => 'nullable|string|max:255',
            'digital_card_campaign_large' => 'nullable|string|max:255',
            'digital_card_campaign_kadi' => 'nullable|string|max:255',
            'digital_card_donor_title' => 'nullable|string|max:255',
            'digital_card_body_1' => 'nullable|string|max:2000',
            'digital_card_body_2' => 'nullable|string|max:2000',
            'digital_card_body_3' => 'nullable|string|max:2000',
            'digital_card_contribution_note' => 'nullable|string|max:3000',
            'digital_card_qr_title' => 'nullable|string|max:255',
            'digital_card_motto' => 'nullable|string|max:500',
            'digital_card_footer_contact' => 'nullable|string|max:255',
            'digital_card_footer_tagline' => 'nullable|string|max:255',
            'digital_card_leader_event_stamp' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'digital_card_leader_secretary_stamp' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'digital_card_leader_treasurer_stamp' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('digital_card_background_image')) {
            $image = $request->file('digital_card_background_image');
            $path = $image->store('digital-cards', 'public');

            $old = (string) Setting::get('digital_card.background_image', '');
            if ($old !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($old) && $old !== $path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            }

            Setting::put('digital_card.background_image', $path);
        }

        if ($request->filled('digital_card_remove_background')) {
            $old = (string) Setting::get('digital_card.background_image', '');
            if ($old !== '') {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            }
            Setting::put('digital_card.background_image', '');
        }

        foreach ([
            'digital_card_leader_event_stamp' => 'digital_card.leader_event_stamp',
            'digital_card_leader_secretary_stamp' => 'digital_card.leader_secretary_stamp',
            'digital_card_leader_treasurer_stamp' => 'digital_card.leader_treasurer_stamp',
        ] as $field => $key) {
            if ($request->hasFile($field)) {
                $image = $request->file($field);
                $path = $image->store('digital-cards', 'public');

                $old = (string) Setting::get($key, '');
                if ($old !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($old) && $old !== $path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
                }

                Setting::put($key, $path);
            }
        }

        foreach ([
            'digital_card_title' => 'digital_card.title',
            'digital_card_message' => 'digital_card.message',
            'digital_card_target_amount' => 'digital_card.target_amount',
            'digital_card_background_color' => 'digital_card.background_color',
            'digital_card_accent_color' => 'digital_card.accent_color',
            'digital_card_cta_text' => 'digital_card.cta_text',
            'digital_card_sms_text' => 'digital_card.sms_text',
            'digital_card_status' => 'digital_card.status',
            'digital_card_leader_event_name' => 'digital_card.leader_event_name',
            'digital_card_leader_secretary_name' => 'digital_card.leader_secretary_name',
            'digital_card_leader_treasurer_name' => 'digital_card.leader_treasurer_name',
            'digital_card_leader_secretary_phone' => 'digital_card.leader_secretary_phone',
            'digital_card_leader_treasurer_phone' => 'digital_card.leader_treasurer_phone',
            'digital_card_org_main' => 'digital_card.org_main',
            'digital_card_org_sub' => 'digital_card.org_sub',
            'digital_card_campaign_small' => 'digital_card.campaign_small',
            'digital_card_campaign_large' => 'digital_card.campaign_large',
            'digital_card_campaign_kadi' => 'digital_card.campaign_kadi',
            'digital_card_donor_title' => 'digital_card.donor_title',
            'digital_card_body_1' => 'digital_card.body_1',
            'digital_card_body_2' => 'digital_card.body_2',
            'digital_card_body_3' => 'digital_card.body_3',
            'digital_card_contribution_note' => 'digital_card.contribution_note',
            'digital_card_qr_title' => 'digital_card.qr_title',
            'digital_card_motto' => 'digital_card.motto',
            'digital_card_footer_contact' => 'digital_card.footer_contact',
            'digital_card_footer_tagline' => 'digital_card.footer_tagline',
        ] as $field => $key) {
            Setting::put($key, $data[$field] ?? null);
        }

        AuditLog::record('Updated digital card settings', 'Settings — Digital Card');

        return back()->with('success', 'Digital card settings saved successfully.');
    }

    public function updateOrganization(Request $request)
    {
        $data = $request->validate([
            'church_name' => 'required|string|max:255',
            'church_phone' => 'nullable|string|max:30',
            'church_email' => 'nullable|email|max:255',
            'church_website' => 'nullable|string|max:255',
            'church_address' => 'nullable|string|max:500',
            'chaplain' => 'required|string|max:255',
        ]);

        foreach ([
            'church_name' => 'church.name',
            'church_phone' => 'church.phone',
            'church_email' => 'church.email',
            'church_website' => 'church.website',
            'church_address' => 'church.address',
            'chaplain' => 'church.chaplain',
        ] as $field => $key) {
            Setting::put($key, $data[$field] ?? null);
        }

        AuditLog::record('Updated organization profile', 'Settings');

        return back()->with('success', 'Organization settings saved successfully.');
    }

    public function updateNotifications(Request $request)
    {
        foreach (['email', 'sms', 'push', 'digest', 'payment_alerts'] as $key) {
            Setting::put("notify.{$key}", $request->boolean($key) ? '1' : '0');
        }

        AuditLog::record('Updated notification preferences', 'Settings');

        return back()->with('success', 'Notification preferences saved successfully.');
    }

    public function updateAccounting(Request $request)
    {
        $data = $request->validate([
            'acct_default_cash' => 'nullable|exists:accounts,code',
            'acct_default_bank' => 'nullable|exists:accounts,code',
            'acct_default_mobile' => 'nullable|exists:accounts,code',
            'acct_pledge_income' => 'nullable|exists:accounts,code',
            'acct_attendee_income' => 'nullable|exists:accounts,code',
        ]);

        foreach ([
            'acct_default_cash' => 'acct.default_cash',
            'acct_default_bank' => 'acct.default_bank',
            'acct_default_mobile' => 'acct.default_mobile',
            'acct_pledge_income' => 'acct.pledge_income',
            'acct_attendee_income' => 'acct.attendee_income',
        ] as $field => $key) {
            Setting::put($key, $data[$field] ?? null);
        }

        AuditLog::record('Updated accounting account defaults', 'Settings — Accounting');

        return back()->with('success', 'Accounting defaults saved successfully.');
    }

    public function updateSecurity(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = User::whereHas('role', fn ($q) => $q->where('name', 'Super Administrator'))->first();

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            return back()->with('error', 'The current password is incorrect.');
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        AuditLog::record('Changed admin password', 'Settings — Security');

        return back()->with('success', 'Password changed successfully.');
    }

    public function storeYear(Request $request)
    {
        $data = $this->validatedYear($request);

        $year = FinancialYear::create($data);
        $this->applyDefault($year);

        AuditLog::record('Created financial year', 'Settings — Financial Years', "{$year->name} ({$year->start_date} → {$year->end_date})");

        return redirect()->route('settings.index', ['tab' => 'financial-years'])->with('success', "Financial year {$year->name} created successfully.");
    }

    public function updateYear(Request $request, FinancialYear $year)
    {
        $year->update($this->validatedYear($request, $year));
        $this->applyDefault($year);

        AuditLog::record('Updated financial year', 'Settings — Financial Years', $year->name);

        return redirect()->route('settings.index', ['tab' => 'financial-years'])->with('success', "Financial year {$year->name} updated successfully.");
    }

    public function destroyYear(FinancialYear $year)
    {
        if ($year->is_default && FinancialYear::count() > 1) {
            return redirect()->route('settings.index', ['tab' => 'financial-years'])
                ->with('error', 'Set another year as default before deleting this one.');
        }

        AuditLog::record('Deleted financial year', 'Settings — Financial Years', $year->name);
        $name = $year->name;
        $year->delete();

        return redirect()->route('settings.index', ['tab' => 'financial-years'])->with('success', "Financial year {$name} deleted successfully.");
    }

    public function switchYear(Request $request, int|string $yearId)
    {
        if ((int) $yearId === 0) {
            session(['fy_id' => null]);
            $message = 'Financial year filter cleared. Showing all periods.';
        } else {
            $year = FinancialYear::findOrFail($yearId);
            session(['fy_id' => $year->id]);
            $message = "Now viewing data for {$year->name}.";
        }

        return back()->with('success', $message);
    }

    public function clearAudit()
    {
        $count = AuditLog::count();
        AuditLog::query()->delete();
        AuditLog::record('Cleared audit logs', 'Settings — Audit', "{$count} entries removed");

        return redirect()->route('settings.index', ['tab' => 'audit'])->with('success', "Audit log cleared ({$count} entries).");
    }

    public function backup(): StreamedResponse
    {
        $tables = ['members', 'families', 'groups', 'ministries', 'users', 'roles', 'settings', 'financial_years', 'audit_logs'];

        AuditLog::record('Downloaded backup', 'Settings — Backup');

        $fileName = 'st-joseph-backup-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($tables) {
            $out = fopen('php://output', 'w');
            fwrite($out, '{"generated_at":"'.now()->toIso8601String().'","database":{');
            $first = true;
            foreach ($tables as $table) {
                if (! $first) {
                    fwrite($out, ',');
                }
                $first = false;
                fwrite($out, json_encode($table).':'.json_encode(\DB::table($table)->get()));
            }
            fwrite($out, '}}');
            fclose($out);
        }, $fileName, ['Content-Type' => 'application/json']);
    }

    private function applyDefault(FinancialYear $year): void
    {
        if ($year->is_default) {
            FinancialYear::whereKeyNot($year->id)->update(['is_default' => false]);
            session(['fy_id' => null]);
        }
    }

    private function validatedYear(Request $request, ?FinancialYear $existing = null): array
    {
        $ignore = $existing?->id ?? 0;

        return $request->validate([
            'name' => 'required|string|max:50|unique:financial_years,name,'.$ignore,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_default' => 'nullable|boolean',
        ]) + ['is_default' => $request->boolean('is_default')];
    }
}
