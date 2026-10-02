<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(
                default: route('dashboard', absolute: false),
                navigate: true
            );

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash(
            'status',
            'Tautan verifikasi baru telah dikirim ke email Anda.'
        );
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    {{-- Heading --}}
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">
            Verifikasi akun
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Verifikasi email Anda
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Sebelum melanjutkan, silakan verifikasi alamat email akun Anda
            melalui tautan yang telah dikirimkan.
        </p>
    </div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="px-4 py-3 mb-5 text-sm border rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- Actions --}}
    <div class="space-y-4">
        <x-primary-button
            wire:click="sendVerification"
            class="w-full"
        >
            Kirim Ulang Email Verifikasi
        </x-primary-button>

        <button
            type="button"
            wire:click="logout"
            class="w-full rounded-xl border border-slate-300 bg-[#fffdfa] px-5 py-2.5
                   text-sm font-semibold text-slate-700
                   transition hover:border-red-300 hover:bg-red-50 hover:text-red-600
                   focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        >
            Keluar
        </button>
    </div>
</div>
