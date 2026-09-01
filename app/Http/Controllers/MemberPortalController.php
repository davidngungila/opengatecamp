<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FinancialYear;
use App\Models\Member;
use App\Models\ReceiptPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberPortalController extends Controller
{
    private function getMember(): ?Member
    {
        return Auth::user()?->member;
    }

    public function dashboard()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $fy = FinancialYear::current();
        $activated = $member->isActivatedFor($fy?->id);
        $family = $member->family;
        $group = $member->group;
        $ministry = $member->ministry;

        $totalContributions = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->sum('amount');

        $fyContributions = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->when($fy, fn($q) => $q->whereBetween('pay_date', [$fy->start_date, $fy->end_date]))
            ->sum('amount');

        return view('portal.dashboard', compact(
            'member', 'fy', 'activated', 'family', 'group', 'ministry',
            'totalContributions', 'fyContributions'
        ));
    }

    public function profile()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        return view('portal.profile', ['member' => $member->load(['group', 'ministry', 'family'])]);
    }

    public function updateProfile(Request $request)
    {
        $member = $this->getMember();
        if (! $member) {
            return back()->with('error', 'No member record linked to your account.');
        }

        $data = $request->validate([
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:255',
            'emergency_name'  => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $member->update($data);

        AuditLog::record('Updated profile via member portal', 'Member Portal', $member->name);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function family()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $family = $member->family?->load('members');

        return view('portal.family', ['member' => $member, 'family' => $family]);
    }

    public function contributions()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $contributions = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->orderByDesc('pay_date')
            ->paginate(15);

        $totalAll = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->sum('amount');

        $fy = FinancialYear::current();
        $totalFY = ReceiptPayment::where('type', 'receipt')
            ->where('party', $member->name)
            ->when($fy, fn($q) => $q->whereBetween('pay_date', [$fy->start_date, $fy->end_date]))
            ->sum('amount');

        return view('portal.contributions', compact('contributions', 'totalAll', 'totalFY', 'fy'));
    }

    public function activations()
    {
        $member = $this->getMember();
        if (! $member) {
            return view('portal.no-member');
        }

        $activations = $member->activations()->with('financialYear')->get();
        $fy = FinancialYear::current();
        $activated = $member->isActivatedFor($fy?->id);

        return view('portal.activations', compact('member', 'activations', 'fy', 'activated'));
    }

    public function settings()
    {
        return view('portal.settings', ['user' => Auth::user()]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::record('Changed password via member portal', 'Member Portal', $user->name);

        return back()->with('success', 'Password changed successfully.');
    }
}
