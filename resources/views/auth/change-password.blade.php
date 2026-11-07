@extends('layouts.guest')

@section('content')
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">Keamanan akun</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Ganti kata sandi sementara
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Anda masih menggunakan kata sandi sementara.
            Buat kata sandi baru sebelum melanjutkan ke ProjectHub.
        </p>
        </div>

    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" :value="__('Kata sandi baru')" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-2 block w-full"
                required
                autofocus
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

        <x-primary-button class="w-full">Simpan kata sandi baru</x-primary-button>
    </form>
@endsection
