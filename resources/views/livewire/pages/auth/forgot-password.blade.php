<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($this->only('email'));

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));
            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    {{-- Heading --}}
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">
            Pemulihan akun
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Lupa password?
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Masukkan email akun Anda. Kami akan mengirimkan tautan untuk membuat password baru.
        </p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status
        class="px-4 py-3 mb-5 border rounded-xl border-emerald-200 bg-emerald-50"
        :status="session('status')"
    />

    {{-- Reset Form --}}
    <form wire:submit="sendPasswordResetLink" class="space-y-5">

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
                autocomplete="email"
                placeholder="Masukkan email Anda"
            />

            <x-input-error
                :messages="$errors->get('email')"
                class="mt-2"
            />
        </div>

        {{-- Submit --}}
        <x-primary-button class="w-full">
            Kirim Tautan Reset Password
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
