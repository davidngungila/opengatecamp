<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
        $tab = $request->query('tab', 'general');
        $valid = ['general', 'appearance', 'notifications', 'security', 'backup', 'audit', 'financial-years'];

        return view('settings.index', [
            'tab' => in_array($tab, $valid) ? $tab : 'general',
            'auditLogs' => AuditLog::latest()->paginate(10, ['*'], 'page', $request->query('page', 1)),
            'years' => FinancialYear::orderByDesc('start_date')->get(),
            'adminUser' => User::whereHas('role', fn ($q) => $q->where('name', 'Super Administrator'))->first(),
        ]);
    }

    public function updateGeneral(Request $request)
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

        AuditLog::record('Updated church profile', 'Settings');

        return back()->with('success', 'Church profile saved successfully.');
    }

    public function updateNotifications(Request $request)
    {
        foreach (['email', 'sms', 'push', 'digest', 'payment_alerts'] as $key) {
            Setting::put("notify.{$key}", $request->boolean($key) ? '1' : '0');
        }

        AuditLog::record('Updated notification preferences', 'Settings');

        return back()->with('success', 'Notification preferences saved successfully.');
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
