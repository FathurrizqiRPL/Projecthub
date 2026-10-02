@extends('layouts.project-manager')

@section('title', 'Dashboard Project Manager')
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

        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Kelola proyek, anggota tim, tugas, dan pantau
            perkembangan pekerjaan melalui ProjectHub.
        </p>
    </div>


    {{-- Statistics --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Total Projects --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                Total Proyek
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $totalProjects }}
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Seluruh proyek yang dikelola
            </p>
        </div>


        {{-- Active Projects --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                Proyek Aktif
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $activeProjects }}
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Proyek yang sedang berjalan
            </p>
        </div>


        {{-- Ongoing Tasks --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                Tugas Berjalan
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-300">
                —
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Modul tugas belum tersedia
            </p>
        </div>


        {{-- Completed Tasks --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-5 shadow-sm"
        >
            <p class="text-sm font-medium text-slate-500">
                Tugas Selesai
            </p>

            <p class="mt-3 text-3xl font-bold text-slate-300">
                —
            </p>

            <p class="mt-2 text-xs text-slate-400">
                Modul tugas belum tersedia
            </p>
        </div>

    </div>


    {{-- Bottom --}}
    <div class="mt-8 grid gap-5 lg:grid-cols-3">

        {{-- Recent Projects --}}
        <div
            class="rounded-2xl border border-slate-200
                   bg-[#fffdfa] p-6 shadow-sm
                   lg:col-span-2"
        >
            <div class="flex items-start justify-between gap-4">

                <div>
                    <h3 class="font-semibold text-slate-900">
                        Proyek Terbaru
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Project yang terakhir Anda buat.
                    </p>
                </div>

                @if ($recentProjects->isNotEmpty())
                    <a
                        href="{{ route('project-manager.projects.index') }}"
                        wire:navigate
                        class="text-xs font-semibold text-red-600
                               transition hover:text-red-700"
                    >
                        Lihat Semua
                    </a>
                @endif

            </div>


            @if ($recentProjects->isEmpty())

                {{-- Empty --}}
                <div
                    class="mt-6 flex min-h-48 flex-col
                           items-center justify-center rounded-2xl
                           border border-dashed border-slate-200
                           px-6 text-center"
                >
                    <div
                        class="flex h-12 w-12 items-center
                               justify-center rounded-2xl bg-red-50
                               text-red-600"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 7h5l2 2h11v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"
                            />
                        </svg>
                    </div>

                    <p class="mt-4 text-sm font-semibold text-slate-700">
                        Belum ada proyek
                    </p>

                    <p class="mt-1 max-w-sm text-xs leading-5 text-slate-400">
                        Project yang Anda buat akan muncul di sini.
                    </p>
                </div>

            @else

                {{-- Projects --}}
                <div class="mt-6 space-y-3">

                    @foreach ($recentProjects as $project)

                        <a
                            href="{{ route(
                                'project-manager.projects.show',
                                $project
                            ) }}"
                            wire:navigate
                            class="flex items-center justify-between gap-4
                                   rounded-xl border border-slate-100
                                   bg-slate-50 p-4 transition
                                   hover:border-red-100 hover:bg-red-50/50"
                        >

                            <div class="min-w-0">

                                <div class="flex flex-wrap items-center gap-2">

                                    <p
                                        class="truncate text-sm font-semibold
                                               text-slate-800"
                                    >
                                        {{ $project->name }}
                                    </p>

                                    {{-- Status --}}
                                    <span
                                        @class([
                                            'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold',

                                            'border-slate-200 bg-slate-100 text-slate-600'
                                                => $project->status === 'draft',

                                            'border-blue-200 bg-blue-50 text-blue-700'
                                                => $project->status === 'active',

                                            'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                => $project->status === 'completed',

                                            'border-red-200 bg-red-50 text-red-700'
                                                => $project->status === 'cancelled',
                                        ])
                                    >
                                        {{ match ($project->status) {
                                            'draft' => 'Draft',
                                            'active' => 'Aktif',
                                            'completed' => 'Selesai',
                                            'cancelled' => 'Dibatalkan',
                                            default => ucfirst($project->status),
                                        } }}
                                    </span>

                                </div>

                                <p class="mt-1 text-xs text-slate-400">
                                    Deadline:
                                    {{ $project->deadline?->format('d M Y') ?? '-' }}
                                </p>

                            </div>


                            <svg
                                class="h-4 w-4 shrink-0 text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>

                        </a>

                    @endforeach

                </div>

            @endif

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
                Shortcut pengelolaan proyek.
            </p>


            <div class="mt-6 space-y-3">

                <a
                    href="{{ route('project-manager.projects.create') }}"
                    wire:navigate
                    class="flex w-full items-center justify-center
                           rounded-xl bg-red-600 px-4 py-2.5
                           text-sm font-semibold text-white
                           transition hover:bg-red-700"
                >
                    + Buat Proyek
                </a>

                <a
                    href="{{ route('project-manager.projects.index') }}"
                    wire:navigate
                    class="flex w-full items-center justify-center
                           rounded-xl border border-slate-200
                           px-4 py-2.5 text-sm font-semibold
                           text-slate-600 transition
                           hover:bg-slate-50"
                >
                    Kelola Proyek
                </a>

            </div>

        </div>

    </div>

@endsection
