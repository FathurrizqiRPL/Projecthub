<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->resetErrorBag();

        try {
            $this->validate();
            $this->form->authenticate();
        } catch (ValidationException $e) {
            $this->dispatch('login-failed');

            throw $e;
        }

        Session::regenerate();

        $this->redirectIntended(
            default: route('dashboard', absolute: false),
            navigate: true
        );
    }
}; ?>

<div
    x-data="{ submitting: false }"
    x-on:login-failed.window="submitting = false"
>
    {{-- Heading --}}
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">
            Selamat datang kembali
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Masuk ke ProjectHub
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Gunakan username dan password akun Anda untuk masuk.
        </p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status
        class="mb-5 rounded-xl border border-emerald-200
               bg-emerald-50 px-4 py-3"
        :status="session('status')"
    />

    {{-- Login Form --}}
    <form
        wire:submit="login"
        x-on:submit="submitting = true"
        class="space-y-5"
    >
        {{-- Username --}}
        <div>
            <x-input-label
                for="username"
                value="Username"
            />

            <x-text-input
                wire:model="form.username"
                id="username"
                class="mt-2 block w-full"
                type="text"
                name="username"
                required
                autofocus
                autocomplete="username"
                placeholder="Masukkan username Anda"
                x-bind:disabled="submitting"
            />

            <x-input-error
                :messages="$errors->get('form.username')"
                class="mt-2"
            />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label
                for="password"
                value="Password"
            />

            <x-text-input
                wire:model="form.password"
                id="password"
                class="mt-2 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan password"
                x-bind:disabled="submitting"
            />

            <x-input-error
                :messages="$errors->get('form.password')"
                class="mt-2"
            />
        </div>

        {{-- Remember Me --}}
        <label
            for="remember"
            class="inline-flex cursor-pointer items-center gap-2.5"
        >
            <input
                wire:model="form.remember"
                id="remember"
                type="checkbox"
                x-bind:disabled="submitting"
                class="h-4 w-4 rounded border-slate-300
                       bg-[#fffdfa] text-red-600 shadow-sm
                       focus:ring-2 focus:ring-red-500
                       focus:ring-offset-2
                       checked:border-red-600
                       checked:bg-red-600
                       disabled:cursor-not-allowed
                       disabled:opacity-60"
            >

            <span class="text-sm text-slate-600">
                Ingat saya
            </span>
        </label>

        {{-- Submit --}}
        <button
            type="submit"
            x-bind:disabled="submitting"
            class="flex w-full items-center justify-center gap-2
                   rounded-xl bg-red-600 px-4 py-3
                   text-sm font-semibold text-white transition
                   hover:bg-red-700
                   focus:outline-none focus:ring-2
                   focus:ring-red-500 focus:ring-offset-2
                   disabled:cursor-not-allowed
                   disabled:bg-red-300
                   disabled:text-white/80"
        >
            <span x-show="!submitting">
                Masuk
            </span>

            <span
                x-show="submitting"
                x-cloak
                class="flex items-center gap-2"
            >
                <svg
                    class="h-4 w-4 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />

                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                </svg>

                Memproses...
            </span>
        </button>
    </form>
</div>
