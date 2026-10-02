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
                'password' => __('auth.password'),
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
            Konfirmasi password
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Untuk melanjutkan ke tindakan yang memerlukan keamanan tambahan,
            silakan masukkan password akun Anda.
        </p>
    </div>

    {{-- Confirm Password Form --}}
    <form wire:submit="confirmPassword" class="space-y-5">

        <div>
            <x-input-label
                for="password"
                :value="__('Password')"
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
                placeholder="Masukkan password Anda"
            />

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2"
            />
        </div>

        <x-primary-button class="w-full">
            Konfirmasi Password
        </x-primary-button>

    </form>
</div>
