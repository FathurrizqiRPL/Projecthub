@extends('layouts.guest')

@section('content')
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">
            Selamat datang kembali
        </p>

        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Masuk ke ProjectHub
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Gunakan Nama pengguna dan Kata sandi akun Anda untuk masuk.
        </p>
        </div>

    <x-auth-session-status
        class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="username" value="Nama pengguna" />
            <x-text-input
                id="username"
                name="username"
                type="text"
                class="mt-2 block w-full"
                :value="old('username')"
                required
                autofocus
                autocomplete="username"
                placeholder="Masukkan Nama pengguna Anda"
            />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-2 block w-full"
                required
                autocomplete="current-password"
                placeholder="Masukkan Kata sandi"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember" class="inline-flex cursor-pointer items-center gap-2.5">
            <input
                id="remember"
                name="remember"
                type="checkbox"
                value="1"
                @checked(old('remember'))
                class="h-4 w-4 rounded border-slate-300 bg-[#fffdfa] text-red-600 shadow-sm focus:ring-2 focus:ring-red-500 focus:ring-offset-2 checked:border-red-600 checked:bg-red-600"
            >
            <span class="text-sm text-slate-600">Ingat saya</span>
        </label>

        <button
            type="submit"
            class="flex w-full items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
        >
            Masuk
        </button>

        <a
            href="{{ route('password.request') }}"
            class="block text-center text-sm font-semibold text-slate-600 transition hover:text-red-600"
        >
            Lupa kata sandi?
        </a>
    </form>
@endsection
