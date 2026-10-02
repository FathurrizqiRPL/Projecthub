@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('header', 'Dashboard')

@section('content')

    {{-- Welcome --}}
    <div
        class="mb-8 rounded-3xl border border-red-100
               bg-gradient-to-r from-red-50 to-[#fffdfa]
               p-6 lg:p-8"
    >
        <p class="text-sm font-semibold text-red-600">
            Selamat datang kembali
        </p>

        <h2 class="mt-2 text-2xl font-bold text-slate-900">
            Halo, {{ auth()->user()->name }} 👋
        </h2>

        <p class="max-w-2xl mt-2 text-sm leading-6 text-slate-500">
            Kelola akun pengguna dan hak akses ProjectHub
            melalui dashboard Administrator.
        </p>
    </div>


    {{-- Statistics --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Total User --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                Total User
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalUsers }}
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Seluruh pengguna sistem
            </p>
        </div>


        {{-- Project Manager --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                Project Manager
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalProjectManagers }}
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Akun Project Manager
            </p>
        </div>


        {{-- Employee --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                Karyawan
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalEmployees }}
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Akun karyawan
            </p>
        </div>


        {{-- Active User --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                User Aktif
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalActiveUsers }}
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Akun dengan status aktif
            </p>
        </div>

    </div>


    {{-- Bottom --}}
    <div class="grid gap-5 mt-8 lg:grid-cols-3">

        {{-- Recent Users --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-6 shadow-sm
                   lg:col-span-2"
        >
            {{-- Header --}}
            <div class="flex items-start justify-between gap-4">

                <div>
                    <h3 class="font-semibold text-slate-900">
                        User Terbaru
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Akun pengguna yang baru ditambahkan.
                    </p>
                </div>

                <a
                    href="{{ route('admin.users.index') }}"
                    wire:navigate
                    class="text-sm font-semibold text-red-600
                           transition shrink-0 hover:text-red-700"
                >
                    Lihat Semua
                </a>

            </div>

            {{-- Content --}}
            <div class="mt-6 space-y-4">

                @forelse ($recentUsers as $user)

                    <div
                        class="flex items-center justify-between gap-4
                               rounded-xl border border-slate-100
                               px-4 py-3"
                    >

                        {{-- User Info --}}
                        <div class="flex min-w-0 items-center gap-3">

                            <div
                                class="flex h-10 w-10 shrink-0
                                       items-center justify-center
                                       rounded-full bg-red-100
                                       font-bold text-red-600"
                            >
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>

                            <div class="min-w-0">

                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ $user->name }}
                                </p>

                                <p class="truncate text-xs text-slate-400">
                                    {{ $user->email }}
                                </p>

                            </div>

                        </div>

                        {{-- Role + Time --}}
                        <div class="shrink-0 text-right">

                            <p class="text-sm font-medium text-slate-600">
                                @if ($user->role === 'admin')
                                    Administrator
                                @elseif ($user->role === 'project_manager')
                                    Project Manager
                                @else
                                    Karyawan
                                @endif
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $user->created_at->diffForHumans() }}
                            </p>

                        </div>

                    </div>

                @empty

                    <p class="text-sm text-slate-400">
                        Belum ada user terbaru.
                    </p>

                @endforelse

            </div>

        </div>


        {{-- Quick Actions --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-6 shadow-sm"
        >
            <h3 class="font-semibold text-slate-900">
                Aksi Cepat
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Kelola pengguna ProjectHub.
            </p>

            <div class="mt-6 space-y-3">

                <a
                    href="{{ route('admin.users.create') }}"
                    wire:navigate
                    class="block w-full rounded-xl
                           bg-red-600 px-4 py-2.5
                           text-center text-sm font-semibold
                           text-white transition hover:bg-red-700"
                >
                    + Tambah User
                </a>

                <a
                    href="{{ route('admin.users.index') }}"
                    wire:navigate
                    class="block w-full rounded-xl
                           border border-slate-300
                           px-4 py-2.5 text-center
                           text-sm font-semibold text-slate-700
                           transition hover:bg-slate-50"
                >
                    Kelola Semua User
                </a>

            </div>

        </div>

    </div>

@endsection
