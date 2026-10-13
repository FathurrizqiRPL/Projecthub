@extends('layouts.project-manager')

@section('title', 'Antrian Review')
@section('header', 'Antrian Review')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('project-manager.projects.tasks.index', $project) }}" wire:navigate
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-red-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Task Project
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900">{{ $project->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            Task yang diajukan employee dan menunggu keputusan review.
        </p>
    </div>

    @if ($tasks->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-[#fffdfa] p-10 text-center">
            <p class="text-sm font-semibold text-slate-700">Belum ada task untuk direview</p>
            <p class="mt-1 text-xs text-slate-400">
                Task akan muncul di sini setelah employee mengajukannya.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($tasks as $task)
                <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $task->title }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Tahap: {{ $task->stage?->name ?? '-' }}
                            </p>

                            <div class="flex flex-wrap gap-2 mt-3">
                                @foreach ($task->assignees as $assignee)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                        {{ $assignee->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <a href="{{ route('project-manager.projects.tasks.show', [$project, $task]) }}" wire:navigate
                            class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                            Review Task
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
