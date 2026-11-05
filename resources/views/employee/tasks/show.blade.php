@extends('layouts.employee')

@section('title', 'Detail tugas')
@section('header', 'Detail tugas')

@section('content')
@php
    $progress = $totalSubtasks > 0
        ? (int) round(
            ($completedSubtasks / $totalSubtasks) * 100
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
            str_replace('_', ' ', $task->status)
        ),
    };
@endphp

<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ route('employee.projects.show', ['project' => $task->project, 'stage' => $task->project_stage_id]) }}"
        wire:navigate
        class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke proyek
    </a>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-bold text-slate-900">
                {{ $task->title }}
            </h2>

            @unless ($isAssignee)
                <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    Hanya lihat
                </span>
            @endunless

            <span @class([
                'rounded-full border px-3 py-1 text-xs font-semibold',
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

        @if ($task->status === 'cancelled')
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                Tugas ini sudah dibatalkan oleh PM. Riwayat tugas tetap tersimpan.
            </div>
        @endif

        <div class="flex flex-wrap mt-3 text-sm gap-x-5 gap-y-2 text-slate-500">
            <span>
                Proyek:
                <strong class="font-semibold text-slate-700">
                    {{ $task->project?->name ?? '-' }}
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

        <div class="pt-6 border-t mt-7 border-slate-100">
            <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                Anggota tugas
            </p>

            <div class="flex flex-wrap gap-2 mt-3">
                @foreach ($task->assignees as $assignee)
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1.5 pl-1.5 pr-4">
                        <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-red-600 rounded-full bg-red-50">
                            {{ strtoupper(substr($assignee->name, 0, 1)) }}
                        </div>

                        <span class="text-sm font-semibold text-slate-700">
                            {{ $assignee->name }}

                            @if ($assignee->id === auth()->id())
                                <span class="font-normal text-slate-400">
                                    (Anda)
                                </span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900">
                    Progres Tugas
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Dihitung dari subtugas yang telah selesai.
                </p>
            </div>

            <span class="text-2xl font-bold text-slate-900">
                {{ $progress }}%
            </span>
        </div>

        <div class="h-3 mt-5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full bg-red-600 rounded-full"
                style="width: {{ $progress }}%">
            </div>
        </div>

        <p class="mt-3 text-xs text-slate-400">
            {{ $completedSubtasks }} dari
            {{ $totalSubtasks }} Subtugas selesai
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900">Aksi tugas</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Progres 100% belum berarti tugas disetujui; PM perlu melakukan Peninjauan.
                </p>
            </div>

            @if (! $canEditTask)
                <div class="max-w-md rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-600">
                    @if (! $isAssignee)
                        <p class="font-semibold">Anda tidak ditugaskan ke tugas ini.</p>
                        <p class="mt-1">Detail dapat dilihat, tetapi hanya assignee yang dapat mengerjakan tugas.</p>
                    @elseif ($task->completionException)
                        <p class="font-semibold">Tugas ini dikecualikan saat proyek ditutup.</p>
                        <p class="mt-1">Alasan: {{ $task->completionException->reason }}</p>
                    @else
                        <p class="font-semibold">Proyek sudah ditutup; tugas ini tidak dapat ditindaklanjuti.</p>
                    @endif
                </div>
            @elseif ($task->status === 'pending')
                <form action="{{ route('employee.tasks.start', $task) }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                    >
                        Mulai Mengerjakan
                    </button>
                </form>
            @elseif ($task->status === 'in_progress' && $totalSubtasks > 0 && $completedSubtasks === $totalSubtasks)
                <form action="{{ route('employee.tasks.submit', $task) }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                    >
                        Ajukan untuk Peninjauan
                    </button>
                </form>
            @elseif ($task->status === 'in_progress')
                <p class="text-sm font-medium text-slate-500">
                    Selesaikan semua subtugas untuk mengajukan peninjauan.
                </p>
            @elseif ($task->status === 'waiting_review')
                <p class="rounded-full bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-700">
                    Menunggu peninjauan manajer Proyek
                </p>
            @elseif ($task->status === 'revision')
                <p class="rounded-full bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700">
                    Tindak lanjuti item revisi di bawah
                </p>
            @elseif ($task->status === 'completed')
                <p class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">
                    tugas telah disetujui
                </p>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-orange-200 bg-[#fffdfa] p-6 shadow-sm">
            <h3 class="font-bold text-slate-900">Item Revisi</h3>
            <p class="mt-1 text-sm text-slate-500">
                Selesaikan semua item pada putaran aktif agar tugas dapat diajukan kembali.
            </p>

            @if ($task->revisionItems->isEmpty())
                <div class="mt-5 rounded-xl border border-dashed border-orange-200 bg-white p-6 text-center text-sm text-slate-500">Belum ada item revisi untuk tugas ini.</div>
            @else
            <div class="mt-5 space-y-5">
                @foreach ($task->revisionItems->groupBy('cycle') as $cycle => $revisionItems)
                    <section>
                        <h4 class="text-sm font-semibold text-slate-700">
                            Putaran {{ $cycle }}
                        </h4>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            @foreach ($revisionItems as $revisionItem)
                                <article x-data="{ showDetail: @js($errors->any() && (string) old('revision_item_id') === (string) $revisionItem->id) }" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm leading-6 whitespace-pre-line text-slate-700">
                                                {{ $revisionItem->note }}
                                            </p>
                                            <p class="mt-2 text-xs text-slate-400">
                                                Catatan dari {{ $revisionItem->creator?->name ?? '-' }}
                                                @if ($revisionItem->resolved_at)
                                                    · Diselesaikan {{ $revisionItem->resolved_at->locale('id')->translatedFormat('d F Y, H:i') }}
                                                @endif
                                            </p>
                                        </div>

                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-amber-50 text-amber-700' => $revisionItem->status === 'open',
                                            'bg-blue-50 text-blue-700' => $revisionItem->status === 'in_progress',
                                            'bg-emerald-50 text-emerald-700' => $revisionItem->status === 'resolved',
                                        ])>
                                            {{ match ($revisionItem->status) {
                                                'open' => 'Belum Dikerjakan',
                                                'in_progress' => 'Dikerjakan',
                                                'resolved' => 'Selesai',
                                            } }}
                                        </span>
                                    </div>

                                    @if ($revisionItem->status === 'resolved')
                                        <p class="mt-2 text-xs text-slate-500">Diselesaikan oleh {{ $revisionItem->resolver?->name ?? '-' }}</p>
                                    @endif

                                    @if ($canEditTask
                                        && $task->status === 'revision'
                                        && $revisionItem->cycle === $task->review_cycle
                                        && $revisionItem->status !== 'resolved')
                                        <button type="button" @click="showDetail = true" class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Laporkan penyelesaian</button>
                                    @endif

                                    <button type="button" @click="showDetail = true" class="ml-2 mt-4 text-xs font-semibold text-slate-600 hover:text-red-700">Buka detail</button>

                                    <div x-cloak x-show="showDetail" x-transition.opacity style="display:none" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" @click.self="showDetail = false" @keydown.escape.window="showDetail = false">
                                        <section role="dialog" aria-modal="true" class="my-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                                            <div class="flex items-start justify-between gap-4"><div><h5 class="text-lg font-bold text-slate-900">Detail item revisi</h5><p class="mt-1 text-sm text-slate-500">Putaran {{ $cycle }}</p></div><button type="button" @click="showDetail = false" aria-label="Tutup detail" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">&times;</button></div>
                                            <div class="mt-5 space-y-4 text-sm text-slate-600"><p class="whitespace-pre-line leading-6">{{ $revisionItem->note }}</p><p class="text-xs text-slate-400">Catatan dari {{ $revisionItem->creator?->name ?? '-' }}</p>
                                                @if ($revisionItem->completion_note)<div class="rounded-xl bg-slate-50 p-4"><h6 class="text-xs font-semibold uppercase text-slate-400">Keterangan penyelesaian</h6><p class="mt-2 whitespace-pre-line leading-6">{{ $revisionItem->completion_note }}</p></div>@endif
                                                @if ($revisionItem->attachments->isNotEmpty())<div><h6 class="text-xs font-semibold uppercase text-slate-400">Bukti yang dikirim</h6><ul class="mt-2 space-y-1">@foreach ($revisionItem->attachments as $attachment)<li class="text-xs text-slate-500">{{ $attachment->original_name }}</li>@endforeach</ul></div>@endif
                                            </div>
                                            @if ($canEditTask && $task->status === 'revision' && $revisionItem->cycle === $task->review_cycle && $revisionItem->status !== 'resolved')
                                                <form action="{{ route('employee.tasks.revisions.update', ['task' => $task, 'revisionItem' => $revisionItem]) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4 border-t border-slate-100 pt-5">
                                                    @csrf @method('PATCH')<input type="hidden" name="revision_item_id" value="{{ $revisionItem->id }}">
                                                    <div><label for="revision-note-{{ $revisionItem->id }}" class="block text-sm font-semibold text-slate-700">Rincian revisi yang sudah diperbaiki</label><textarea id="revision-note-{{ $revisionItem->id }}" name="completion_note" rows="4" required maxlength="5000" class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm focus:border-red-600 focus:ring-red-600" placeholder="Jelaskan perbaikan yang sudah dilakukan...">{{ old('completion_note') }}</textarea>@error('completion_note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                                    <div><label for="revision-files-{{ $revisionItem->id }}" class="block text-sm font-semibold text-slate-700">Bukti penyelesaian <span class="font-normal text-slate-400">(wajib, maks. 5 file)</span></label><input id="revision-files-{{ $revisionItem->id }}" type="file" name="attachments[]" required multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600">@error('attachments')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @error('attachments.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                                    <div class="flex justify-end"><button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Kirim penyelesaian revisi</button></div>
                                                </form>
                                            @endif
                                        </section>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div>
            <h3 class="font-bold text-slate-900">
                Subtugas
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Pembagian pekerjaan dalam tugas ini.
            </p>

            @php
                $isAssignee = $task->assignees->contains(auth()->id());
                $canManageSubtasks = $canEditTask && $isAssignee
                    && in_array($task->status, ['pending', 'in_progress', 'revision'], true);
            @endphp

            @if ($canManageSubtasks)
                <form
                    action="{{ route('employee.tasks.subtasks.store', ['task' => $task]) }}"
                    method="POST"
                    class="space-y-3 mt-3"
                >
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600">
                            Judul Subtugas
                        </label>
                        <input
                            type="text"
                            name="title"
                            required
                            class="mt-1 block w-full rounded-md border border-slate-300 bg-white py-2 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:border-red-600 focus:ring-red-600 sm:text-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600">
                            Deskripsi (opsional)
                        </label>
                        <textarea
                            name="description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border border-slate-300 bg-white py-2 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:border-red-600 focus:ring-red-600 sm:text-sm"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-md border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                    >
                        Buat Subtugas
                    </button>
                </form>
            @endif
        </div>

        @if ($task->subtasks->isEmpty())
            <div class="p-10 mt-6 text-center border border-dashed rounded-2xl border-slate-200">
                <p class="text-sm font-semibold text-slate-700">
                    Belum ada subtugas
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Subtugas yang dibuat anggota akan tampil di sini.
                </p>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                @foreach ($task->subtasks as $subtask)
                    <div x-data="{ showCompletionModal: @js($errors->any() && (string) old('subtask_id') === (string) $subtask->id), showDetailModal: false }" class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-start gap-3">
                            <div @class([
                                'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                                'bg-emerald-50 text-emerald-600' => $subtask->status === 'completed',
                                'bg-slate-100 text-slate-400' => $subtask->status !== 'completed',
                            ])>
                                @if ($subtask->status === 'completed')
                                    ✓
                                @else
                                    <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-sm font-bold text-slate-900">
                                        {{ $subtask->title }}
                                    </h4>

                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[10px] font-semibold',
                                        'bg-emerald-50 text-emerald-700' => $subtask->status === 'completed',
                                        'bg-slate-100 text-slate-500' => $subtask->status !== 'completed',
                                    ])>
                                        {{ $subtask->status === 'completed' ? 'Selesai' : 'Belum Selesai' }}
                                    </span>
                                </div>

                                <p class="mt-2 text-xs text-slate-500">Dibuat oleh {{ $subtask->creator?->name ?? '-' }}</p>
                                @if ($subtask->status === 'completed')
                                    <p class="mt-1 text-xs text-slate-500">Diselesaikan oleh {{ $subtask->completer?->name ?? '-' }}</p>
                                @endif

                                @if ($subtask->description)
                                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($subtask->description, 120) }}</p>
                                @endif

                                <button type="button" @click="showDetailModal = true" class="mt-3 text-xs font-semibold text-red-700 hover:text-red-800">Lihat detail</button>

                                @if ($canManageSubtasks)
                                    <details
                                        @if ((string) old('edit_subtask_id') === (string) $subtask->id) open @endif
                                        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3"
                                    >
                                        <summary class="cursor-pointer text-xs font-semibold text-red-700">
                                            Edit judul atau deskripsi
                                        </summary>
                                        <form action="{{ route('employee.tasks.subtasks.update', ['task' => $task, 'subtask' => $subtask]) }}" method="POST" class="mt-3 space-y-3">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="edit_subtask_id" value="{{ $subtask->id }}">

                                            <div>
                                                <label for="subtask-title-{{ $subtask->id }}" class="block text-xs font-semibold text-slate-600">Judul</label>
                                                <input id="subtask-title-{{ $subtask->id }}" name="title" type="text" required maxlength="255"
                                                    value="{{ (string) old('edit_subtask_id') === (string) $subtask->id ? old('title', $subtask->title) : $subtask->title }}"
                                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-red-600 focus:ring-red-600">
                                                @if ((string) old('edit_subtask_id') === (string) $subtask->id)
                                                    @error('title')
                                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror
                                                @endif
                                            </div>

                                            <div>
                                                <label for="subtask-description-{{ $subtask->id }}" class="block text-xs font-semibold text-slate-600">Deskripsi</label>
                                                <textarea id="subtask-description-{{ $subtask->id }}" name="description" rows="3"
                                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-red-600 focus:ring-red-600">{{ (string) old('edit_subtask_id') === (string) $subtask->id ? old('description', $subtask->description) : $subtask->description }}</textarea>
                                                @if ((string) old('edit_subtask_id') === (string) $subtask->id)
                                                    @error('description')
                                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror
                                                @endif
                                            </div>

                                            <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                                Simpan subtask
                                            </button>
                                        </form>
                                    </details>
                                @endif

                                <div x-cloak x-show="showDetailModal" x-transition.opacity style="display:none" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" @click.self="showDetailModal = false" @keydown.escape.window="showDetailModal = false">
                                    <section role="dialog" aria-modal="true" aria-labelledby="subtask-detail-title-{{ $subtask->id }}" class="my-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                                        <div class="flex items-start justify-between gap-4">
                                            <div><h3 id="subtask-detail-title-{{ $subtask->id }}" class="text-lg font-bold text-slate-900">{{ $subtask->title }}</h3><p class="mt-1 text-xs text-slate-500">Dibuat oleh {{ $subtask->creator?->name ?? '-' }}</p>
                                                @if ($subtask->status === 'completed')<p class="mt-1 text-xs text-slate-500">Diselesaikan oleh {{ $subtask->completer?->name ?? '-' }}</p>@endif
                                            </div>
                                            <button type="button" @click="showDetailModal = false" aria-label="Tutup detail" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">&times;</button>
                                        </div>
                                        <div class="mt-5 space-y-5 text-sm text-slate-600">
                                            @if ($subtask->description)<div><h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Deskripsi</h4><p class="mt-2 whitespace-pre-line leading-6">{{ $subtask->description }}</p></div>@endif
                                            @if ($subtask->completion_note)<div class="rounded-xl bg-slate-50 p-4"><h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Keterangan penyelesaian</h4><p class="mt-2 whitespace-pre-line leading-6">{{ $subtask->completion_note }}</p></div>@endif
                                            @if ($subtask->attachments->isNotEmpty())
                                            <div class="space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lampiran</p>
                                        @foreach ($subtask->attachments as $attachment)
                                            <div class="rounded-lg border border-slate-200 p-3">
                                                <a href="{{ route('employee.tasks.subtasks.attachments.download', ['task' => $task, 'subtask' => $subtask, 'attachment' => $attachment]) }}"
                                                    target="_blank" rel="noopener"
                                                    class="break-all text-xs font-medium text-red-700 hover:text-red-800">
                                                    {{ $attachment->original_name }}
                                                </a>

                                                @if ($canManageSubtasks && (int) $attachment->uploaded_by === (int) auth()->id())
                                                    <form action="{{ route('employee.tasks.subtasks.attachments.update', ['task' => $task, 'subtask' => $subtask, 'attachment' => $attachment]) }}" method="POST" enctype="multipart/form-data" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="min-w-0 flex-1">
                                                            <label for="replacement-attachment-{{ $attachment->id }}" class="block text-xs font-semibold text-slate-600">Pilih file pengganti</label>
                                                            <input id="replacement-attachment-{{ $attachment->id }}" type="file" name="attachment" required
                                                                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip"
                                                                class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                                                            @error('attachment')
                                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                            @enderror
                                                        </div>
                                                        <button type="submit" class="shrink-0 rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                            Ganti lampiran
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                            </div>
                                            @else
                                                <p class="text-sm text-slate-400">Belum ada lampiran.</p>
                                            @endif
                                        </div>
                                    </section>
                                </div>

                                @if ($canManageSubtasks && $subtask->status !== 'completed')
                                    <button type="button" @click="showCompletionModal = true" class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                                        Laporkan Penyelesaian
                                    </button>

                                    <div x-cloak x-show="showCompletionModal" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" @click.self="showCompletionModal = false" @keydown.escape.window="showCompletionModal = false">
                                        <div x-show="showCompletionModal" x-transition class="my-auto w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="completion-modal-title">
                                            <form action="{{ route('employee.tasks.subtasks.update', ['task' => $task, 'subtask' => $subtask]) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="completed">
                                                <input type="hidden" name="subtask_id" value="{{ $subtask->id }}">

                                                <div class="border-b border-slate-100 px-6 py-5">
                                                    <h3 id="completion-modal-title" class="text-lg font-bold text-slate-900">Laporkan Penyelesaian</h3>
                                                    <p class="mt-1 text-sm text-slate-500">{{ $subtask->title }}</p>
                                                </div>

                                                <div class="space-y-4 px-6 py-5">
                                                    <div>
                                                        <label for="completion-note-{{ $subtask->id }}" class="block text-sm font-semibold text-slate-700">Rincian yang sudah diselesaikan</label>
                                                        <textarea id="completion-note-{{ $subtask->id }}" name="completion_note" rows="5" required maxlength="5000" class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm focus:border-red-600 focus:ring-red-600" placeholder="Jelaskan pekerjaan yang sudah diselesaikan secara rinci...">{{ old('completion_note') }}</textarea>
                                                        @error('completion_note')
                                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label for="completion-files-{{ $subtask->id }}" class="block text-sm font-semibold text-slate-700">Bukti penyelesaian <span class="font-normal text-slate-400">(wajib, maks. 5 file, 10 MB per file)</span></label>
                                                        <p class="mt-1 text-xs leading-5 text-slate-500">Format yang diterima: JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV, dan ZIP.</p>
                                                        <input id="completion-files-{{ $subtask->id }}" type="file" name="attachments[]" required multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600 file:mr-3 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-3 file:py-2.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                                                        @error('attachments')
                                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                        @enderror
                                                        @error('attachments.*')
                                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                                                    <button type="button" @click="showCompletionModal = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                                                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Kirim Penyelesaian</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
        @endif
    </div>
</div>
@endsection
