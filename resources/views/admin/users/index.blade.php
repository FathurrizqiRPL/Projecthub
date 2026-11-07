@extends('layouts.admin')

@section('title', 'Kelola Pengguna')

@section('header', 'Kelola Pengguna')

@section('content')

    @if (session('success'))
        <div
            class="mb-6 rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm font-medium text-emerald-700"
        >
            {{ session('success') }}
        </div>
    @endif

    <div
        class="mb-8 flex flex-col gap-4
               sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <p class="text-sm font-semibold text-red-600">
                Administrator
            </p>

            <h2 class="mt-1 text-2xl font-bold text-slate-900">
                Kelola Pengguna
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Kelola akun Manajer proyek dan karyawan yang menggunakan ProjectHub.
            </p>
        </div>

        <a
            href="{{ route('admin.users.create') }}"
            class="inline-flex items-center justify-center rounded-xl
                   bg-red-600 px-5 py-2.5
                   text-sm font-semibold text-white
                   transition hover:bg-red-700
                   focus:outline-none focus:ring-2
                   focus:ring-red-500 focus:ring-offset-2"
        >
            + Tambah User
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 grid gap-3 lg:grid-cols-[1fr_200px_180px_auto]">
        <input
            type="text"
            name="search"
            value="{{ $filters['search'] }}"
            placeholder="Cari nama, Nama pengguna, atau email..."
            class="block w-full rounded-xl border-slate-300 bg-[#fffdfa] text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
        >

        <select name="role" class="rounded-xl border-slate-300 bg-[#fffdfa] text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            <option value="">Semua Role</option>
            <option value="admin" @selected($filters['role'] === 'admin')>Administrator</option>
            <option value="project_manager" @selected($filters['role'] === 'project_manager')>Manajer proyek</option>
            <option value="employee" @selected($filters['role'] === 'employee')>Karyawan</option>
        </select>

        <select name="status" class="rounded-xl border-slate-300 bg-[#fffdfa] text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            <option value="">Semua Status</option>
            <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
            <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
        </select>

        <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
            Cari
        </button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-[#fffdfa] shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Pengguna</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Nama pengguna</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Role</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-600">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-slate-400">{{ $user->email ?: 'email belum diisi' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-600">{{ $user->username }}</td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-600">
                                    {{ $user->role === 'admin' ? 'Administrator' : ($user->role === 'project_manager' ? 'Manajer proyek' : 'Karyawan') }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-semibold text-red-600 transition hover:text-red-700">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm font-medium text-slate-500">Pengguna tidak ditemukan.</p>
                                <p class="mt-1 text-xs text-slate-400">Coba edit kata pencarian atau saring.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
