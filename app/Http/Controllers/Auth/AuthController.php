<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);
        $throttleKey = Str::transliterate(
            Str::lower(trim($validated['username'])).'|'.$request->ip()
        );

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt([
            'username' => Str::lower(trim($validated['username'])),
            'password' => $validated['password'],
        ], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'Username atau password yang Anda masukkan salah.',
            ]);
        }

        if (Auth::user()->status !== 'active') {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Akun Anda sedang tidak aktif. Silakan hubungi administrator.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);
        $status = Password::sendResetLink($validated);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __($status));
    }

    public function showResetPassword(Request $request, string $token): View
    {
        $email = $request->query('email', '');

        return view('auth.reset-password', [
            'email' => is_string($email) ? $email : '',
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()
            ->route('login')
            ->with('status', __($status));
    }

    public function showConfirmPassword(): View
    {
        return view('auth.confirm-password');
    }

    public function confirmPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Kata sandi yang dimasukkan tidak cocok.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                PasswordRule::defaults(),
                'confirmed',
            ],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard');
    }

    public function showVerificationNotice(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.verify-email');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $user->sendEmailVerificationNotification();

        return back()->with(
            'status',
            'Tautan verifikasi baru telah dikirim ke email Anda.'
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
