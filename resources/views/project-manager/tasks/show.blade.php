@extends('layouts.project-manager')

@section('title', 'Detail tugas')
@section('header', 'Detail tugas')

@section('content')
@php
    $statusLabel = $task->completionException
        ? 'Dikecualikan'
        : match ($task->status) {
        'pending' => 'Belum Dikerjakan',
        'in_progress' => 'Dikerjakan',
        'waiting_review' => 'Menunggu peninjauan',
        'revision' => 'Revisi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
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
        class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke Tugas
    </a>

    @if (session('success'))
        <div class="px-4 py-3 text-sm border rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($task->completionException)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold">Tugas ini dikecualikan saat proyek ditutup.</p>
            <p class="mt-1">Alasan: {{ $task->completionException->reason }}</p>
        </div>
    @elseif ($task->status === 'cancelled')
        <div class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600">
            <p class="font-semibold">Tugas ini sudah dibatalkan dan riwayatnya tetap disimpan.</p>
            @if ($task->cancelled_at)
                <p class="mt-1">Dibatalkan oleh {{ $task->canceller?->name ?? '-' }} pada {{ $task->cancelled_at->locale('id')->translatedFormat('d F Y, H:i') }}.</p>
            @endif
        </div>
    @elseif (! $canModifyProject && $task->status !== 'completed')
        <div class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600">
            Proyek sudah ditutup. Tugas ini tidak dapat dilanjutkan.
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

                <div class="flex flex-wrap mt-3 text-sm gap-x-5 gap-y-2 text-slate-500">
                    <span>
                        Proyek:
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
                        Tenggat:
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

            @if ($canEditTask)
                <a href="{{ route('project-manager.projects.tasks.edit', [$project, $task]) }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                    Edit tugas
                </a>
            @endif
        </div>

        <div class="pt-6 border-t mt-7 border-slate-100">
            <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                Anggota tugas
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
                        Belum ada anggota pada tugas ini.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900">Progres tugas</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Dihitung otomatis berdasarkan subtugas yang selesai.
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
                {{ $completedSubtasks }} dari {{ $totalSubtasks }} subtugas selesai
            </span>

            @if ($progress === 100 && $totalSubtasks > 0)
                <span class="font-semibold text-emerald-600">
                    Seluruh subtugas selesai
                </span>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div>
            <h3 class="font-bold text-slate-900">Subtugas</h3>
            <p class="mt-1 text-sm text-slate-500">
                Rincian pekerjaan yang dibuat dan dikerjakan oleh anggota tugas.
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
                    Belum ada subtugas
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-400">
                    Subtugas akan tampil setelah anggota mulai membagi pekerjaan pada tugas ini.
                </p>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($task->subtasks as $subtaskIndex => $subtask)
                    <article x-data="{ showDetail: false }" class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ $subtaskIndex + 1 }}</span>
                            <h4 class="flex-1 text-sm font-bold text-slate-900">{{ $subtask->title }}</h4>
                            <span @class([
                                'shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold',
                                'bg-emerald-50 text-emerald-700' => $subtask->status === 'completed',
                                'bg-slate-100 text-slate-500' => $subtask->status !== 'completed',
                            ])>{{ $subtask->status === 'completed' ? 'Selesai' : 'Belum Selesai' }}</span>
                        </div>

                        <dl class="mt-4 space-y-2 text-xs text-slate-500">
                            <div class="flex gap-1"><dt>Dibuat oleh</dt><dd class="font-semibold text-slate-700">{{ $subtask->creator?->name ?? '-' }}</dd></div>
                            @if ($subtask->status === 'completed')
                                <div class="flex gap-1"><dt>Diselesaikan oleh</dt><dd class="font-semibold text-slate-700">{{ $subtask->completer?->name ?? '-' }}</dd></div>
                            @endif
                        </dl>

                        <button type="button" @click="showDetail = true" class="mt-5 self-start text-sm font-semibold text-red-700 hover:text-red-800">
                            Buka detail
                        </button>

                        <div x-cloak x-show="showDetail" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" @click.self="showDetail = false" @keydown.escape.window="showDetail = false">
                            <section role="dialog" aria-modal="true" aria-labelledby="subtask-title-{{ $subtask->id }}" class="my-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 id="subtask-title-{{ $subtask->id }}" class="text-lg font-bold text-slate-900">{{ $subtask->title }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ $subtask->status === 'completed' ? 'Selesai' : 'Belum Selesai' }}</p>
                                    </div>
                                    <button type="button" @click="showDetail = false" aria-label="Tutup detail" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">&times;</button>
                                </div>

                                <div class="mt-6 space-y-5">
                                    <div class="grid gap-4 text-sm sm:grid-cols-2">
                                        <p><span class="block text-xs font-semibold uppercase text-slate-400">Dibuat oleh</span><span class="mt-1 block text-slate-700">{{ $subtask->creator?->name ?? '-' }}</span></p>
                                        @if ($subtask->status === 'completed')
                                            <p><span class="block text-xs font-semibold uppercase text-slate-400">Diselesaikan oleh</span><span class="mt-1 block text-slate-700">{{ $subtask->completer?->name ?? '-' }}</span></p>
                                        @endif
                                    </div>

                                    @if ($subtask->completed_at)
                                        <p class="text-xs text-slate-400">{{ $subtask->completed_at->locale('id')->translatedFormat('d F Y, H:i') }}</p>
                                    @endif

                                    @if ($subtask->description)
                                        <div><h4 class="text-xs font-semibold uppercase text-slate-400">Deskripsi</h4><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $subtask->description }}</p></div>
                                    @endif

                                    @if ($subtask->completion_note)
                                        <div class="rounded-xl bg-slate-50 p-4"><h4 class="text-xs font-semibold uppercase text-slate-400">Keterangan penyelesaian</h4><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $subtask->completion_note }}</p></div>
                                    @endif

                                    @if ($subtask->attachments->isNotEmpty())
                                        <div>
                                            <h4 class="text-xs font-semibold uppercase text-slate-400">Lampiran bukti</h4>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($subtask->attachments as $attachment)
                                                    <a href="{{ route('project-manager.projects.tasks.attachments.download', ['project' => $project, 'task' => $task, 'subtask' => $subtask, 'attachment' => $attachment]) }}" target="_blank" rel="noopener" class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs text-red-700 hover:border-red-300">
                                                        <span class="truncate">{{ $attachment->original_name }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </section>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <h3 class="font-bold text-slate-900">Riwayat Item Revisi</h3>
        <p class="mt-1 text-sm text-slate-500">Catatan disimpan per putaran Peninjauan dan tidak dihapus saat Tugas diajukan ulang.</p>

        @if ($task->revisionItems->isEmpty())
            <div class="mt-5 rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-500">
                Belum ada item revisi untuk tugas ini.
            </div>
        @else
            <div class="mt-5 space-y-5">
                @foreach ($task->revisionItems->groupBy('cycle') as $cycle => $revisionItems)
                    <section>
                        <h4 class="text-sm font-semibold text-slate-700">Putaran {{ $cycle }}</h4>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            @foreach ($revisionItems as $revisionIndex => $revisionItem)
                                <article x-data="{ showDetail: false }" class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-orange-50 text-xs font-bold text-orange-700">{{ $revisionIndex + 1 }}</span>
                                        <p class="flex-1 line-clamp-2 text-sm font-semibold leading-6 text-slate-800">{{ $revisionItem->note }}</p>
                                        <span @class(['shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold', 'bg-amber-50 text-amber-700' => $revisionItem->status === 'open', 'bg-blue-50 text-blue-700' => $revisionItem->status === 'in_progress', 'bg-emerald-50 text-emerald-700' => $revisionItem->status === 'resolved'])>{{ match ($revisionItem->status) { 'open' => 'Belum Dikerjakan', 'in_progress' => 'Dikerjakan', 'resolved' => 'Selesai' } }}</span>
                                    </div>
                                    <p class="mt-3 text-xs text-slate-500">Dibuat oleh <strong class="text-slate-700">{{ $revisionItem->creator?->name ?? '-' }}</strong></p>
                                    @if ($revisionItem->resolver)
                                        <p class="mt-1 text-xs text-slate-500">Diselesaikan oleh <strong class="text-slate-700">{{ $revisionItem->resolver->name }}</strong></p>
                                    @endif
                                    <button type="button" @click="showDetail = true" class="mt-5 self-start text-sm font-semibold text-red-700 hover:text-red-800">Buka detail</button>
                                    <div x-cloak x-show="showDetail" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" @click.self="showDetail = false" @keydown.escape.window="showDetail = false">
                                        <section role="dialog" aria-modal="true" class="my-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                                            <div class="flex items-start justify-between gap-4"><div><h5 class="text-lg font-bold text-slate-900">Detail item revisi</h5><p class="mt-1 text-sm text-slate-500">Putaran {{ $cycle }}</p></div><button type="button" @click="showDetail = false" aria-label="Tutup detail" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">&times;</button></div>
                                            <div class="mt-5 space-y-4 text-sm text-slate-600">
                                                <p class="whitespace-pre-line leading-6">{{ $revisionItem->note }}</p>
                                                <p class="text-xs text-slate-400">Dibuat oleh {{ $revisionItem->creator?->name ?? '-' }} @if ($revisionItem->resolved_at) · Diselesaikan oleh {{ $revisionItem->resolver?->name ?? '-' }} pada {{ $revisionItem->resolved_at->locale('id')->translatedFormat('d F Y, H:i') }} @endif</p>
                                                @if ($revisionItem->completion_note)<div class="rounded-xl bg-slate-50 p-4"><h6 class="text-xs font-semibold uppercase text-slate-400">Keterangan penyelesaian</h6><p class="mt-2 whitespace-pre-line leading-6">{{ $revisionItem->completion_note }}</p></div>@endif
                                                @if ($revisionItem->attachments->isNotEmpty())
                                                    <div><h6 class="text-xs font-semibold uppercase text-slate-400">Lampiran bukti</h6><div class="mt-2 flex flex-wrap gap-2">@foreach ($revisionItem->attachments as $attachment)<a href="{{ route('project-manager.projects.tasks.revisions.attachments.download', ['project' => $project, 'task' => $task, 'revisionItem' => $revisionItem, 'attachment' => $attachment]) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-3 py-2 text-xs text-red-700">{{ $attachment->original_name }}</a>@endforeach</div></div>
                                                @endif
                                            </div>
                                        </section>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
        @endif

    @if ($canModifyProject && $task->status === 'waiting_review')
        <div class="rounded-2xl border border-amber-200 bg-[#fffdfa] p-6 shadow-sm">
            <h3 class="font-bold text-slate-900">Keputusan peninjauan</h3>
            <p class="mt-1 text-sm text-slate-500">
                Setujui Tugas atau kirim daftar revisi. Tulis satu item revisi pada setiap baris.
            </p>

            <div class="grid gap-6 mt-5 lg:grid-cols-2">
                <form
                    action="{{ route('project-manager.projects.tasks.approve', [$project, $task]) }}"
                    method="POST"
                    class="flex flex-col items-start gap-3"
                >
                    @csrf
                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Setujui dan Selesaikan Tugas
                    </button>
                </form>

                <form
                    action="{{ route('project-manager.projects.tasks.request-revision', [$project, $task]) }}"
                    method="POST"
                    class="space-y-3"
                >
                    @csrf
                    <div>
                        <label for="revision_notes" class="block text-sm font-semibold text-slate-700">
                            Item revisi
                        </label>
                        <textarea
                            id="revision_notes"
                            name="revision_notes"
                            rows="5"
                            required
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-600 focus:ring-red-600"
                            placeholder="Perbaiki validasi Masuk&#10;Sesuaikan tampilan mobile"
                        >{{ old('revision_notes') }}</textarea>
                        @error('revision_notes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('revision_items')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('revision_items.*')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700"
                    >
                        Minta Revisi
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
