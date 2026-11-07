@extends('layouts.project-manager')

@section('title', 'Buat Proyek')
@section('header', 'Buat Proyek')

@section('content')

<div class="mx-auto max-w-3xl">

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">

        <h2 class="text-xl font-bold text-slate-900">
            Proyek Baru
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Isi informasi dasar Proyek.
        </p>

        <form
            method="POST"
            action="{{ route('project-manager.projects.store') }}"
            class="mt-7 space-y-5"
        >
            @csrf

            @include('project-manager.projects.partials.form')

            <div class="flex justify-end gap-3 pt-4">

                <a
                    href="{{ route('project-manager.projects.index') }}"
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                >
                    Simpan Proyek
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
