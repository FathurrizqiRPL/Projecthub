@extends('layouts.project-manager')

@section('title', 'Edit tugas')
@section('header', 'Edit tugas')

@section('content')
<div class="space-y-6">
    <a href="{{ route('project-manager.projects.tasks.show', [$project, $task]) }}" wire:navigate
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-red-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke detail tugas
    </a>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div class="mb-7">
            <p class="text-sm font-semibold text-red-600">{{ $project->name }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Edit tugas</h2>
        </div>

        <form method="POST" action="{{ route('project-manager.projects.tasks.update', [$project, $task]) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="text-sm font-semibold text-slate-700">Judul tugas</label>
                <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}" required maxlength="255"
                    class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-red-500 focus:ring-red-500">
                @error('title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="text-sm font-semibold text-slate-700">Deskripsi</label>
                <textarea id="description" name="description" rows="5"
                    class="mt-2 block w-full resize-none rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-red-500 focus:ring-red-500">{{ old('description', $task->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="project_stage_id" class="text-sm font-semibold text-slate-700">Tahap proyek</label>
                <select id="project_stage_id" name="project_stage_id" required
                    class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-red-500 focus:ring-red-500">
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->id }}" @selected(old('project_stage_id', $task->project_stage_id) == $stage->id)>
                            {{ $stage->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_stage_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Anggota tugas</label>
                    <p class="mt-1 text-xs text-slate-400">Pilih satu atau beberapa anggota yang merupakan anggota proyek.</p>
                </div>

                @if ($project->members->isEmpty())
                    <p class="mt-3 rounded-xl border border-dashed border-slate-200 p-5 text-sm text-slate-500">
                        Belum ada anggota proyek untuk ditugaskan.
                    </p>
                @else
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($project->members as $member)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-red-200 hover:bg-red-50/40">
                                <input type="checkbox" name="assignees[]" value="{{ $member->id }}"
                                    @checked(in_array($member->id, old('assignees', $task->assignees->modelKeys())))
                                    class="h-4 w-4 shrink-0 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-xs font-bold text-red-600">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-700">{{ $member->name }}</span>
                                    <span class="block truncate text-xs text-slate-400">{{ $member->department ?: $member->email }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                @error('assignees')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('assignees.*')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="deadline" class="text-sm font-semibold text-slate-700">Tenggat</label>
                <input id="deadline" name="deadline" type="date" value="{{ old('deadline', $task->deadline?->format('Y-m-d')) }}"
                    @if ($project->start_date) min="{{ $project->start_date->format('Y-m-d') }}" @endif
                    @if ($project->deadline) max="{{ $project->deadline->format('Y-m-d') }}" @endif
                    class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 shadow-sm focus:border-red-500 focus:ring-red-500">
                <p class="mt-2 text-xs text-slate-400">Tenggat bersifat opsional dan harus berada dalam rentang tanggal proyek.</p>
                @error('deadline')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                <a href="{{ route('project-manager.projects.tasks.show', [$project, $task]) }}" wire:navigate
                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                    class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                    Simpan perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
