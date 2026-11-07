@extends('layouts.guest')

@section('content')
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">Buat kata sandi baru</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Atur ulang kata sandi
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Masukkan alamat email akun Anda dan buat kata sandi baru untuk melanjutkan.
        </p>
        </div>

    <form
        method="POST"
        action="{{ route('password.reset.store', $token) }}"
        class="space-y-5"
    >
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-2 block w-full"
                :value="old('email', $email)"
                required
                autofocus
                autocomplete="username"
                placeholder="nama@namaperusahaan.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Kata sandi baru')" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-2 block w-full"
                required
                autocomplete="new-password"
                placeholder="Masukkan kata sandi baru"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi kata sandi Baru')" />
            <x-text-input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="mt-2 block w-full"
                required
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Atur ulang kata sandi</x-primary-button>

        <a
            href="{{ route('login') }}"
            class="block text-center text-sm font-semibold text-slate-600 transition hover:text-red-600"
        >
            Kembali ke halaman masuk
        </a>
    </form>
@endsection
