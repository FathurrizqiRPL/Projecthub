@extends('layouts.guest')

@section('content')
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">Pemulihan akun</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Lupa Kata sandi?
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk membuat kata sandi baru.
        </p>
        </div>

    <x-auth-session-status
        class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-2 block w-full"
                :value="old('email')"
                required
                autofocus
                autocomplete="email"
                placeholder="Masukkan email Anda"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            Kirim tautan untuk mengatur ulang kata sandi
        </x-primary-button>

        <a
            href="{{ route('login') }}"
            class="block text-center text-sm font-semibold text-slate-600 transition hover:text-red-600"
        >
            Kembali ke halaman masuk
        </a>
    </form>
@endsection
