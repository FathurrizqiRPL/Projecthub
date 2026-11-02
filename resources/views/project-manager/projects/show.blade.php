@extends('layouts.project-manager')

@section('title', $project->name)
@section('header', 'Detail proyek')

@section('content')
<div class="space-y-6">

    {{-- Kembali --}}
    <a href="{{ route('project-manager.projects.index') }}" wire:navigate
        class="inline-flex items-center gap-2 text-sm font-semibold transition text-slate-500 hover:text-red-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke Proyek
    </a>

    {{-- Success --}}
    @if (session('success'))
        <div class="px-4 py-3 text-sm border rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Proyek Detail --}}
    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-wrap items-center min-w-0 gap-3">
                <h2 class="text-2xl font-bold text-slate-900">
                    {{ $project->name }}
                </h2>

                <span @class([
                    'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold',
                    'border-slate-200 bg-slate-100 text-slate-600' => $project->status === 'draft',
                    'border-blue-200 bg-blue-50 text-blue-700' => $project->status === 'active',
                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $project->status === 'completed',
                    'border-red-200 bg-red-50 text-red-700' => $project->status === 'cancelled',
                ])>
                    <span @class([
                        'mr-1.5 h-1.5 w-1.5 rounded-full',
                        'bg-slate-400' => $project->status === 'draft',
                        'bg-blue-500' => $project->status === 'active',
                        'bg-emerald-500' => $project->status === 'completed',
                        'bg-red-500' => $project->status === 'cancelled',
                    ])></span>

                    {{ match ($project->status) {
                        'draft' => 'Draf',
                        'active' => 'Aktif',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($project->status),
                    } }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('project-manager.projects.tasks.index', $project) }}" wire:navigate
                    class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                    Lihat tugas
                </a>

                <a href="{{ route('project-manager.projects.edit', $project) }}" wire:navigate
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Ubah Proyek
                </a>
            </div>
        </div>

        {{-- Deskripsi --}}
        <p class="w-full mt-6 text-sm leading-7 whitespace-pre-line text-slate-500">{{ $project->description ?: 'Tidak ada deskripsi.' }}</p>

        {{-- Dates --}}
        <div class="grid gap-4 mt-8 sm:grid-cols-2">
            <div class="p-4 rounded-xl bg-slate-50">
                <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                    Tanggal Mulai
                </p>
                <p class="mt-2 font-semibold text-slate-800">
                    {{ $project->start_date?->format('d M Y') ?? '-' }}
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50">
                <p class="text-xs font-semibold tracking-wide uppercase text-slate-400">
                    Tenggat
                </p>
                <p class="mt-2 font-semibold text-slate-800">
                    {{ $project->deadline?->format('d M Y') ?? '-' }}
                </p>
            </div>
        </div>

        <div class="mt-6 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Progres proyek</p>
                    <p class="mt-1 text-xs text-slate-500">Rata-rata Progres setiap tugas</p>
                </div>
                <span class="text-lg font-bold text-slate-900">{{ $projectProgresss }}%</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-red-600 transition-all" style="width: {{ $projectProgresss }}%"></div>
            </div>
        </div>
    </div>

    @if ($project->completionExceptions->isNotEmpty())
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
            <h3 class="font-bold text-amber-900">Tugas yang dikecualikan saat proyek ditutup</h3>
            <p class="mt-1 text-sm text-amber-700">Tugas ini belum selesai ketika PM menutup proyek. Catatan keputusan disimpan di sini.</p>

            <div class="mt-4 space-y-4">
                @foreach ($project->completionExceptions->groupBy('stage_name') as $stageName => $exceptions)
                    <div>
                        <h4 class="text-sm font-semibold text-amber-900">{{ $stageName }}</h4>
                        <ul class="mt-2 space-y-2">
                            @foreach ($exceptions as $exception)
                                <li class="rounded-xl border border-amber-200 bg-white p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">{{ $exception->task_title }}</p>
                                            <p class="mt-1 text-xs text-slate-500">Status saat proyek ditutup: {{ ucfirst(str_replace('_', ' ', $exception->task_status)) }}</p>
                                        </div>
                                        <span class="text-xs text-slate-400">
                                            Dicatat oleh {{ $exception->waivingManager?->name ?? 'PM' }}
                                            {{ $exception->created_at?->locale('id')->translatedFormat('d M Y, H:i') }}
                                        </span>
                                    </div>
                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $exception->reason }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Anggota & Alur Kerja --}}
    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Anggota --}}
        <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-slate-900">
                        Anggota proyek
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Karyawan yang tergabung dalam Proyek ini.
                    </p>
                </div>

                @if (!in_array($project->status, ['completed', 'cancelled']))
                    <a href="{{ route('project-manager.projects.members.edit', $project) }}" wire:navigate
                        class="px-3 py-2 text-xs font-semibold transition border rounded-lg shrink-0 border-slate-200 text-slate-600 hover:bg-slate-50">
                        Kelola anggota
                    </a>
                @endif
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($project->members as $member)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center justify-center text-xs font-bold text-red-600 rounded-full h-9 w-9 shrink-0 bg-red-50">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate text-slate-700">
                                {{ $member->name }}
                            </p>
                            <p class="text-xs truncate text-slate-400">
                                {{ $member->department ?: $member->email }}
                            </p>
                        </div>

                        <span @class([
                            'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold',
                            'border-emerald-200 bg-emerald-50 text-emerald-700' => $member->status === 'active',
                            'border-slate-200 bg-slate-100 text-slate-500' => $member->status !== 'active',
                        ])>
                            {{ $member->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                @empty
                    <div class="p-5 text-center border border-dashed rounded-xl border-slate-200">
                        <p class="text-sm font-semibold text-slate-600">
                            Belum ada anggota
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Tambahkan karyawan yang akan bekerja pada Proyek ini.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Alur Kerja --}}
        <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-slate-900">
                        Tahapan Proyek
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Posisi Proyek dalam Alur Kerja saat ini.
                    </p>
                </div>

                @if ($project->status !== 'completed')
                    <a href="{{ route('project-manager.projects.workflow.edit', $project) }}" wire:navigate
                        class="px-3 py-2 text-xs font-semibold transition border rounded-lg shrink-0 border-slate-200 text-slate-600 hover:bg-slate-50">
                        Atur Alur Kerja
                    </a>
                @endif
            </div>

            @if ($project->stages->isEmpty())
                <div class="p-5 mt-5 text-center border border-dashed rounded-xl border-slate-200">
                    <p class="text-sm font-semibold text-slate-600">
                        Belum ada Alur Kerja
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Tambahkan tahapan pengerjaan untuk Proyek ini.
                    </p>
                </div>
            @else
                <div class="mt-6 space-y-3">
                    @foreach ($project->stages as $stage)
                        @php
                            $currentPosition = $project->currentStage?->position;
                            $isCurrent = $project->current_stage_id === $stage->id;
                            $isCompleted = $currentPosition && $stage->position < $currentPosition;
                            $isUpcoming = !$isCurrent && !$isCompleted;
                        @endphp

                        <div @class([
                            'flex items-center gap-4 rounded-xl border p-4 transition',
                            'border-emerald-200 bg-emerald-50' => $isCompleted,
                            'border-red-200 bg-red-50 ring-1 ring-red-100' => $isCurrent,
                            'border-slate-200 bg-slate-50' => $isUpcoming,
                        ])>
                            <div @class([
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-emerald-600 text-white' => $isCompleted,
                                'bg-red-600 text-white' => $isCurrent,
                                'bg-white text-slate-400' => $isUpcoming,
                            ])>
                                @if ($isCompleted)
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/>
                                    </svg>
                                @else
                                    {{ $stage->position }}
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p @class([
                                    'text-sm font-semibold',
                                    'text-emerald-700' => $isCompleted,
                                    'text-red-700' => $isCurrent,
                                    'text-slate-600' => $isUpcoming,
                                ])>
                                    {{ $stage->name }}
                                </p>

                                <p @class([
                                    'mt-0.5 text-xs',
                                    'text-emerald-600' => $isCompleted,
                                    'text-red-500' => $isCurrent,
                                    'text-slate-400' => $isUpcoming,
                                ])>
                                    @if ($isCompleted)
                                        @if ($stage->completed_at)
                                            Selesai {{ $stage->completed_at->locale('id')->translatedFormat('d F Y') }}
                                        @else
                                            Selesai
                                        @endif
                                    @elseif ($isCurrent)
                                        Sedang berjalan
                                    @else
                                        Belum dimulai
                                    @endif
                                </p>
                            </div>

                            @if ($isCurrent)
                                <span class="rounded-full bg-red-600 px-2.5 py-1 text-[10px] font-semibold text-white">
                                    Fase Saat Ini
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Workflow Controls --}}
                @if ($project->status === 'draft')
                    <div class="px-4 py-3 mt-5 border rounded-xl border-amber-200 bg-amber-50">
                        <p class="text-xs leading-5 text-amber-700">
                            Proyek masih berstatus draf. Ubah status menjadi aktif untuk memulai alur kerja.
                        </p>
                    </div>

                @elseif ($project->status === 'active' && !$project->currentStage)
                    <form method="POST"
                        action="{{ route('project-manager.projects.progress.start', $project) }}"
                        class="mt-5">
                        @csrf

                        <button type="submit"
                            class="w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                            Mulai tahap {{ $project->stages->first()->name }}
                        </button>
                    </form>

                @elseif ($project->status === 'active' && $project->currentStage)
                    @php
                        $nextStage = $project->stages
                            ->where('position', '>', $project->currentStage->position)
                            ->sortBy('position')
                            ->first();
                    @endphp

                    @if ($nextStage && $currentStageTaskCount === 0)
                        <div class="px-4 py-3 mt-5 border rounded-xl border-amber-200 bg-amber-50">
                            <p class="text-sm font-semibold text-amber-700">
                                Belum ada tugas pada tahap ini.
                            </p>
                            <p class="mt-1 text-xs text-amber-600">
                                Tambahkan minimal satu tugas sebelum tahap dapat diselesaikan.
                            </p>
                        </div>
                    @elseif ($nextStage && $incompleteCurrentStageTaskCount > 0)
                        <div class="px-4 py-3 mt-5 border rounded-xl border-amber-200 bg-amber-50">
                            <p class="text-sm font-semibold text-amber-700">
                                {{ $incompleteCurrentStageTaskCount }} tugas belum selesai pada tahap ini.
                            </p>
                            <p class="mt-1 text-xs text-amber-600">
                                Selesaikan semua tugas sebelum melanjutkan Alur Kerja.
                            </p>
                        </div>
                    @elseif ($nextStage)
                        <form method="POST"
                            action="{{ route('project-manager.projects.progress.next', $project) }}"
                            class="mt-5"
                            onsubmit="return confirm('Lanjutkan proyek ke tahap {{ $nextStage->name }}?')">
                            @csrf

                            <button type="submit"
                                class="w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                                Lanjut ke {{ $nextStage->name }}
                            </button>
                        </form>
                    @else
                        @if (! $hasMaintenanceStage)
                            <form method="POST"
                                action="{{ route('project-manager.projects.workflow.maintenance', $project) }}"
                                class="mt-5"
                                onsubmit="return confirm('Tambahkan tahap pemeliharaan ke alur kerja proyek?')">
                                @csrf
                                <button type="submit" class="w-full rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                    Tambah Tahap Pemeliharaan
                                </button>
                            </form>
                        @endif

                        @if ($projectTaskCount === 0)
                            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                <p class="text-sm font-semibold text-amber-700">Proyek belum memiliki tugas.</p>
                                <p class="mt-1 text-xs text-amber-600">Tambahkan minimal satu tugas sebelum menyelesaikan proyek.</p>
                            </div>
                        @elseif ($incompleteProjectTaskCount === 0)
                            <form method="POST"
                                action="{{ route('project-manager.projects.progress.complete', $project) }}"
                                class="mt-5"
                                onsubmit="return confirm('Selesaikan proyek {{ $project->name }}?')">
                                @csrf

                                <button type="submit"
                                    class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Selesaikan Proyek
                                </button>
                            </form>
                        @else
                            <div x-data="{ showCompletionModal: @js($errors->any() && old('completion_form') === '1') }" class="mt-5">
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-amber-700">
                                        {{ $incompleteProjectTaskCount }} tugas proyek belum selesai.
                                    </p>
                                    <p class="mt-1 text-xs text-amber-600">
                                        Periksa tugas berdasarkan tahap. Jika tugas memang tidak diperlukan, PM dapat mengecualikannya dengan alasan.
                                    </p>
                                </div>

                                <button type="button" @click="showCompletionModal = true" class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Tinjau dan Selesaikan Proyek
                                </button>

                                <div x-cloak x-show="showCompletionModal" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" @click.self="showCompletionModal = false" @keydown.escape.window="showCompletionModal = false">
                                    <div x-show="showCompletionModal" x-transition class="my-auto w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="project-completion-title">
                                        <form method="POST" action="{{ route('project-manager.projects.progress.complete', $project) }}">
                                            @csrf
                                            <input type="hidden" name="completion_form" value="1">

                                            <div class="border-b border-slate-100 px-6 py-5">
                                                <h3 id="project-completion-title" class="text-lg font-bold text-slate-900">Selesaikan proyek</h3>
                                                <p class="mt-1 text-sm text-slate-500">Berikut tugas yang belum selesai. Pastikan tugas yang dikecualikan memang tidak lagi diperlukan.</p>
                                            </div>

                                            <div class="max-h-[45vh] space-y-4 overflow-y-auto px-6 py-5">
                                                @foreach ($incompleteTasks->groupBy('project_stage_id') as $stageTasks)
                                                    <section>
                                                        <h4 class="mb-2 text-sm font-semibold text-slate-700">
                                                            {{ $stageTasks->first()->stage?->name ?? 'Tahap tidak tersedia' }}
                                                        </h4>
                                                        <ul class="space-y-2">
                                                            @foreach ($stageTasks as $incompleteTask)
                                                                <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                                                                    <input type="hidden" name="incomplete_task_ids[]" value="{{ $incompleteTask->id }}">
                                                                    <span class="min-w-0 truncate text-sm text-slate-700">{{ $incompleteTask->title }}</span>
                                                                    <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-800">
                                                                        {{ match ($incompleteTask->status) {
                                                                            'pending' => 'Belum Dikerjakan',
                                                                            'in_progress' => 'Dikerjakan',
                                                                            'waiting_review' => 'Menunggu peninjauan',
                                                                            'revision' => 'Revisi',
                                                                            default => ucfirst(str_replace('_', ' ', $incompleteTask->status)),
                                                                        } }}
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </section>
                                                @endforeach

                                                <label class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                                    <input type="checkbox" name="confirm_incomplete_tasks" value="1" required class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                                    <span>Saya memastikan semua tugas yang belum selesai di atas tidak lagi diperlukan. tugas tersebut akan dicatat sebagai pengecualian dan tidak dapat dilanjutkan setelah proyek ditutup.</span>
                                                </label>
                                                @error('confirm_incomplete_tasks')
                                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                                @enderror

                                                <div>
                                                    <label for="completion-reason" class="block text-sm font-semibold text-slate-700">Alasan pengecualian</label>
                                                    <textarea id="completion-reason" name="completion_reason" rows="3" required maxlength="1000" class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm focus:border-red-600 focus:ring-red-600" placeholder="Jelaskan mengapa tugas yang tersisa tidak perlu dilanjutkan...">{{ old('completion_reason') }}</textarea>
                                                    @error('completion_reason')
                                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                                                <button type="button" @click="showCompletionModal = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Kembali</button>
                                                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Konfirmasi Selesaikan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                @endif
            @endif
        </div>
    </div>

    {{-- Hapus Proyek --}}
    <div class="p-6 border border-red-100 rounded-2xl bg-red-50">
        <h3 class="font-semibold text-red-700">
            Hapus Proyek
        </h3>

        <p class="mt-1 text-sm text-red-600">
            Proyek yang dihapus tidak dapat dikembalikan.
        </p>

        <form method="POST"
            action="{{ route('project-manager.projects.destroy', $project) }}"
            class="mt-4"
            onsubmit="return confirm('Yakin ingin menghapus proyek ini?')">
            @csrf
            @method('Hapus')

            <button type="submit"
                class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                Hapus Proyek
            </button>
        </form>
    </div>

</div>
@endsection
