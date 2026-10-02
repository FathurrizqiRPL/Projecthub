@extends('layouts.project-manager')

@section('title', 'Atur Workflow')
@section('header', 'Atur Workflow')

@section('content')

<div class="mx-auto max-w-3xl space-y-6">

    <a
        href="{{ route(
            'project-manager.projects.show',
            $project
        ) }}"
        wire:navigate
        class="inline-flex items-center gap-2
               text-sm font-semibold text-slate-500
               transition hover:text-red-600"
    >
        <svg
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M15 19l-7-7 7-7"
            />
        </svg>

        Kembali ke Project
    </a>


    <div
        class="rounded-2xl border border-slate-200
               bg-[#fffdfa] p-6 shadow-sm lg:p-8"
    >

        <div>
            <h2 class="text-xl font-bold text-slate-900">
                Workflow {{ $project->name }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Atur urutan tahapan project.
            </p>
        </div>


        @if ($project->status === 'active')

            <div
                class="mt-5 rounded-xl border
                       border-amber-200 bg-amber-50
                       px-4 py-3 text-sm text-amber-700"
            >
                Project sedang aktif. Stage lama tidak dapat
                dihapus, tetapi Anda masih dapat mengganti nama
                atau menambahkan stage baru.
            </div>

        @endif


        <form
            method="POST"
            action="{{ route(
                'project-manager.projects.workflow.update',
                $project
            ) }}"
            class="mt-7"
            x-data="{
                stages: @js(
                    $project->stages
                        ->map(fn ($stage) => [
                            'id' => $stage->id,
                            'name' => $stage->name,
                        ])
                        ->values()
                )
            }"
        >
            @csrf
            @method('PUT')


            <div class="space-y-3">

                <template
                    x-for="(stage, index) in stages"
                    :key="stage.id ?? `new-${index}`"
                >

                    <div
                        class="flex items-center gap-3
                               rounded-xl border
                               border-slate-200 bg-slate-50 p-3"
                    >

                        <div
                            class="flex h-10 w-10 shrink-0
                                   items-center justify-center
                                   rounded-xl bg-white text-sm
                                   font-bold text-red-600"
                            x-text="index + 1"
                        ></div>


                        <input
                            x-show="stage.id"
                            type="hidden"
                            :name="`stages[${index}][id]`"
                            :value="stage.id"
                        >


                        <input
                            type="text"
                            x-model="stage.name"
                            :name="`stages[${index}][name]`"
                            class="w-full rounded-xl
                                   border-slate-300
                                   focus:border-red-500
                                   focus:ring-red-500"
                            required
                        >


                        @if ($project->status === 'draft')

                            <button
                                type="button"
                                x-show="stages.length > 1"
                                x-on:click="stages.splice(index, 1)"
                                class="rounded-xl border
                                       border-red-200 px-3 py-2
                                       text-sm font-semibold
                                       text-red-600
                                       hover:bg-red-50"
                            >
                                Hapus
                            </button>

                        @endif

                    </div>

                </template>

            </div>


            <button
                type="button"
                x-on:click="
                    stages.push({
                        id: null,
                        name: ''
                    })
                "
                class="mt-4 rounded-xl border
                       border-slate-200 px-4 py-2
                       text-sm font-semibold text-slate-600
                       transition hover:bg-slate-50"
            >
                + Tambah Tahap
            </button>


            @error('stages')
                <p class="mt-3 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror

            @error('stages.*.name')
                <p class="mt-3 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror


            <div
                class="mt-8 flex justify-end gap-3
                       border-t border-slate-100 pt-6"
            >

                <a
                    href="{{ route(
                        'project-manager.projects.show',
                        $project
                    ) }}"
                    wire:navigate
                    class="rounded-xl border
                           border-slate-200 px-4 py-2.5
                           text-sm font-semibold text-slate-600"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-xl bg-red-600
                           px-5 py-2.5 text-sm
                           font-semibold text-white
                           transition hover:bg-red-700"
                >
                    Simpan Workflow
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
