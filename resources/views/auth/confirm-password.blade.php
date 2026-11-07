@extends('layouts.guest')

@section('content')
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">Konfirmasi keamanan</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Konfirmasi kata sandi
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Untuk melanjutkan ke tindakan yang memerlukan keamanan tambahan,
            silakan masukkan Kata sandi akun Anda.
        </p>
        </div>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Kata sandi')" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-2 block w-full"
                required
                autofocus
                autocomplete="current-password"
                placeholder="Masukkan Kata sandi Anda"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Konfirmasi kata sandi</x-primary-button>
    </form>
@endsection
