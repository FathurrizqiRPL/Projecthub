@extends('layouts.project-manager')

@section('title', $project->name)
@section('header', 'Detail Project')

@section('content')
<div class="space-y-6">

    {{-- Back --}}
    <a href="{{ route('project-manager.projects.index') }}" wire:navigate
        class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke Projects
    </a>

    {{-- Success --}}
    @if (session('success'))
        <div class="px-4 py-3 text-sm border rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Project Detail --}}
    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-wrap items-center min-w-0 gap-3">
                <h2 class="text-2xl font-bold text-slate-900">
                    {{ $project->name }}
                </h2>

                <span @class([
                    'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold',
                    'border-slate-200 bg-slate-100 text-slate-600' => $project->status === 'draft',
                    'border-blue-200 bg-blue-50 text-blue-700' => $project->status === 'active',
                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $project->status === 'completed',
                    'border-red-200 bg-red-50 text-red-700' => $project->status === 'cancelled',
                ])>
                    <span @class([
                        'mr-1.5 h-1.5 w-1.5 rounded-full',
                        'bg-slate-400' => $project->status === 'draft',
                        'bg-blue-500' => $project->status === 'active',
                        'bg-emerald-500' => $project->status === 'completed',
                        'bg-red-500' => $project->status === 'cancelled',
                    ])></span>

                    {{ match ($project->status) {
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($project->status),
                    } }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('project-manager.projects.tasks.index', $project) }}" wire:navigate
                    class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                    Lihat Task
                </a>

                <a href="{{ route('project-manager.projects.edit', $project) }}" wire:navigate
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Edit Project
                </a>
            </div>
        </div>

        {{-- Description --}}
        <p class="w-full mt-6 text-sm leading-7 whitespace-pre-line text-slate-500">{{ $project->description ?: 'Tidak ada deskripsi.' }}</p>

        {{-- Dates --}}
        <div class="grid gap-4 mt-8 sm:grid-cols-2">
            <div class="p-4 rounded-xl bg-slate-50">
                <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                    Tanggal Mulai
                </p>
                <p class="mt-2 font-semibold text-slate-800">
                    {{ $project->start_date?->format('d M Y') ?? '-' }}
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50">
                <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                    Deadline
                </p>
                <p class="mt-2 font-semibold text-slate-800">
                    {{ $project->deadline?->format('d M Y') ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Members & Workflow --}}
    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Members --}}
        <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-slate-900">
                        Anggota Project
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Karyawan yang tergabung dalam project ini.
                    </p>
                </div>

                @if (!in_array($project->status, ['completed', 'cancelled']))
                    <a href="{{ route('project-manager.projects.members.edit', $project) }}" wire:navigate
                        class="px-3 py-2 text-xs font-semibold transition border rounded-lg shrink-0 border-slate-200 text-slate-600 hover:bg-slate-50">
                        Kelola Anggota
                    </a>
                @endif
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($project->members as $member)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center justify-center text-xs font-bold text-red-600 rounded-full h-9 w-9 shrink-0 bg-red-50">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate text-slate-700">
                                {{ $member->name }}
                            </p>
                            <p class="text-xs truncate text-slate-400">
                                {{ $member->department ?: $member->email }}
                            </p>
                        </div>

                        <span @class([
                            'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                            'border-emerald-200 bg-emerald-50 text-emerald-700' => $member->status === 'active',
                            'border-slate-200 bg-slate-100 text-slate-500' => $member->status !== 'active',
                        ])>
                            {{ $member->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                @empty
                    <div class="p-5 text-center border border-dashed rounded-xl border-slate-200">
                        <p class="text-sm font-semibold text-slate-600">
                            Belum ada anggota
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Tambahkan karyawan yang akan bekerja pada project ini.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Workflow --}}
        <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-slate-900">
                        Tahapan Project
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Posisi project dalam workflow saat ini.
                    </p>
                </div>

                @if ($project->status !== 'completed')
                    <a href="{{ route('project-manager.projects.workflow.edit', $project) }}" wire:navigate
                        class="px-3 py-2 text-xs font-semibold transition border rounded-lg shrink-0 border-slate-200 text-slate-600 hover:bg-slate-50">
                        Atur Workflow
                    </a>
                @endif
            </div>

            @if ($project->stages->isEmpty())
                <div class="p-5 mt-5 text-center border border-dashed rounded-xl border-slate-200">
                    <p class="text-sm font-semibold text-slate-600">
                        Belum ada workflow
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Tambahkan tahapan pengerjaan untuk project ini.
                    </p>
                </div>
            @else
                <div class="mt-6 space-y-3">
                    @foreach ($project->stages as $stage)
                        @php
                            $currentPosition = $project->currentStage?->position;
                            $isCurrent = $project->current_stage_id === $stage->id;
                            $isCompleted = $currentPosition && $stage->position < $currentPosition;
                            $isUpcoming = !$isCurrent && !$isCompleted;
                        @endphp

                        <div @class([
                            'flex items-center gap-4 rounded-xl border p-4 transition',
                            'border-emerald-200 bg-emerald-50' => $isCompleted,
                            'border-red-200 bg-red-50 ring-1 ring-red-100' => $isCurrent,
                            'border-slate-200 bg-slate-50' => $isUpcoming,
                        ])>
                            <div @class([
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-emerald-600 text-white' => $isCompleted,
                                'bg-red-600 text-white' => $isCurrent,
                                'bg-white text-slate-400' => $isUpcoming,
                            ])>
                                @if ($isCompleted)
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/>
                                    </svg>
                                @else
                                    {{ $stage->position }}
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p @class([
                                    'text-sm font-semibold',
                                    'text-emerald-700' => $isCompleted,
                                    'text-red-700' => $isCurrent,
                                    'text-slate-600' => $isUpcoming,
                                ])>
                                    {{ $stage->name }}
                                </p>

                                <p @class([
                                    'mt-0.5 text-xs',
                                    'text-emerald-600' => $isCompleted,
                                    'text-red-500' => $isCurrent,
                                    'text-slate-400' => $isUpcoming,
                                ])>
                                    @if ($isCompleted)
                                        @if ($stage->completed_at)
                                            Selesai {{ $stage->completed_at->locale('id')->translatedFormat('d F Y') }}
                                        @else
                                            Selesai
                                        @endif
                                    @elseif ($isCurrent)
                                        Sedang berjalan
                                    @else
                                        Belum dimulai
                                    @endif
                                </p>
                            </div>

                            @if ($isCurrent)
                                <span class="rounded-full bg-red-600 px-2.5 py-1 text-[10px] font-semibold text-white">
                                    Fase Saat Ini
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Workflow Controls --}}
                @if ($project->status === 'draft')
                    <div class="px-4 py-3 mt-5 border rounded-xl border-amber-200 bg-amber-50">
                        <p class="text-xs leading-5 text-amber-700">
                            Project masih berstatus Draft. Ubah status menjadi Aktif untuk memulai workflow.
                        </p>
                    </div>

                @elseif ($project->status === 'active' && !$project->currentStage)
                    <form method="POST"
                        action="{{ route('project-manager.projects.progress.start', $project) }}"
                        class="mt-5">
                        @csrf

                        <button type="submit"
                            class="w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                            Mulai Tahap {{ $project->stages->first()->name }}
                        </button>
                    </form>

                @elseif ($project->status === 'active' && $project->currentStage)
                    @php
                        $nextStage = $project->stages
                            ->where('position', '>', $project->currentStage->position)
                            ->sortBy('position')
                            ->first();
                    @endphp

                    @if ($nextStage)
                        <form method="POST"
                            action="{{ route('project-manager.projects.progress.next', $project) }}"
                            class="mt-5"
                            onsubmit="return confirm('Lanjutkan project ke tahap {{ $nextStage->name }}?')">
                            @csrf

                            <button type="submit"
                                class="w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                                Lanjut ke {{ $nextStage->name }}
                            </button>
                        </form>
                    @else
                        <div class="px-4 py-3 mt-5 border rounded-xl border-emerald-200 bg-emerald-50">
                            <p class="text-sm font-semibold text-emerald-700">
                                Tahap terakhir sedang berjalan.
                            </p>
                            <p class="mt-1 text-xs text-emerald-600">
                                Project saat ini berada pada tahap {{ $project->currentStage->name }}.
                            </p>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>

    {{-- Delete Project --}}
    <div class="p-6 border border-red-100 rounded-2xl bg-red-50">
        <h3 class="font-semibold text-red-700">
            Hapus Project
        </h3>

        <p class="mt-1 text-sm text-red-600">
            Project yang dihapus tidak dapat dikembalikan.
        </p>

        <form method="POST"
            action="{{ route('project-manager.projects.destroy', $project) }}"
            class="mt-4"
            onsubmit="return confirm('Yakin ingin menghapus project ini?')">
            @csrf
            @method('DELETE')

            <button type="submit"
                class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                Hapus Project
            </button>
        </form>
    </div>

</div>
@endsection
