@extends('layouts.employee')

@section('title', 'Tugas Saya')
@section('header', 'Tugas Saya')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-slate-900">
            Tugas Saya
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Daftar task yang diberikan kepada Anda.
        </p>
    </div>

    @if ($tasks->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-[#fffdfa] p-10 text-center">
            <div class="flex items-center justify-center w-12 h-12 mx-auto text-red-600 rounded-2xl bg-red-50">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 11l3 3L22 4M5 13l4 4L19 7"/>
                </svg>
            </div>

            <p class="mt-4 text-sm font-semibold text-slate-700">
                Belum ada tugas
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Task yang diberikan Project Manager akan tampil di sini.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($tasks as $task)
                @php
                    $total = $task->subtasks->count();

                    $completed = $task->subtasks
                        ->where('status', 'completed')
                        ->count();

                    $progress = $total > 0
                        ? (int) round(
                            ($completed / $total) * 100
                        )
                        : 0;

                    $statusLabel = match ($task->status) {
                        'pending' => 'Belum Dikerjakan',
                        'in_progress' => 'Dikerjakan',
                        'waiting_review' => 'Menunggu Review',
                        'revision' => 'Revisi',
                        'completed' => 'Selesai',
                        default => ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $task->status
                            )
                        ),
                    };
                @endphp

                <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-5 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-slate-900">
                                    {{ $task->title }}
                                </h3>

                                <span @class([
                                    'rounded-full border px-2.5 py-1 text-[10px] font-semibold',
                                    'border-slate-200 bg-slate-100 text-slate-600' => $task->status === 'pending',
                                    'border-blue-200 bg-blue-50 text-blue-700' => $task->status === 'in_progress',
                                    'border-amber-200 bg-amber-50 text-amber-700' => $task->status === 'waiting_review',
                                    'border-orange-200 bg-orange-50 text-orange-700' => $task->status === 'revision',
                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $task->status === 'completed',
                                ])>
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ $task->project?->name ?? '-' }}
                                <span class="mx-1 text-slate-300">•</span>
                                {{ $task->stage?->name ?? '-' }}
                            </p>

                            @if ($task->description)
                                <p class="mt-3 text-sm leading-6 line-clamp-2 text-slate-500">
                                    {{ $task->description }}
                                </p>
                            @endif

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
                                    <div class="h-full bg-red-600 rounded-full"
                                        style="width: {{ $progress }}%">
                                    </div>
                                </div>

                                <p class="mt-2 text-xs text-slate-400">
                                    {{ $completed }} dari
                                    {{ $total }} subtask selesai
                                </p>
                            </div>

                            <div class="flex flex-wrap mt-4 text-xs gap-x-5 gap-y-2 text-slate-400">
                                <span>
                                    Deadline:
                                    <strong class="font-semibold text-slate-600">
                                        {{ $task->deadline?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                                    </strong>
                                </span>

                                <span>
                                    Tim:
                                    <strong class="font-semibold text-slate-600">
                                        {{ $task->assignees->count() }} orang
                                    </strong>
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('employee.tasks.show', $task) }}"
                            wire:navigate
                            class="shrink-0 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-xs font-semibold text-white transition hover:bg-slate-800">
                            Detail Task
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
