@extends('layouts.project-manager')

@section('title', 'Task')
@section('header', 'Task')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Daftar Task Project</h2>
            <p class="mt-1 text-sm text-slate-500">
                Task dikelompokkan berdasarkan project yang Anda kelola.
            </p>
        </div>

        <form method="GET" action="{{ route('project-manager.tasks.index') }}" class="mt-5">
            <label for="search" class="sr-only">Cari nama project</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    id="search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari nama project..."
                    class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-red-600 focus:ring-red-600"
                >
                @if ($reviewOnly)
                    <input type="hidden" name="filter" value="review">
                @endif
                <button
                    type="submit"
                    class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                >
                    Cari
                </button>
                @if ($search !== '')
                    <a
                        href="{{ route('project-manager.tasks.index', $reviewOnly ? ['filter' => 'review'] : []) }}"
                        class="rounded-xl border border-slate-200 px-5 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <div class="flex flex-wrap gap-2 mt-5">
            <a
                href="{{ route('project-manager.tasks.index', array_filter(['search' => $search])) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition',
                    'border-red-600 bg-red-600 text-white' => ! $reviewOnly,
                    'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => $reviewOnly,
                ])
            >
                Semua Task
            </a>
            <a
                href="{{ route('project-manager.tasks.index', array_filter(['search' => $search, 'filter' => 'review'])) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition',
                    'border-amber-500 bg-amber-500 text-white' => $reviewOnly,
                    'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' => ! $reviewOnly,
                ])
            >
                Perlu Review
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs',
                    'bg-white/20 text-white' => $reviewOnly,
                    'bg-white text-amber-700' => ! $reviewOnly,
                ])>
                    {{ $reviewTaskCount }}
                </span>
            </a>
        </div>
    </div>

    @if ($projects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-[#fffdfa] p-10 text-center">
            <p class="text-sm font-semibold text-slate-700">
                {{ $reviewOnly ? 'Tidak ada task yang menunggu review' : 'Task tidak ditemukan' }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
                @if ($search !== '')
                    Coba kata kunci nama project yang berbeda.
                @elseif ($reviewOnly)
                    Task yang diajukan employee akan tampil di sini untuk divalidasi.
                @else
                    Project yang memiliki task akan tampil di sini.
                @endif
            </p>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($projects as $project)
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-[#fffdfa] shadow-sm">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">
                                {{ $project->name }}
                            </h3>
                            @if ($project->description)
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $project->description }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($project->waiting_review_tasks_count > 0)
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                    {{ $project->waiting_review_tasks_count }} perlu review
                                </span>
                            @endif
                            <a
                                href="{{ route('project-manager.projects.tasks.index', $project) }}"
                                wire:navigate
                                class="shrink-0 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-xs font-semibold text-white transition hover:bg-slate-800"
                            >
                                Lihat Task Project
                            </a>
                        </div>
                    </header>

                    <div class="space-y-3 p-4 sm:p-5">
                        @foreach ($project->tasks as $task)
                            @php
                                $statusLabel = match ($task->status) {
                                    'pending' => 'Belum Dikerjakan',
                                    'in_progress' => 'Dikerjakan',
                                    'waiting_review' => 'Perlu Review',
                                    'revision' => 'Revisi',
                                    'completed' => 'Selesai',
                                    default => ucfirst(str_replace('_', ' ', $task->status)),
                                };
                                $totalSubtasks = $task->subtasks->count();
                                $completedSubtasks = $task->subtasks
                                    ->where('status', 'completed')
                                    ->count();
                                $progress = $totalSubtasks > 0
                                    ? (int) round(($completedSubtasks / $totalSubtasks) * 100)
                                    : 0;
                            @endphp

                            <article class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-bold text-slate-900">
                                                {{ $task->title }}
                                            </h4>
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

                                        <p class="mt-1 text-xs text-slate-500">
                                            Tahap: {{ $task->stage?->name ?? '-' }}
                                            <span class="mx-1 text-slate-300">·</span>
                                            {{ $completedSubtasks }} dari {{ $totalSubtasks }} subtask selesai
                                        </p>

                                        <div class="mt-3 h-1.5 max-w-md overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-red-600" style="width: {{ $progress }}%"></div>
                                        </div>

                                        @if ($task->assignees->isNotEmpty())
                                            <p class="mt-3 text-xs text-slate-500">
                                                Anggota:
                                                {{ $task->assignees->pluck('name')->join(', ') }}
                                            </p>
                                        @endif
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div>
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection
