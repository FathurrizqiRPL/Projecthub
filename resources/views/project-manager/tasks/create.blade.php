@extends('layouts.project-manager')

@section('title', 'Buat Task')
@section('header', 'Buat Task')

@section('content')
<div class="space-y-6">
    <a href="{{ route('project-manager.projects.tasks.index', $project) }}" wire:navigate
        class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke Task
    </a>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div class="mb-7">
            <p class="text-sm font-semibold text-red-600">{{ $project->name }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Buat Task Baru</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Tambahkan pekerjaan dan pilih anggota project yang akan mengerjakannya bersama.
            </p>
        </div>

        <form method="POST" action="{{ route('project-manager.projects.tasks.store', $project) }}" class="space-y-6">
            @csrf

            {{-- Judul --}}
            <div>
                <label for="title" class="text-sm font-semibold text-slate-700">Judul Task</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" required
                    placeholder="Contoh: Menambahkan fitur foto profile"
                    class="block w-full mt-2 text-sm bg-white shadow-sm rounded-xl border-slate-300 text-slate-700 focus:border-red-500 focus:ring-red-500">
                @error('title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div>
                <label for="description" class="text-sm font-semibold text-slate-700">Deskripsi</label>
                <textarea id="description" name="description" rows="5"
                    placeholder="Jelaskan pekerjaan yang perlu dilakukan..."
                    class="block w-full mt-2 text-sm bg-white shadow-sm resize-none rounded-xl border-slate-300 text-slate-700 focus:border-red-500 focus:ring-red-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Stage --}}
            <div>
                <label for="project_stage_id" class="text-sm font-semibold text-slate-700">Tahap Project</label>
                <select id="project_stage_id" name="project_stage_id" required
                    class="block w-full mt-2 text-sm bg-white shadow-sm rounded-xl border-slate-300 text-slate-700 focus:border-red-500 focus:ring-red-500">
                    <option value="">Pilih tahap project</option>
                    @foreach ($project->stages->sortBy('position') as $stage)
                        <option value="{{ $stage->id }}"
                            @selected(old('project_stage_id', request('stage') ?? $project->current_stage_id) == $stage->id)>
                            {{ $stage->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_stage_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Anggota Task --}}
            <div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Anggota Task</label>
                    <p class="mt-1 text-xs leading-5 text-slate-400">
                        Pilih satu atau beberapa anggota yang akan mengerjakan task ini bersama.
                    </p>
                </div>

                @if ($project->members->isEmpty())
                    <div class="p-5 mt-3 text-center border border-dashed rounded-xl border-slate-200">
                        <p class="text-sm font-semibold text-slate-600">Belum ada anggota project</p>
                        <p class="mt-1 text-xs text-slate-400">Tambahkan anggota ke project sebelum membuat task.</p>
                    </div>
                @else
                    <div class="grid gap-2 mt-3 sm:grid-cols-2">
                        @foreach ($project->members as $member)
                            <label class="flex items-center gap-3 p-3 transition bg-white border cursor-pointer rounded-xl border-slate-200 hover:border-red-200 hover:bg-red-50/40">
                                <input type="checkbox" name="assignees[]" value="{{ $member->id }}"
                                    @checked(in_array($member->id, old('assignees', [])))
                                    class="w-4 h-4 text-red-600 rounded shrink-0 border-slate-300 focus:ring-red-500">

                                <div class="flex items-center justify-center text-xs font-bold text-red-600 rounded-full h-9 w-9 shrink-0 bg-red-50">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold truncate text-slate-700">{{ $member->name }}</p>
                                    <p class="text-xs truncate text-slate-400">
                                        {{ $member->department ?: $member->email }}
                                    </p>
                                </div>
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

            {{-- Deadline --}}
            <div>
                <label for="deadline" class="text-sm font-semibold text-slate-700">Deadline</label>
                <input id="deadline" name="deadline" type="date" value="{{ old('deadline') }}"
                    @if ($project->start_date) min="{{ $project->start_date->format('Y-m-d') }}" @endif
                    @if ($project->deadline) max="{{ $project->deadline->format('Y-m-d') }}" @endif
                    class="block w-full mt-2 text-sm bg-white shadow-sm rounded-xl border-slate-300 text-slate-700 focus:border-red-500 focus:ring-red-500">
                <p class="mt-2 text-xs text-slate-400">
                    @if ($project->start_date && $project->deadline)
                        Rentang project: {{ $project->start_date->locale('id')->translatedFormat('d F Y') }} - {{ $project->deadline->locale('id')->translatedFormat('d F Y') }}.
                    @else
                        Deadline task bersifat opsional.
                    @endif
                </p>
                @error('deadline')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Action --}}
            <div class="flex flex-col-reverse gap-3 pt-6 border-t border-slate-100 sm:flex-row sm:justify-end">
                <a href="{{ route('project-manager.projects.tasks.index', $project) }}" wire:navigate
                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" @disabled($project->members->isEmpty())
                    class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300">
                    Buat Task
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
