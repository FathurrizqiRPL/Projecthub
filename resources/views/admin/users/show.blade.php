@extends('layouts.admin')

@section('title', 'Detail Pengguna')
@section('header', 'Detail Pengguna')

@section('content')
<div class="mx-auto max-w-5xl">

    @if (session('success'))
        <div
            class="mb-6 rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm font-medium text-emerald-700"
        >
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="mb-6 rounded-xl border border-red-200
                   bg-red-50 px-4 py-3
                   text-sm font-medium text-red-700"
        >
            {{ session('error') }}
        </div>
    @endif

    @if (session('credentials'))
        @php
            $credentials = session('credentials');

            $credentialText =
                "Akun ProjectHub\n\n".
                "Nama: {$credentials['name']}\n".
                "Username: {$credentials['username']}\n".
                "Password sementara: {$credentials['password']}\n\n".
                "Silakan login dan ganti password setelah masuk.";
        @endphp

        <div
            x-data="{
                copied: false,

                async copyCredentials() {
                    try {
                        await navigator.clipboard.writeText(
                            @js($credentialText)
                        );

                        this.copied = true;

                        setTimeout(() => {
                            this.copied = false;
                        }, 2000);
                    } catch (error) {
                        this.copied = false;
                    }
                }
            }"
            class="mb-6 overflow-hidden rounded-2xl
                   border border-blue-200 bg-blue-50"
        >
            <div
                class="border-b border-blue-200
                       px-6 py-4"
            >
                <h2 class="font-bold text-blue-900">
                    Kredensial Akun
                </h2>

                <p class="mt-1 text-sm text-blue-700">
                    Salin kredensial ini dan berikan kepada pengguna.
                </p>
            </div>

            <div class="p-6">

                <div
                    class="grid gap-5 rounded-xl
                           border border-blue-200
                           bg-white p-5 sm:grid-cols-2"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wider text-slate-400"
                        >
                            Username
                        </p>

                        <p
                            class="mt-1 break-all font-mono
                                   text-sm font-semibold
                                   text-slate-900"
                        >
                            {{ $credentials['username'] }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wider text-slate-400"
                        >
                            Password Sementara
                        </p>

                        <p
                            class="mt-1 break-all font-mono
                                   text-sm font-semibold
                                   text-slate-900"
                        >
                            {{ $credentials['password'] }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-5 flex flex-col gap-3
                           sm:flex-row sm:items-center
                           sm:justify-between"
                >
                    <p class="text-xs leading-5 text-blue-700">
                        Password sementara ini hanya ditampilkan
                        pada proses ini dan wajib diganti oleh
                        pengguna setelah login.
                    </p>

                    <button
                        type="button"
                        x-on:click="copyCredentials()"
                        class="shrink-0 rounded-xl bg-blue-600
                               px-4 py-2.5 text-sm font-semibold
                               text-white transition
                               hover:bg-blue-700"
                    >
                        <span x-show="!copied">
                            Salin Kredensial
                        </span>

                        <span
                            x-show="copied"
                            x-cloak
                        >
                            Tersalin ✓
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div
        class="mb-6 flex items-center
               justify-between gap-4"
    >
        <a
            href="{{ route('admin.users.index') }}"
            wire:navigate
            class="text-sm font-medium text-slate-500
                   transition hover:text-red-600"
        >
            ← Kembali ke User Management
        </a>

        <a
            href="{{ route('admin.users.edit', $user) }}"
            wire:navigate
            class="rounded-xl bg-red-600 px-4 py-2.5
                   text-sm font-semibold text-white
                   transition hover:bg-red-700"
        >
            Edit Pengguna
        </a>
    </div>

    <div
        class="overflow-hidden rounded-2xl
               border border-slate-200
               bg-[#fffdfa] shadow-sm"
    >
        <div
            class="border-b border-slate-200
                   p-6 lg:p-8"
        >
            <div class="flex items-center gap-4">

                <div
                    class="flex h-16 w-16 shrink-0
                           items-center justify-center
                           rounded-full bg-red-100
                           text-xl font-bold text-red-600"
                >
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div class="min-w-0">
                    <h2
                        class="truncate text-xl
                               font-bold text-slate-900"
                    >
                        {{ $user->name }}
                    </h2>

                    <p
                        class="mt-1 text-sm
                               font-medium text-slate-500"
                    >
                        {{ '@'.$user->username }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">

                        <span
                            class="rounded-full bg-slate-100
                                   px-3 py-1 text-xs
                                   font-semibold text-slate-600"
                        >
                            {{
                                $user->role === 'admin'
                                    ? 'Administrator'
                                    : (
                                        $user->role === 'project_manager'
                                            ? 'Project Manager'
                                            : 'Karyawan'
                                    )
                            }}
                        </span>

                        <span
                            class="rounded-full px-3 py-1
                                   text-xs font-semibold
                                {{
                                    $user->status === 'active'
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-slate-100 text-slate-500'
                                }}"
                        >
                            {{
                                $user->status === 'active'
                                    ? 'Aktif'
                                    : 'Nonaktif'
                            }}
                        </span>

                    </div>
                </div>
            </div>
        </div>

        <div
            class="grid gap-x-8 gap-y-6 p-6
                   md:grid-cols-2 lg:p-8"
        >
            <div>
                <p
                    class="text-xs font-semibold uppercase
                           tracking-wider text-slate-400"
                >
                    Username
                </p>

                <p
                    class="mt-1 text-sm font-medium
                           text-slate-800"
                >
                    {{ $user->username }}
                </p>
            </div>

            <div>
                <p
                    class="text-xs font-semibold uppercase
                           tracking-wider text-slate-400"
                >
                    Email
                </p>

                <p
                    class="mt-1 text-sm font-medium
                           text-slate-800"
                >
                    {{ $user->email ?: 'Belum diisi' }}
                </p>
            </div>

            <div>
                <p
                    class="text-xs font-semibold uppercase
                           tracking-wider text-slate-400"
                >
                    Departemen
                </p>

                <p
                    class="mt-1 text-sm font-medium
                           text-slate-800"
                >
                    {{ $user->department ?: '-' }}
                </p>
            </div>

            <div>
                <p
                    class="text-xs font-semibold uppercase
                           tracking-wider text-slate-400"
                >
                    Nomor Telepon
                </p>

                <p
                    class="mt-1 text-sm font-medium
                           text-slate-800"
                >
                    {{ $user->phone ?: '-' }}
                </p>
            </div>

            <div>
                <p
                    class="text-xs font-semibold uppercase
                           tracking-wider text-slate-400"
                >
                    Keahlian
                </p>

                <p
                    class="mt-1 text-sm font-medium
                           text-slate-800"
                >
                    {{ $user->skills ?: '-' }}
                </p>
            </div>

            <div>
                <p
                    class="text-xs font-semibold uppercase
                           tracking-wider text-slate-400"
                >
                    Tanggal Bergabung
                </p>

                <p
                    class="mt-1 text-sm font-medium
                           text-slate-800"
                >
                    {{
                        $user->joined_at
                            ? $user->joined_at
                                ->locale('id')
                                ->translatedFormat('d F Y')
                            : '-'
                    }}
                </p>
            </div>
        </div>

        @if ($user->role !== 'admin')

            {{-- Account Security --}}
            <div
                class="border-t border-slate-200
                       p-6 lg:p-8"
            >
                <div
                    class="flex flex-col justify-between gap-4
                           sm:flex-row sm:items-center"
                >
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            Keamanan Akun
                        </p>

                        <p class="mt-1 max-w-xl text-sm text-slate-500">
                            Buat password sementara baru jika
                            pengguna lupa password atau kredensial
                            awal belum sempat diberikan.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{
                            route(
                                'admin.users.reset-password',
                                $user
                            )
                        }}"
                        onsubmit="return confirm(
                            'Buat password sementara baru untuk pengguna ini? Password sebelumnya tidak akan dapat digunakan lagi.'
                        )"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="shrink-0 rounded-xl
                                   border border-amber-200
                                   bg-amber-50 px-4 py-2.5
                                   text-sm font-semibold
                                   text-amber-700 transition
                                   hover:bg-amber-100"
                        >
                            Reset Password
                        </button>
                    </form>
                </div>
            </div>

            {{-- Account Status --}}
            <div
                class="border-t border-slate-200
                       p-6 lg:p-8"
            >
                <div
                    class="flex flex-col justify-between gap-4
                           sm:flex-row sm:items-center"
                >
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            Status Akun
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{
                                $user->status === 'active'
                                    ? 'Nonaktifkan akun agar pengguna tidak dapat masuk.'
                                    : 'Aktifkan kembali akun agar pengguna dapat masuk.'
                            }}
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{
                            route(
                                'admin.users.status',
                                $user
                            )
                        }}"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="rounded-xl px-4 py-2.5
                                   text-sm font-semibold transition
                                {{
                                    $user->status === 'active'
                                        ? 'border border-red-200 bg-red-50 text-red-600 hover:bg-red-100'
                                        : 'bg-emerald-600 text-white hover:bg-emerald-700'
                                }}"
                        >
                            {{
                                $user->status === 'active'
                                    ? 'Nonaktifkan Akun'
                                    : 'Aktifkan Akun'
                            }}
                        </button>
                    </form>
                </div>
            </div>

        @endif
    </div>
</div>
@endsection
