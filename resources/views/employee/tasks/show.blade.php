@extends('layouts.employee')

@section('title', 'Detail Task')
@section('header', 'Detail Task')

@section('content')
@php
    $progress = $totalSubtasks > 0
        ? (int) round(
            ($completedSubtasks / $totalSubtasks) * 100
        )
        : 0;

    $statusLabel = match ($task->status) {
        'pending' => 'Belum Dikerjakan',
        'in_progress' => 'Dikerjakan',
        'waiting_review' => 'Menunggu Review',
        'revision' => 'Revisi',
        'completed' => 'Selesai',
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

    <a href="{{ route('employee.tasks.index') }}"
        wire:navigate
        class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke Tugas
    </a>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-bold text-slate-900">
                {{ $task->title }}
            </h2>

            <span @class([
                'rounded-full border px-3 py-1 text-xs font-semibold',
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

        <div class="pt-6 border-t mt-7 border-slate-100">
            <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                Anggota Task
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
                    Progress Task
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Dihitung dari subtask yang telah selesai.
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
            {{ $totalSubtasks }} subtask selesai
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900">Aksi Task</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Progress 100% belum berarti task disetujui; PM perlu melakukan review.
                </p>
            </div>

            @if ($task->status === 'pending')
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
                        Ajukan untuk Review
                    </button>
                </form>
            @elseif ($task->status === 'in_progress')
                <p class="text-sm font-medium text-slate-500">
                    Selesaikan semua subtask untuk mengajukan review.
                </p>
            @elseif ($task->status === 'waiting_review')
                <p class="rounded-full bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-700">
                    Menunggu review Project Manager
                </p>
            @elseif ($task->status === 'revision')
                <p class="rounded-full bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700">
                    Tindak lanjuti item revisi di bawah
                </p>
            @elseif ($task->status === 'completed')
                <p class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">
                    Task telah disetujui
                </p>
            @endif
        </div>
    </div>

    @if ($task->revisionItems->isNotEmpty())
        <div class="rounded-2xl border border-orange-200 bg-[#fffdfa] p-6 shadow-sm">
            <h3 class="font-bold text-slate-900">Item Revisi</h3>
            <p class="mt-1 text-sm text-slate-500">
                Selesaikan semua item pada putaran aktif agar task dapat diajukan kembali.
            </p>

            <div class="mt-5 space-y-5">
                @foreach ($task->revisionItems->groupBy('cycle') as $cycle => $revisionItems)
                    <section>
                        <h4 class="text-sm font-semibold text-slate-700">
                            Putaran {{ $cycle }}
                        </h4>

                        <ul class="mt-2 space-y-3">
                            @foreach ($revisionItems as $revisionItem)
                                <li class="rounded-xl border border-slate-200 bg-white p-4">
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

                                    @if ($task->status === 'revision'
                                        && $revisionItem->cycle === $task->review_cycle
                                        && $revisionItem->status !== 'resolved')
                                        <form
                                            action="{{ route('employee.tasks.revisions.update', ['task' => $task, 'revisionItem' => $revisionItem]) }}"
                                            method="POST"
                                            class="flex flex-wrap items-center gap-3 mt-4"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <label for="revision-status-{{ $revisionItem->id }}" class="text-xs font-semibold text-slate-600">
                                                Status tindak lanjut
                                            </label>
                                            <select
                                                id="revision-status-{{ $revisionItem->id }}"
                                                name="status"
                                                class="rounded-md border border-slate-300 bg-white px-2 py-1 text-sm focus:border-red-600 focus:ring-red-600"
                                            >
                                                <option value="in_progress" {{ $revisionItem->status === 'in_progress' ? 'selected' : '' }}>
                                                    Sedang dikerjakan
                                                </option>
                                                <option value="resolved">Sudah diperbaiki</option>
                                            </select>
                                            <button
                                                type="submit"
                                                class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700"
                                            >
                                                Simpan
                                            </button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
        <div>
            <h3 class="font-bold text-slate-900">
                Subtask
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Pembagian pekerjaan dalam task ini.
            </p>

            @php
                $isAssignee = $task->assignees->contains(auth()->id());
            @endphp

            @if ($isAssignee && in_array($task->status, ['pending', 'in_progress', 'revision'], true))
                <form
                    action="{{ route('employee.tasks.subtasks.store', ['task' => $task]) }}"
                    method="POST"
                    class="space-y-3 mt-3"
                >
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600">
                            Judul Subtask
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
                        Buat Subtask
                    </button>
                </form>
            @endif
        </div>

        @if ($task->subtasks->isEmpty())
            <div class="p-10 mt-6 text-center border border-dashed rounded-2xl border-slate-200">
                <p class="text-sm font-semibold text-slate-700">
                    Belum ada subtask
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Subtask yang dibuat anggota akan tampil di sini.
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
                                    @endif
                                </div>

                                @if (in_array($task->status, ['pending', 'in_progress', 'revision'], true))
                                    <form
                                        action="{{ route('employee.tasks.subtasks.update', ['task' => $task, 'subtask' => $subtask]) }}"
                                        method="POST"
                                        class="mt-3 space-y-2 w-full max-w-xs"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <div class="flex items-center gap-3">
                                            <label class="text-xs font-semibold text-slate-600">
                                                Status:
                                            </label>
                                            <select
                                                name="status"
                                                class="border rounded-md px-2 py-1 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-600"
                                            >
                                                <option value="pending"
                                                    {{ $subtask->status === 'pending' ? 'selected' : '' }}>
                                                    Belum Selesai
                                                </option>
                                                <option value="completed"
                                                    {{ $subtask->status === 'completed' ? 'selected' : '' }}>
                                                    Selesai
                                                </option>
                                            </select>
                                        </div>

                                        @if ($subtask->status === 'completed')
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-600">
                                                    Catatan Penyelesaian (opsional)
                                                </label>
                                                <textarea
                                                    name="completion_note"
                                                    rows="2"
                                                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white py-2 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:border-red-600 focus:ring-red-600 sm:text-sm"
                                                >{{ $subtask->completion_note }}</textarea>
                                            </div>
                                        @else
                                            <div class="hidden">
                                                <input type="text" name="completion_note">
                                            </div>
                                        @endif

                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center gap-2 px-2 py-1 text-xs font-medium rounded-md border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                                        >
                                            Perbarui
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
        @endif
    </div>
</div>
@endsection
