@extends('layouts.employee')

@section('title', 'Proyek saya')
@section('header', 'Proyek saya')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl border border-red-100 bg-gradient-to-r from-red-50 to-[#fffdfa] p-6 lg:p-8">
        <p class="text-sm font-semibold text-red-600">Ruang kerja tim</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">Proyek yang melibatkan Anda</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Lihat tahapan dan semua tugas dalam proyek. Anda dapat mengerjakan tugas yang ditugaskan kepada Anda.
        </p>
    </div>

    <form method="GET" action="{{ route('employee.projects.index') }}" class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-4 shadow-sm">
        <label for="project-search" class="sr-only">Cari proyek</label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <input
                id="project-search"
                name="search"
                type="search"
                value="{{ $search }}"
                maxlength="255"
                placeholder="Cari nama atau deskripsi proyek..."
                class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
            >
            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                    Cari proyek
                </button>
                @if ($search !== '')
                    <a href="{{ route('employee.projects.index') }}" wire:navigate
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Hapus pencarian
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if ($projects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-[#fffdfa] p-10 text-center">
            @if ($search !== '')
                <p class="text-sm font-semibold text-slate-700">Proyek tidak ditemukan</p>
                <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain atau hapus pencarian.</p>
            @else
                <p class="text-sm font-semibold text-slate-700">Belum ada proyek</p>
                <p class="mt-1 text-sm text-slate-500">Proyek akan muncul setelah Anda mendapat tugas di dalamnya.</p>
            @endif
        </div>
    @else
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($projects as $project)
                @php
                    $projectStatusLabel = match ($project->status) {
                        'draft' => 'Draf',
                        'active' => 'Berjalan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($project->status),
                    };
                @endphp

                <article class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-bold text-slate-900">{{ $project->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Tahap saat ini:
                                <span class="font-semibold text-slate-700">{{ $project->currentStage?->name ?? 'Belum dimulai' }}</span>
                            </p>
                        </div>
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-blue-50 text-blue-700' => $project->status === 'active',
                            'bg-slate-100 text-slate-600' => $project->status === 'draft',
                            'bg-emerald-50 text-emerald-700' => $project->status === 'completed',
                            'bg-red-50 text-red-700' => $project->status === 'cancelled',
                        ])>
                            {{ $projectStatusLabel }}
                        </span>
                    </div>

                    @if ($project->description)
                        <p class="mt-4 text-sm leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($project->description, 180) }}</p>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-500">
                        <span>{{ $project->my_tasks_count }} tugas untuk Anda</span>
                        <span>{{ $project->tasks_count }} tugas dalam proyek</span>
                        @if ($project->deadline)
                            <span>Tenggat {{ $project->deadline->locale('id')->translatedFormat('d F Y') }}</span>
                        @endif
                    </div>

                    <a href="{{ route('employee.projects.show', $project) }}" wire:navigate
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Lihat proyek dan tugas
                        <span aria-hidden="true">→</span>
                    </a>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
