@extends('layouts.project-manager')

@section('title', 'Task Project')
@section('header', 'Task Project')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('project-manager.projects.show', $project) }}" wire:navigate
            class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Project
        </a>

        @if (!in_array($project->status, ['completed', 'cancelled']))
            <a href="{{ route('project-manager.projects.tasks.create', ['project' => $project, 'stage' => $stage?->id]) }}" wire:navigate
                class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                + Buat Task
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="px-4 py-3 text-sm border rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900">{{ $project->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            Pilih tahapan untuk melihat task pada setiap fase project.
        </p>

        @if ($project->stages->isNotEmpty())
            <div class="flex flex-wrap gap-2 mt-6">
                @foreach ($project->stages->sortBy('position') as $projectStage)
                    @php
                        $isSelected = $stage?->id === $projectStage->id;
                        $isCurrent = $project->current_stage_id === $projectStage->id;
                        $isCompleted = !is_null($projectStage->completed_at);
                    @endphp

                    <a href="{{ route('project-manager.projects.tasks.index', ['project' => $project, 'stage' => $projectStage->id]) }}"
                        wire:navigate
                        @class([
                            'inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition',
                            'border-red-600 bg-red-600 text-white' => $isSelected,
                            'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => !$isSelected && $isCompleted,
                            'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:text-slate-700' => !$isSelected && !$isCompleted,
                        ])>
                        @if ($isCompleted)
                            <span>✓</span>
                        @elseif ($isCurrent)
                            <span class="h-2 w-2 rounded-full {{ $isSelected ? 'bg-white' : 'bg-red-500' }}"></span>
                        @endif

                        {{ $projectStage->name }}

                        @if ($isCurrent)
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                'bg-white/20 text-white' => $isSelected,
                                'bg-red-50 text-red-600' => !$isSelected,
                            ])>
                                Saat Ini
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($stage)
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">{{ $stage->name }}</h3>

                @if ($stage->completed_at)
                    <p class="mt-1 text-xs text-emerald-600">
                        Selesai {{ $stage->completed_at->locale('id')->translatedFormat('d F Y') }}
                    </p>
                @elseif ($project->current_stage_id === $stage->id)
                    <p class="mt-1 text-xs text-red-600">Fase project saat ini</p>
                @endif
            </div>

            <span class="text-sm text-slate-400">
                {{ $tasks->count() }} task
            </span>
        </div>
    @endif

    @if (!$stage)
        <div class="rounded-2xl border border-dashed border-slate-200 bg-[#fffdfa] p-10 text-center">
            <p class="text-sm font-semibold text-slate-700">Belum ada tahapan project</p>
            <p class="mt-1 text-xs text-slate-400">
                Atur workflow project terlebih dahulu sebelum membuat task.
            </p>
        </div>
    @elseif ($tasks->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-[#fffdfa] p-10 text-center">
            <div class="flex items-center justify-center w-12 h-12 mx-auto text-red-600 rounded-2xl bg-red-50">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 11h6m-6 4h4m-6 6h10a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z"/>
                </svg>
            </div>

            <p class="mt-4 text-sm font-semibold text-slate-700">Belum ada task</p>
            <p class="mt-1 text-xs text-slate-400">
                Belum ada pekerjaan pada tahap {{ $stage->name }}.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($tasks as $task)
                @php
                    $statusLabel = match ($task->status) {
                        'pending' => 'Belum Dikerjakan',
                        'in_progress' => 'Dikerjakan',
                        'waiting_review' => 'Menunggu Review',
                        'revision' => 'Revisi',
                        'completed' => 'Selesai',
                        default => ucfirst(str_replace('_', ' ', $task->status)),
                    };

                    $totalSubtasks = $task->subtasks->count();
                    $completedSubtasks = $task->subtasks
                        ->where('status', 'completed')
                        ->count();

                    $progress = $totalSubtasks > 0
                        ? (int) round(
                            ($completedSubtasks / $totalSubtasks) * 100
                        )
                        : 0;
                @endphp

                <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-5 shadow-sm transition hover:border-slate-300">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base font-bold text-slate-900">
                                    {{ $task->title }}
                                </h4>

                                <span @class([
                                    'inline-flex rounded-full border px-2.5 py-1 text-[10px] font-semibold',
                                    'border-slate-200 bg-slate-100 text-slate-600' => $task->status === 'pending',
                                    'border-blue-200 bg-blue-50 text-blue-700' => $task->status === 'in_progress',
                                    'border-amber-200 bg-amber-50 text-amber-700' => $task->status === 'waiting_review',
                                    'border-orange-200 bg-orange-50 text-orange-700' => $task->status === 'revision',
                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $task->status === 'completed',
                                ])>
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            @if ($task->description)
                                <p class="mt-2 text-sm leading-6 line-clamp-2 text-slate-500">
                                    {{ $task->description }}
                                </p>
                            @endif

                            <div class="flex flex-wrap items-center gap-2 mt-4">
                                @forelse ($task->assignees as $assignee)
                                    <div class="inline-flex items-center gap-2 py-1 pl-1 pr-3 rounded-full bg-slate-100">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-red-50 text-[10px] font-bold text-red-600">
                                            {{ strtoupper(substr($assignee->name, 0, 1)) }}
                                        </div>

                                        <span class="text-xs font-semibold text-slate-600">
                                            {{ $assignee->name }}
                                        </span>
                                    </div>
                                @empty
                                    <span class="text-xs italic text-slate-400">
                                        Belum ada anggota.
                                    </span>
                                @endforelse
                            </div>

                            <div class="max-w-xl mt-5">
                                <div class="flex items-center justify-between gap-4 mb-2">
                                    <span class="text-xs font-semibold text-slate-500">
                                        Progress
                                    </span>

                                    <span class="text-xs font-bold text-slate-700">
                                        {{ $progress }}%
                                    </span>
                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full transition-all bg-red-600 rounded-full"
                                        style="width: {{ $progress }}%"></div>
                                </div>

                                <p class="mt-2 text-xs text-slate-400">
                                    {{ $completedSubtasks }} dari {{ $totalSubtasks }} subtask selesai
                                </p>
                            </div>

                            <div class="flex flex-wrap mt-4 text-xs gap-x-5 gap-y-2 text-slate-400">
                                <span>
                                    Tahap:
                                    <strong class="font-semibold text-slate-600">
                                        {{ $task->stage?->name ?? '-' }}
                                    </strong>
                                </span>

                                <span>
                                    Deadline:
                                    <strong class="font-semibold text-slate-600">
                                        {{ $task->deadline?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                                    </strong>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 shrink-0">
                            <a href="{{ route('project-manager.projects.tasks.show', [$project, $task]) }}" wire:navigate
                                class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800">
                                Detail Task
                            </a>

                            @if ($task->status === 'pending')
                                <form method="POST"
                                    action="{{ route('project-manager.projects.tasks.destroy', [$project, $task]) }}"
                                    onsubmit="return confirm('Yakin ingin menghapus task ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="rounded-xl border border-red-200 px-4 py-2.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
