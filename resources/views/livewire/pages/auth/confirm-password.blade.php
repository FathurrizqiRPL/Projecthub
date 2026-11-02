<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => 'Kata sandi yang dimasukkan tidak cocok.',
            ]);
        }

        session()->put('auth.password_confirmed_at', time());

        $this->redirectIntended(
            default: route('dashboard', absolute: false),
            navigate: true
        );
    }
}; ?>

<div>
    {{-- Heading --}}
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">
            Konfirmasi keamanan
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Konfirmasi kata sandi
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Untuk melanjutkan ke tindakan yang memerlukan keamanan tambahan,
            silakan masukkan Kata sandi akun Anda.
        </p>
    </div>

    {{-- Formulir konfirmasi kata sandi --}}
    <form wire:submit="confirmPassword" class="space-y-5">

        <div>
            <x-input-label
                for="password"
                :value="__('Kata sandi')"
            />

            <x-text-input
                wire:model="password"
                id="password"
                class="block w-full mt-2"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                placeholder="Masukkan Kata sandi Anda"
            />

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2"
            />
        </div>

        <x-primary-button class="w-full">
            Konfirmasi kata sandi
        </x-primary-button>

    </form>
</div>
