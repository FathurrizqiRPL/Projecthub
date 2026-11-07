@extends('layouts.guest')

@section('content')
    <div class="mb-7">
        <p class="text-sm font-semibold text-red-600">Verifikasi akun</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
            Verifikasi email Anda
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Sebelum melanjutkan, silakan verifikasi alamat email akun Anda
            melalui tautan yang telah dikirimkan.
        </p>
        </div>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">
                Kirim Ulang Email Verifikasi
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full rounded-xl border border-slate-300 bg-[#fffdfa] px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            >
                Keluar
            </button>
        </form>
    </div>
@endsection
