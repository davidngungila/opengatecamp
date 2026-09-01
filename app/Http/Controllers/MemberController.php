<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FinancialYear;
use App\Models\Group;
use App\Models\Member;
use App\Models\MemberActivation;
use App\Models\Ministry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        [$fy, $activatedIds, $unactivatedCount] = $this->activationContext();
        [, $members] = $this->filteredMembers($request, $fy);

        return view('members.index', [
            'members' => $members,
            'total' => Member::count(),
            'families' => Family::orderBy('name')->get(),
            'groups' => Group::orderBy('name')->get(),
            'ministries' => Ministry::orderBy('name')->get(),
            'editMember' => null,
            'fy' => $fy,
            'activatedIds' => $activatedIds,
            'unactivatedCount' => $unactivatedCount,
        ]);
    }

    public function edit(Request $request, Member $member)
    {
        [$fy, $activatedIds, $unactivatedCount] = $this->activationContext();
        [, $members] = $this->filteredMembers($request, $fy);

        return view('members.index', [
            'members' => $members,
            'total' => Member::count(),
            'families' => Family::orderBy('name')->get(),
            'groups' => Group::orderBy('name')->get(),
            'ministries' => Ministry::orderBy('name')->get(),
            'editMember' => $member,
            'fy' => $fy,
            'activatedIds' => $activatedIds,
            'unactivatedCount' => $unactivatedCount,
        ]);
    }

    private function activationContext(): array
    {
        $fy = FinancialYear::current();

        if (! $fy) {
            return [null, collect(), 0];
        }

        $activatedIds = MemberActivation::where('financial_year_id', $fy->id)->pluck('member_id');
        $unactivatedCount = Member::where('member_type', 'student')
            ->whereNotIn('id', $activatedIds)
            ->count();

        return [$fy, $activatedIds, $unactivatedCount];
    }

    private function filteredMembers(Request $request, ?FinancialYear $fy): array
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));
        $groupId = $request->query('group_id');
        $ministryId = $request->query('ministry_id');
        $memberType = $request->query('member_type');
        $staffType = $request->query('staff_type');

        $query = Member::with(['family', 'group', 'ministry'])
            ->when($status, fn ($qr) => $qr->where('status', $status))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('member_no', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->when($groupId, fn ($qr) => $qr->where('group_id', $groupId))
            ->when($ministryId, fn ($qr) => $qr->where('ministry_id', $ministryId))
            ->when($memberType === 'student', fn ($qr) => $qr->where('member_type', 'student'))
            ->when($memberType === 'non_student', fn ($qr) => $qr->where('member_type', 'non_student'))
            ->when($staffType === 'staff', fn ($qr) => $qr->where('member_type', 'non_student')->where('staff_type', 'staff'))
            ->when($staffType === 'non_staff', fn ($qr) => $qr->where('member_type', 'non_student')->where('staff_type', 'non_staff'));

        if ($memberType === 'students_pending') {
            $query->where('member_type', 'student')
                ->whereNotIn('id', MemberActivation::where('financial_year_id', $fy?->id ?? 0)->pluck('member_id'));
        }

        if ($fy && ! $request->boolean('all_time') && !$status && !$memberType && !$staffType && !$groupId && !$ministryId && $q === '') {
            $query->whereBetween('joined_on', [$fy->start_date, $fy->end_date]);
        }

        $filters = ['status' => $status, 'q' => $q];

        return [$filters, $query->orderBy('name')->paginate(8)->withQueryString()];
    }

    public function profile(string $key)
    {
        try {
            $id = Crypt::decryptString($key);
        } catch (\Throwable) {
            abort(404);
        }

        $member = Member::with(['family', 'group', 'ministry'])->findOrFail($id);

        return view('members.profile', ['member' => $member]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['member_no'] = $this->nextMemberNo();

        $member = Member::create($data);
        $this->maybeAutoActivate($member);

        AuditLog::record('Created member', 'Members', "{$member->member_no} — {$member->name}");

        if ($request->input('action') === 'again') {
            return redirect()->route('members.edit', $member)->with('success', 'Member saved. You can register another one.');
        }

        return redirect()->route('members.index')->with('success', "Member {$member->name} registered successfully.");
    }

    public function update(Request $request, Member $member)
    {
        $member->update($this->validated($request));

        AuditLog::record('Updated member', 'Members', "{$member->member_no} — {$member->name}");

        return redirect()->route('members.index')->with('success', "Member {$member->name} updated successfully.");
    }

    public function destroy(Member $member)
    {
        AuditLog::record('Deleted member', 'Members', "{$member->member_no} — {$member->name}");
        $name = $member->name;
        $member->delete();

        return redirect()->route('members.index')->with('success', "Member {$name} deleted successfully.");
    }

    public function toggleStatus(Member $member)
    {
        $member->update(['status' => $member->status === 'Active' ? 'Inactive' : 'Active']);

        AuditLog::record('Toggled member status', 'Members', "{$member->member_no} â†’ {$member->status}");

        return back()->with('success', "Member {$member->name} is now {$member->status}.");
    }

    public function activate(Member $member)
    {
        $fy = FinancialYear::current();

        if (! $fy) {
            return back()->with('error', 'Select a financial year first.');
        }

        MemberActivation::firstOrCreate(
            ['member_id' => $member->id, 'financial_year_id' => $fy->id],
            ['activated_at' => now(), 'activated_by' => auth()->user()?->name ?? 'Daniel Mwinuka']
        );

        AuditLog::record('Activated student account', 'Members', "{$member->member_no} for {$fy->name}");

        return back()->with('success', "{$member->name} activated for {$fy->name}.");
    }

    public function activateAll()
    {
        $fy = FinancialYear::current();

        if (! $fy) {
            return back()->with('error', 'Select a financial year first.');
        }

        $already = MemberActivation::where('financial_year_id', $fy->id)->pluck('member_id');
        $pending = Member::where('member_type', 'student')->whereNotIn('id', $already)->get();

        foreach ($pending as $member) {
            MemberActivation::create([
                'member_id' => $member->id,
                'financial_year_id' => $fy->id,
                'activated_at' => now(),
                'activated_by' => auth()->user()?->name ?? 'Daniel Mwinuka',
            ]);
        }

        AuditLog::record('Bulk-activated students', 'Members', "{$pending->count()} accounts for {$fy->name}");

        if ($pending->isEmpty()) {
            return back()->with('info', 'All student accounts are already active for '.$fy->name.'.');
        }

        return back()->with('success', "{$pending->count()} student account(s) activated for {$fy->name}.");
    }

    private function maybeAutoActivate(Member $member): void
    {
        $fy = FinancialYear::current();

        if ($fy && $member->isStudent()) {
            MemberActivation::firstOrCreate(
                ['member_id' => $member->id, 'financial_year_id' => $fy->id],
                ['activated_at' => now(), 'activated_by' => auth()->user()?->name ?? 'Daniel Mwinuka']
            );
        }
    }

    private function nextMemberNo(): string
    {
        $max = (int) substr((string) (Member::query()->max('member_no') ?? 'CHP-1000'), -4);

        return 'CHP-'.($max + 1);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string|max:50',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'family_id' => 'nullable|exists:families,id',
            'group_id' => 'nullable|exists:groups,id',
            'ministry_id' => 'nullable|exists:ministries,id',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:30',
            'status' => 'required|in:Active,Inactive,New',
            'joined_on' => 'nullable|date',
            'member_type' => 'required|in:student,non_student',
            'staff_type' => 'nullable|in:staff,non_staff',
        ]);

        $data['joined_on'] = ($data['joined_on'] ?? null) ?: now()->toDateString();

        if ($data['member_type'] === 'student') {
            $data['staff_type'] = null;
        }

        return $data;
    }
}
