<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function profile()
    {
        return view('account.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'bio'   => 'nullable|string|max:500',
        ]);

        $user->update($data);

        AuditLog::record('Updated own profile', 'Account', ucfirst(strtolower($user->name)));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePhoto(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $image = $data['profile_image'];

        $dir = public_path('uploads/profiles');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Remove previous image if it lives in our uploads dir
        if ($user->profile_image && Str::startsWith($user->profile_image, '/uploads/profiles/')) {
            $old = public_path(trim($user->profile_image, '/'));
            if (is_file($old)) {
                @unlink($old);
            }
        }

        $name = 'user-'.$user->id.'-'.now()->format('YmdHis').'.'.$image->getClientOriginalExtension();
        $image->move($dir, $name);

        $user->update(['profile_image' => '/uploads/profiles/'.$name]);

        AuditLog::record('Updated profile photo', 'Account', ucfirst(strtolower($user->name)));

        return back()->with('success', 'Profile photo updated successfully.');
    }

    public function removePhoto()
    {
        $user = Auth::user();

        if ($user->profile_image && Str::startsWith($user->profile_image, '/uploads/profiles/')) {
            $old = public_path(trim($user->profile_image, '/'));
            if (is_file($old)) {
                @unlink($old);
            }
        }

        $user->update(['profile_image' => null]);

        AuditLog::record('Removed profile photo', 'Account', ucfirst(strtolower($user->name)));

        return back()->with('success', 'Profile photo removed.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'current_password'      => 'required',
            'password'              => 'required|min:6|confirmed',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
        }

        $user->update(['password' => Hash::make($data['password'])]);

        AuditLog::record('Changed own password', 'Account', ucfirst(strtolower($user->name)));

        return back()->with('success', 'Password changed successfully.');
    }

    public function settings()
    {
        $user = Auth::user();

        $lastEntries = AuditLog::where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        return view('account.settings', [
            'user' => $user,
            'lastEntries' => $lastEntries,
        ]);
    }

    public function auditLogs(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $module = $request->query('module');

        $query = AuditLog::with(['user'])->latest();

        if ($module && $module !== 'all') {
            $query->where('module', $module);
        }
        if ($q !== '') {
            $query->where(fn ($w) => $w
                ->where('user_name', 'like', "%{$q}%")
                ->orWhere('action', 'like', "%{$q}%")
                ->orWhere('details', 'like', "%{$q}%"));
        }

        $logs = $query->paginate(20)->withQueryString();

        $modules = AuditLog::query()
            ->select('module')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('account.audit-logs', [
            'logs' => $logs,
            'modules' => $modules,
            'filters' => compact('q', 'module'),
        ]);
    }
}