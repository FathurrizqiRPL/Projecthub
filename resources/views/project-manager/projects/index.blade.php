@extends('layouts.project-manager')

@section('title', 'Projects')
@section('header', 'Projects')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-bold text-slate-900">
                Daftar Project
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola seluruh project yang Anda buat.
            </p>
        </div>

        <a
            href="{{ route('project-manager.projects.create') }}"
            wire:navigate
            class="inline-flex items-center justify-center rounded-xl
                   bg-red-600 px-4 py-2.5 text-sm font-semibold text-white
                   transition hover:bg-red-700"
        >
            + Buat Project
        </a>

    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div
            class="rounded-xl border border-emerald-200
                   bg-emerald-50 px-4 py-3
                   text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>
    @endif

    {{-- Project List --}}
    <div
        class="rounded-2xl border border-slate-200
               bg-[#fffdfa] shadow-sm"
    >

        @forelse ($projects as $project)

            <div
                class="flex flex-col gap-4 border-b
                       border-slate-100 p-5 last:border-0
                       sm:flex-row sm:items-center
                       sm:justify-between"
            >

                {{-- Project Information --}}
                <div class="min-w-0">

                    <div class="flex flex-wrap items-center gap-3">

                        <a
                            href="{{ route(
                                'project-manager.projects.show',
                                $project
                            ) }}"
                            wire:navigate
                            class="font-semibold text-slate-900
                                   transition hover:text-red-600"
                        >
                            {{ $project->name }}
                        </a>

                        {{-- Status Badge --}}
                        <span
                            @class([
                                'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold',

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
                            <span
                                @class([
                                    'mr-1.5 h-1.5 w-1.5 rounded-full',

                                    'bg-slate-400'
                                        => $project->status === 'draft',

                                    'bg-blue-500'
                                        => $project->status === 'active',

                                    'bg-emerald-500'
                                        => $project->status === 'completed',

                                    'bg-red-500'
                                        => $project->status === 'cancelled',
                                ])
                            ></span>

                            {{ match ($project->status) {
                                'draft' => 'Draft',
                                'active' => 'Aktif',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                default => ucfirst($project->status),
                            } }}
                        </span>

                    </div>

                    {{-- Description --}}
                    <p class="mt-2 text-sm text-slate-500">
                        {{ Str::limit(
                            $project->description ?: 'Tidak ada deskripsi.',
                            100
                        ) }}
                    </p>

                    {{-- Dates --}}
                    <div
                        class="mt-3 flex flex-wrap gap-4
                               text-xs text-slate-400"
                    >

                        <span>
                            Mulai:
                            {{ $project->start_date?->format('d M Y') ?? '-' }}
                        </span>

                        <span>
                            Deadline:
                            {{ $project->deadline?->format('d M Y') ?? '-' }}
                        </span>

                    </div>

                </div>

                {{-- Actions --}}
                <div class="flex shrink-0 gap-2">

                    <a
                        href="{{ route(
                            'project-manager.projects.show',
                            $project
                        ) }}"
                        wire:navigate
                        class="rounded-lg border border-slate-200
                               px-3 py-2 text-xs font-semibold
                               text-slate-600 transition
                               hover:bg-slate-50"
                    >
                        Detail
                    </a>

                    <a
                        href="{{ route(
                            'project-manager.projects.edit',
                            $project
                        ) }}"
                        wire:navigate
                        class="rounded-lg border border-slate-200
                               px-3 py-2 text-xs font-semibold
                               text-slate-600 transition
                               hover:bg-slate-50"
                    >
                        Edit
                    </a>

                </div>

            </div>

        @empty

            {{-- Empty State --}}
            <div class="p-10 text-center">

                <p class="font-semibold text-slate-700">
                    Belum ada project
                </p>

                <p class="mt-1 text-sm text-slate-400">
                    Buat project pertama Anda untuk memulai.
                </p>

            </div>

        @endforelse

    </div>

    {{-- Pagination --}}
    {{ $projects->links() }}

</div>

@endsection
