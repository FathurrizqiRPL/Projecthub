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
            Buat kata sandi baru
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Atur ulang kata sandi
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Masukkan alamat email akun Anda dan buat kata sandi baru untuk melanjutkan.
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

        {{-- Kata sandi baru --}}
        <div>
            <x-input-label
                for="password"
                :value="__('Kata sandi baru')"
            />

            <x-text-input
                wire:model="password"
                id="password"
                class="block w-full mt-2"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Masukkan kata sandi baru"
            />

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2"
            />
        </div>

        {{-- Konfirmasi kata sandi --}}
        <div>
            <x-input-label
                for="password_confirmation"
                :value="__('Konfirmasi kata sandi Baru')"
            />

            <x-text-input
                wire:model="password_confirmation"
                id="password_confirmation"
                class="block w-full mt-2"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
            />

            <x-input-error
                :messages="$errors->get('password_confirmation')"
                class="mt-2"
            />
        </div>

        {{-- Kirim --}}
        <x-primary-button class="w-full">
            Atur ulang kata sandi
        </x-primary-button>

        {{-- Kembali ke masuk --}}
        <a
            href="{{ route('login') }}"
            wire:navigate
            class="block text-sm font-semibold text-center transition text-slate-600 hover:text-red-600"
        >
            Kembali ke halaman masuk
        </a>

    </form>
</div>
