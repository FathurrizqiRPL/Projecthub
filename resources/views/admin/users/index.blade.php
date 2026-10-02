@extends('layouts.admin')

@section('title', 'User Management')

@section('header', 'User Management')

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
                User Management
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Kelola akun Project Manager dan karyawan yang menggunakan ProjectHub.
            </p>
        </div>

        <a
            href="{{ route('admin.users.create') }}"
            wire:navigate
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

    <livewire:admin.users.user-table />

@endsection
