<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';
    public string $password_confirmation = '';

    public function changePassword(): void
    {
        $validated = $this->validate([
            'password' => [
                'required',
                'string',
                Password::defaults(),
                'confirmed',
            ],
        ]);

        Auth::user()->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        $this->reset('password', 'password_confirmation');

        $this->redirectRoute('dashboard', navigate: true);
    }
}; ?>

<div>
    {{-- Heading --}}
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">
            Keamanan akun
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Ganti kata sandi sementara
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Anda masih menggunakan kata sandi sementara.
            Buat kata sandi baru sebelum melanjutkan ke ProjectHub.
        </p>
    </div>

    {{-- Formulir ganti kata sandi --}}
    <form wire:submit="changePassword" class="space-y-5">

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
                autofocus
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
            Simpan kata sandi baru
        </x-primary-button>

    </form>
</div>
