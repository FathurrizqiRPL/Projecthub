@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : (auth()->user()->role === 'project_manager' ? 'layouts.project-manager' : 'layouts.employee'))

@section('title', 'Profil')
@section('header', 'Profil')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
            <div class="max-w-2xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
            <div class="max-w-2xl">
                <livewire:profile.update-password-form />
            </div>
        </section>

        @if (auth()->user()->role === 'admin')
            <section class="rounded-2xl border border-red-100 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
                <div class="max-w-2xl">
                    <livewire:profile.delete-user-form />
                </div>
            </section>
        @endif
    </div>
@endsection
