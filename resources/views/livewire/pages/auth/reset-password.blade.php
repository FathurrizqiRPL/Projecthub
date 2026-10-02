<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));
            return;
        }

        session()->flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    {{-- Heading --}}
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">
            Buat password baru
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Reset password
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Masukkan email akun Anda dan buat password baru untuk melanjutkan.
        </p>
    </div>

    <form wire:submit="resetPassword" class="space-y-5">

        {{-- Email --}}
        <div>
            <x-input-label
                for="email"
                :value="__('Email')"
            />

            <x-text-input
                wire:model="email"
                id="email"
                class="block w-full mt-2"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="nama@namaperusahaan.com"
            />

            <x-input-error
                :messages="$errors->get('email')"
                class="mt-2"
            />
        </div>

        {{-- New Password --}}
        <div>
            <x-input-label
                for="password"
                :value="__('Password Baru')"
            />

            <x-text-input
                wire:model="password"
                id="password"
                class="block w-full mt-2"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Masukkan password baru"
            />

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2"
            />
        </div>

        {{-- Confirm Password --}}
        <div>
            <x-input-label
                for="password_confirmation"
                :value="__('Konfirmasi Password Baru')"
            />

            <x-text-input
                wire:model="password_confirmation"
                id="password_confirmation"
                class="block w-full mt-2"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Ulangi password baru"
            />

            <x-input-error
                :messages="$errors->get('password_confirmation')"
                class="mt-2"
            />
        </div>

        {{-- Submit --}}
        <x-primary-button class="w-full">
            Reset Password
        </x-primary-button>

        {{-- Back to Login --}}
        <a
            href="{{ route('login') }}"
            wire:navigate
            class="block text-sm font-semibold text-center transition text-slate-600 hover:text-red-600"
        >
            Kembali ke halaman login
        </a>

    </form>
</div>
