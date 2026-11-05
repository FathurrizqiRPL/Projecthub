@extends('layouts.employee')

@section('title', 'Tugas saya')
@section('header', 'Tugas saya')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl border border-red-100 bg-gradient-to-r from-red-50 to-[#fffdfa] p-6 lg:p-8">
        <p class="text-sm font-semibold text-red-600">Selamat datang kembali</p>
        <h2 class="text-xl font-bold text-slate-900">
            Halo, {{ auth()->user()->name }} 👋
        </h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Pantau tugas Anda dan lanjutkan pekerjaan yang perlu dikerjakan atau direvisi.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total tugas', 'value' => $taskSummary['total'], 'hint' => 'Semua tugas yang diberikan'],
            ['label' => 'Belum dikerjakan', 'value' => $taskSummary['pending'], 'hint' => 'Menunggu untuk dimulai'],
            ['label' => 'Sedang dikerjakan', 'value' => $taskSummary['in_progress'], 'hint' => 'Tugas yang sedang berjalan'],
            ['label' => 'Revisi', 'value' => $taskSummary['revision'], 'hint' => 'Perlu diperbaiki'],
            ['label' => 'Selesai', 'value' => $taskSummary['completed'], 'hint' => 'Tugas yang telah selesai'],
        ] as $summary)
            <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $summary['label'] }}</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['value'] }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $summary['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <h3 class="font-semibold text-slate-900">Aksi cepat</h3>
        <p class="mt-1 text-sm text-slate-500">Buka tugas yang perlu segera Anda tindak lanjuti.</p>

        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach ([
                ['key' => 'pending', 'title' => 'Mulai tugas', 'empty' => 'Tidak ada tugas baru yang perlu dimulai.', 'style' => 'bg-red-600 text-white hover:bg-red-700'],
                ['key' => 'revision', 'title' => 'Lanjutkan revisi', 'empty' => 'Tidak ada revisi yang perlu ditindaklanjuti.', 'style' => 'border border-slate-200 text-slate-700 hover:bg-slate-50'],
            ] as $action)
                @php($actionTask = $quickActionTasks[$action['key']])
                @if ($actionTask)
                    <a href="{{ route('employee.tasks.show', $actionTask) }}" wire:navigate class="flex items-center justify-between gap-4 rounded-xl px-4 py-3 text-sm font-semibold transition {{ $action['style'] }}">
                        <span class="min-w-0">
                            <span class="block">{{ $action['title'] }}</span>
                            <span class="mt-1 block truncate text-xs {{ $action['key'] === 'pending' ? 'text-red-100' : 'text-slate-500' }}">{{ $actionTask->title }} · {{ $actionTask->project?->name }}</span>
                        </span>
                        <span aria-hidden="true">→</span>
                    </a>
                @else
                    <div class="rounded-xl border border-dashed border-slate-200 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-500">{{ $action['title'] }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $action['empty'] }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    <div>
        <h3 class="text-lg font-bold text-slate-900">Semua tugas</h3>
        <p class="mt-1 text-sm text-slate-500">Daftar tugas yang diberikan kepada Anda.</p>
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
                tugas yang diberikan Manajer proyek akan tampil di sini.
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

                    $statusLabel = $task->completionException
                        ? 'Dikecualikan oleh manajer proyek'
                        : match ($task->status) {
                        'pending' => 'Belum Dikerjakan',
                        'in_progress' => 'Dikerjakan',
                        'waiting_review' => 'Menunggu peninjauan',
                        'revision' => 'Revisi',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
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
                                    'border-slate-200 bg-slate-100 text-slate-600' => ! $task->completionException && $task->status === 'pending',
                                    'border-blue-200 bg-blue-50 text-blue-700' => ! $task->completionException && $task->status === 'in_progress',
                                    'border-amber-200 bg-amber-50 text-amber-700' => $task->completionException || $task->status === 'waiting_review',
                                    'border-orange-200 bg-orange-50 text-orange-700' => ! $task->completionException && $task->status === 'revision',
                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => ! $task->completionException && $task->status === 'completed',
                                    'border-slate-200 bg-slate-100 text-slate-500' => $task->status === 'cancelled',
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
                                        Progres
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
                                    {{ $total }} Subtugas selesai
                                </p>
                            </div>

                            <div class="flex flex-wrap mt-4 text-xs gap-x-5 gap-y-2 text-slate-400">
                                <span>
                                    Tenggat:
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
                            Detail tugas
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
