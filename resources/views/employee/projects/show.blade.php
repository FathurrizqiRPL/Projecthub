@extends('layouts.employee')

@section('title', $project->name)
@section('header', 'Detail proyek')

@section('content')
<div class="space-y-6">
    <a href="{{ route('employee.projects.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-red-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke proyek
    </a>

    <section class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-red-600">Proyek</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $project->name }}</h2>
                @if ($project->description)
                    <p class="mt-3 max-w-3xl whitespace-pre-line text-sm leading-6 text-slate-500">{{ $project->description }}</p>
                @endif
            </div>

            <div class="text-right text-xs text-slate-500">
                @if ($project->start_date)
                    <p>Mulai {{ $project->start_date->locale('id')->translatedFormat('d F Y') }}</p>
                @endif
                @if ($project->deadline)
                    <p class="mt-1">Tenggat {{ $project->deadline->locale('id')->translatedFormat('d F Y') }}</p>
                @endif
            </div>
        </div>

        @if ($project->stages->isNotEmpty())
            <div class="mt-7 border-t border-slate-100 pt-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Workflow proyek</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($project->stages as $projectStage)
                        @php
                            $isSelected = $stage?->is($projectStage) ?? false;
                            $isCurrent = $project->current_stage_id === $projectStage->id;
                            $isCompleted = $projectStage->completed_at !== null;
                        @endphp

                        <a href="{{ route('employee.projects.show', ['project' => $project, 'stage' => $projectStage->id]) }}"
                            @class([
                                'inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition',
                                'border-red-600 bg-red-600 text-white' => $isSelected,
                                'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => ! $isSelected && $isCompleted,
                                'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => ! $isSelected && ! $isCompleted,
                            ])>
                            @if ($isCompleted)
                                <span aria-hidden="true">✓</span>
                            @elseif ($isCurrent)
                                <span @class([
                                    'h-2 w-2 rounded-full',
                                    'bg-white' => $isSelected,
                                    'bg-red-500' => ! $isSelected,
                                ])></span>
                            @endif
                            {{ $projectStage->name }}
                            @if ($isCurrent)
                                <span class="text-[10px] font-bold uppercase opacity-80">Saat ini</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @if (! $stage)
        <div class="rounded-2xl border border-dashed border-slate-200 bg-[#fffdfa] p-10 text-center">
            <p class="text-sm font-semibold text-slate-700">Proyek belum memiliki tahapan</p>
            <p class="mt-1 text-sm text-slate-500">Tugas proyek akan tampil setelah workflow dibuat.</p>
        </div>
    @else
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="text-xl font-bold text-slate-900">{{ $stage->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">Tugas pada tahapan ini, termasuk tugas yang ditangani rekan tim.</p>
            </div>
            <span class="text-sm text-slate-400">{{ $tasks->count() }} tugas</span>
        </div>

        @foreach ([
            [
                'title' => 'Tugas Saya',
                'description' => 'Tugas yang ditugaskan kepada Anda dan dapat Anda tindak lanjuti.',
                'tasks' => $myTasks,
                'assigned' => true,
            ],
            [
                'title' => 'Tugas Rekan Tim',
                'description' => 'Tugas anggota lain di proyek ini. Anda dapat melihat detailnya dalam mode baca saja.',
                'tasks' => $teammateTasks,
                'assigned' => false,
            ],
        ] as $group)
            <section class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">{{ $group['title'] }}</h4>
                        <p class="mt-1 text-sm text-slate-500">{{ $group['description'] }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ $group['tasks']->count() }} tugas
                    </span>
                </div>

                @if ($group['tasks']->isEmpty())
                    <p class="mt-5 rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                        Belum ada tugas pada kelompok ini di tahap {{ $stage->name }}.
                    </p>
                @else
                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        @foreach ($group['tasks'] as $task)
                            @php
                                $totalSubtasks = $task->subtasks->count();
                                $completedSubtasks = $task->subtasks->where('status', 'completed')->count();
                                $progress = $totalSubtasks > 0
                                    ? (int) round(($completedSubtasks / $totalSubtasks) * 100)
                                    : 0;
                                $statusLabel = $task->completionException
                                    ? 'Dikecualikan'
                                    : match ($task->status) {
                                        'pending' => 'Belum dikerjakan',
                                        'in_progress' => 'Dikerjakan',
                                        'waiting_review' => 'Menunggu peninjauan',
                                        'revision' => 'Revisi',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Dibatalkan',
                                        default => ucfirst(str_replace('_', ' ', $task->status)),
                                    };
                            @endphp

                            <article class="rounded-xl border border-slate-200 bg-white p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h5 class="font-bold text-slate-900">{{ $task->title }}</h5>
                                        @if ($task->description)
                                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{{ $task->description }}</p>
                                        @endif
                                    </div>
                                    <span @class([
                                        'shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold',
                                        'bg-slate-100 text-slate-600' => $task->status === 'pending',
                                        'bg-blue-50 text-blue-700' => $task->status === 'in_progress',
                                        'bg-amber-50 text-amber-700' => $task->completionException || $task->status === 'waiting_review',
                                        'bg-orange-50 text-orange-700' => $task->status === 'revision',
                                        'bg-emerald-50 text-emerald-700' => $task->status === 'completed',
                                        'bg-slate-100 text-slate-500' => $task->status === 'cancelled',
                                    ])>
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-500">Progres subtugas</span>
                                        <span class="font-bold text-slate-700">{{ $progress }}%</span>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-red-600" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-400">{{ $completedSubtasks }} dari {{ $totalSubtasks }} subtugas selesai</p>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($task->assignees as $assignee)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                                {{ $assignee->name }}
                                                @if ($assignee->is(auth()->user()))
                                                    (Anda)
                                                @endif
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400">Belum ada assignee</span>
                                        @endforelse
                                    </div>
                                    @if ($task->deadline)
                                        <span class="text-xs text-slate-500">Tenggat {{ $task->deadline->locale('id')->translatedFormat('d M Y') }}</span>
                                    @endif
                                </div>

                                <a href="{{ route('employee.tasks.show', $task) }}"
                                    class="mt-4 inline-flex rounded-lg px-3 py-2 text-xs font-semibold transition {{ $group['assigned'] ? 'bg-slate-900 text-white hover:bg-slate-800' : 'border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                                    {{ $group['assigned'] ? 'Buka tugas' : 'Lihat detail saja' }}
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    @endif
</div>
@endsection
