<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'users');

        return view('users.index', [
            'tab' => in_array($tab, ['users', 'roles', 'permissions']) ? $tab : 'users',
            'users' => User::with('role')->orderBy('name')->get(),
            'roles' => Role::withCount('users')->orderBy('id')->get(),
            'permissions' => Role::PERMISSIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:30',
        ]);

        $tempPassword = Str::random(10);
        $data['password'] = Hash::make($tempPassword);

        $user = User::create($data);

        AuditLog::record('Created user', 'Users & Roles', "{$user->name} <{$user->email}>");

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} invited. Temporary password: {$tempPassword}")
            ->with('show_password', $tempPassword);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:30',
            'status' => 'required|in:Active,Suspended',
        ]);

        $user->update($data);

        AuditLog::record('Updated user', 'Users & Roles', "{$user->name} ({$user->status})");

        return redirect()->route('users.index')->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('Super Administrator')) {
            return redirect()->route('users.index')->with('error', 'The Super Administrator account cannot be deleted.');
        }

        AuditLog::record('Deleted user', 'Users & Roles', "{$user->name} <{$user->email}>");
        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "User {$name} deleted successfully.");
    }

    public function toggleSuspend(User $user)
    {
        if ($user->hasRole('Super Administrator')) {
            return redirect()->route('users.index')->with('error', 'The Super Administrator cannot be suspended.');
        }

        $user->update(['status' => $user->status === 'Active' ? 'Suspended' : 'Active']);

        AuditLog::record('Toggled user status', 'Users & Roles', "{$user->name} → {$user->status}");

        return back()->with('success', "User {$user->name} is now {$user->status}.");
    }

    public function resetPassword(User $user)
    {
        $tempPassword = Str::random(10);
        $user->update(['password' => Hash::make($tempPassword)]);

        AuditLog::record('Reset password', 'Users & Roles', $user->name);

        return back()->with('success', "Password reset for {$user->name}. New password: {$tempPassword}");
    }

    public function apiUserDetail(User $user)
    {
        $user->load('role');

        $permissions = array_map(function ($key) use ($user) {
            $has = $user->role && ($user->role->is_super || in_array($key, $user->role->permissions ?? []));
            return ['key' => $key, 'granted' => $has];
        }, Role::PERMISSIONS);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'role' => $user->role?->name ?? '—',
                'role_id' => $user->role_id,
                'is_super' => $user->role?->is_super ?? false,
                'last_login' => $user->last_login_at?->toDateTimeString(),
                'created' => $user->created_at?->format('d M Y'),
            ],
            'permissions' => $permissions,
        ]);
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:'.implode(',', Role::PERMISSIONS),
        ]);

        $role->update(['permissions' => array_values($data['permissions'] ?? [])]);

        AuditLog::record('Updated role permissions', 'Users & Roles', $role->name);

        return redirect()->route('users.index', ['tab' => 'permissions'])->with('success', "Permissions for {$role->name} saved successfully.");
    }
}
