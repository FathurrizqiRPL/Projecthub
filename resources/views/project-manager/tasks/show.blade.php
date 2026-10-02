@extends('layouts.project-manager')

@section('title', 'Detail Task')
@section('header', 'Detail Task')

@section('content')
@php
    $statusLabel = match ($task->status) {
        'pending' => 'Belum Dikerjakan',
        'in_progress' => 'Dikerjakan',
        'waiting_review' => 'Menunggu Review',
        'revision' => 'Revisi',
        'completed' => 'Selesai',
        default => ucfirst(str_replace('_', ' ', $task->status)),
    };

    $progress = $totalSubtasks > 0
        ? (int) round(
            ($completedSubtasks / $totalSubtasks) * 100
        )
        : 0;
@endphp

<div class="space-y-6">
    <a href="{{ route('project-manager.projects.tasks.index', ['project' => $project, 'stage' => $task->project_stage_id]) }}"
        wire:navigate
        class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke Task
    </a>

    @if (session('success'))
        <div class="px-4 py-3 text-sm border rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold text-slate-900">
                        {{ $task->title }}
                    </h2>

                    <span @class([
                        'inline-flex rounded-full border px-3 py-1 text-xs font-semibold',
                        'border-slate-200 bg-slate-100 text-slate-600' => $task->status === 'pending',
                        'border-blue-200 bg-blue-50 text-blue-700' => $task->status === 'in_progress',
                        'border-amber-200 bg-amber-50 text-amber-700' => $task->status === 'waiting_review',
                        'border-orange-200 bg-orange-50 text-orange-700' => $task->status === 'revision',
                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $task->status === 'completed',
                    ])>
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="flex flex-wrap mt-3 text-sm gap-x-5 gap-y-2 text-slate-500">
                    <span>
                        Project:
                        <strong class="font-semibold text-slate-700">
                            {{ $project->name }}
                        </strong>
                    </span>

                    <span>
                        Tahap:
                        <strong class="font-semibold text-slate-700">
                            {{ $task->stage?->name ?? '-' }}
                        </strong>
                    </span>

                    <span>
                        Deadline:
                        <strong class="font-semibold text-slate-700">
                            {{ $task->deadline?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                        </strong>
                    </span>
                </div>

                @if ($task->description)
                    <div class="mt-6">
                        <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                            Deskripsi
                        </p>

                        <p class="mt-2 text-sm leading-7 whitespace-pre-line text-slate-600">{{ $task->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-6 border-t mt-7 border-slate-100">
            <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                Anggota Task
            </p>

            <div class="flex flex-wrap gap-2 mt-3">
                @forelse ($task->assignees as $assignee)
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1.5 pl-1.5 pr-4">
                        <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-red-600 rounded-full bg-red-50">
                            {{ strtoupper(substr($assignee->name, 0, 1)) }}
                        </div>

                        <span class="text-sm font-semibold text-slate-700">
                            {{ $assignee->name }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">
                        Belum ada anggota pada task ini.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900">Progress Task</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Dihitung otomatis berdasarkan subtask yang selesai.
                </p>
            </div>

            <p class="text-2xl font-bold text-slate-900">
                {{ $progress }}%
            </p>
        </div>

        <div class="h-3 mt-5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full transition-all bg-red-600 rounded-full"
                style="width: {{ $progress }}%"></div>
        </div>

        <div class="flex items-center justify-between gap-4 mt-3 text-xs">
            <span class="text-slate-400">
                {{ $completedSubtasks }} dari {{ $totalSubtasks }} subtask selesai
            </span>

            @if ($progress === 100 && $totalSubtasks > 0)
                <span class="font-semibold text-emerald-600">
                    Seluruh subtask selesai
                </span>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div>
            <h3 class="font-bold text-slate-900">Subtask</h3>
            <p class="mt-1 text-sm text-slate-500">
                Rincian pekerjaan yang dibuat dan dikerjakan oleh anggota task.
            </p>
        </div>

        @if ($task->subtasks->isEmpty())
            <div class="p-10 mt-6 text-center border border-dashed rounded-2xl border-slate-200">
                <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 11l3 3L22 4M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <p class="mt-4 text-sm font-semibold text-slate-700">
                    Belum ada subtask
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-400">
                    Subtask akan tampil setelah anggota mulai membagi pekerjaan pada task ini.
                </p>
            </div>
        @else
            <div class="mt-6 divide-y divide-slate-100">
                @foreach ($task->subtasks as $subtask)
                    <div class="py-5 first:pt-0 last:pb-0">
                        <div class="flex gap-4">
                            <div @class([
                                'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                                'bg-emerald-50 text-emerald-600' => $subtask->status === 'completed',
                                'bg-slate-100 text-slate-400' => $subtask->status !== 'completed',
                            ])>
                                @if ($subtask->status === 'completed')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                                    </svg>
                                @else
                                    <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 @class([
                                        'text-sm font-bold',
                                        'text-slate-900' => $subtask->status !== 'completed',
                                        'text-slate-500' => $subtask->status === 'completed',
                                    ])>
                                        {{ $subtask->title }}
                                    </h4>

                                    @if ($subtask->status === 'completed')
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                            Selesai
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-500">
                                            Belum Selesai
                                        </span>
                                    @endif
                                </div>

                                @if ($subtask->description)
                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        {{ $subtask->description }}
                                    </p>
                                @endif

                                <div class="flex flex-wrap mt-3 text-xs gap-x-5 gap-y-2 text-slate-400">
                                    <span>
                                        Dibuat oleh
                                        <strong class="font-semibold text-slate-600">
                                            {{ $subtask->creator?->name ?? '-' }}
                                        </strong>
                                    </span>

                                    @if ($subtask->status === 'completed')
                                        <span>
                                            Diselesaikan oleh
                                            <strong class="font-semibold text-slate-600">
                                                {{ $subtask->completer?->name ?? '-' }}
                                            </strong>
                                        </span>

                                        @if ($subtask->completed_at)
                                            <span>
                                                {{ $subtask->completed_at->locale('id')->translatedFormat('d F Y, H:i') }}
                                            </span>
                                        @endif
                                    @endif
                                </div>

                                @if ($subtask->completion_note)
                                    <div class="p-4 mt-4 rounded-xl bg-slate-50">
                                        <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                                            Keterangan Penyelesaian
                                        </p>

                                        <p class="mt-2 text-sm leading-6 whitespace-pre-line text-slate-600">{{ $subtask->completion_note }}</p>
                                    </div>
                                @endif

                                @if ($subtask->attachments->isNotEmpty())
                                    <div class="mt-4">
                                        <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                                            Bukti
                                        </p>

                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach ($subtask->attachments as $attachment)
                                                <div class="inline-flex items-center max-w-full gap-2 px-3 py-2 text-xs bg-white border rounded-lg border-slate-200 text-slate-600">
                                                    <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.172 7l-6.586 6.586a2 2 0 1 0 2.828 2.828L18 9.828a4 4 0 1 0-5.657-5.657L5.757 10.757a6 6 0 1 0 8.486 8.486L20.5 13"/>
                                                    </svg>

                                                    <span class="truncate">
                                                        {{ $attachment->original_name }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
