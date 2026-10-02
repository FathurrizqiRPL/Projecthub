@extends('layouts.project-manager')

@section('title', 'Kelola Anggota')
@section('header', 'Kelola Anggota')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <a href="{{ route('project-manager.projects.show', $project) }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke Project
    </a>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Anggota {{ $project->name }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Pilih karyawan yang akan tergabung dan mengerjakan tugas dalam project ini.</p>
        </div>

        <form method="POST" action="{{ route('project-manager.projects.members.update', $project) }}" class="mt-7">
            @csrf
            @method('PUT')

            @if ($employees->isEmpty())
                <div class="p-8 text-center border border-dashed rounded-2xl border-slate-200">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H2v-2a4 4 0 0 1 4-4h3m6 6v-2a6 6 0 0 0-12 0v2m9-10a4 4 0 1 0-8 0 4 4 0 0 0 8 0Zm8 0a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-700">Belum ada karyawan aktif</p>
                    <p class="mt-1 text-xs leading-5 text-slate-400">Karyawan aktif yang dibuat oleh Administrator akan muncul di sini.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($employees as $employee)
                        <label for="member-{{ $employee->id }}" class="flex items-center gap-4 p-4 transition border cursor-pointer rounded-2xl border-slate-200 hover:border-red-200 hover:bg-red-50/30">
                            <input
                                id="member-{{ $employee->id }}"
                                type="checkbox"
                                name="members[]"
                                value="{{ $employee->id }}"
                                @checked(in_array($employee->id, old('members', $selectedMembers)))
                                class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500"
                            >

                            <div class="flex items-center justify-center text-sm font-bold text-red-600 rounded-full h-11 w-11 shrink-0 bg-red-50">
                                {{ strtoupper(substr($employee->name, 0, 1)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold truncate text-slate-800">{{ $employee->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-400">{{ $employee->email }}</p>

                                @if ($employee->department)
                                    <p class="mt-1 text-xs text-slate-500">{{ $employee->department }}</p>
                                @endif
                            </div>

                            <span class="inline-flex shrink-0 items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                Aktif
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            @error('members')
                <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @error('members.*')
                <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex justify-end gap-3 pt-6 mt-8 border-t border-slate-100">
                <a href="{{ route('project-manager.projects.show', $project) }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Batal
                </a>

                @if ($employees->isNotEmpty())
                    <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                        Simpan Anggota
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
