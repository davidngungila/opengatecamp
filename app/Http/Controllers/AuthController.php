<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($data['login']);
        $password = $data['password'];

        $isPhone = preg_match('/^[0-9+\s\-().]{7,20}$/', $login) && strpos($login, '@') === false;

        $user = null;

        if ($isPhone) {
            $phone = preg_replace('/[\s\-().]+/', '', $login);

            // Normalize: strip all non-digits, handle 0 prefix → 255 prefix
            $norm = function (string $raw) {
                $digits = preg_replace('/[^0-9]/', '', $raw);
                if (strlen($digits) >= 10 && str_starts_with($digits, '0')) {
                    $digits = '255' . substr($digits, 1);
                }
                return $digits;
            };

            $inputNorm = $norm($phone);

            // 1) Match an existing admin/leader/committee user by phone
            $phoneUsers = User::whereNotNull('phone')->where('phone', '!=', '')->get();
            $user = $phoneUsers->first(function ($u) use ($inputNorm, $norm) {
                $dbNorm = $norm($u->phone);
                return $dbNorm === $inputNorm || str_ends_with($dbNorm, $inputNorm) || str_ends_with($inputNorm, $dbNorm);
            });

            // 2) Otherwise find the member (their phone becomes their username)
            if (! $user) {
                $members = Member::whereNotNull('phone')->where('phone', '!=', '')->get();
                $member = $members->first(function ($m) use ($inputNorm, $norm) {
                    $dbNorm = $norm($m->phone);
                    return $dbNorm === $inputNorm || str_ends_with($dbNorm, $inputNorm) || str_ends_with($inputNorm, $dbNorm);
                });

                if ($member) {
                    // Reuse existing linked account, otherwise find by phone
                    $user = User::where('member_id', $member->id)->first()
                        ?? User::where('phone', $member->phone)->first();

                    if (! $user) {
                        $email = $member->email ?: ($member->phone . '@opengatecamp.local');
                        if (User::where('email', $email)->exists()) {
                            $email = 'member' . $member->id . '@opengatecamp.local';
                        }

                        $user = User::create([
                            'name'     => $member->name,
                            'email'    => $email,
                            'phone'    => $member->phone,
                            'password' => Hash::make('password'),
                            'role_id'  => null,
                            'member_id' => $member->id,
                            'status'   => 'Active',
                        ]);

                        AuditLog::record(
                            'Auto-created user account via phone login',
                            'System',
                            $member->name . ' (' . $member->phone . ')'
                        );
                    }
                }
            }
        } else {
            $user = User::where('email', $login)->first();
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
        }

        if ($user->status === 'Suspended') {
            return back()->withErrors(['login' => 'This account is suspended. Contact the administrator.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $user->update(['last_login_at' => now()]);

        AuditLog::record('Logged in', 'System', 'via ' . ($isPhone ? 'phone' : 'email'));

        // Members without admin role go to portal
        if ($user->member_id && ! $user->role_id) {
            return redirect()->intended(route('portal.dashboard'))->with('success', 'Welcome back, ' . $user->name . '!');
        }

        return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        AuditLog::record('Logged out', 'System');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Signed out successfully.');
    }
}
