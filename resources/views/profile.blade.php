@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : (auth()->user()->role === 'project_manager' ? 'layouts.project-manager' : 'layouts.employee'))

@section('title', 'Profil')
@section('header', 'Profil')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('status') === 'profile-updated' || session('status') === 'password-updated')
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                Berhasil disimpan.
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
            <div class="max-w-2xl">
                <header>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ __('Informasi Profil') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('Perbarui informasi profil Anda. Alamat email hanya dapat diatur oleh administrator.') }}
                    </p>
                </header>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="flex items-center gap-4">
                        @if (auth()->user()->profile_photo)
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo) }}"
                                alt="Foto profil {{ auth()->user()->name }}"
                                class="h-20 w-20 rounded-full border border-slate-200 object-cover"
                            >
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-red-50 text-2xl font-semibold text-red-600">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif

                        <div>
                            <x-input-label for="photo" :value="__('Foto profil')" />
                            <input
                                id="photo"
                                name="photo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-red-50 file:px-4 file:py-2 file:font-semibold file:text-red-700 hover:file:bg-red-100"
                            >
                            <p class="mt-1 text-xs text-slate-500">Format JPG, PNG, atau WEBP; ukuran maksimal 2 MB.</p>
                            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Nama')" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500"
                            :value="old('name', auth()->user()->name)"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Alamat email')" />
                        <p id="email" class="mt-1 text-sm text-slate-600">
                            {{ auth()->user()->email ?: 'Belum diatur' }}
                        </p>
                    </div>

                    <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                </form>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
            <div class="max-w-2xl">
                <header>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ __('Perbarui kata sandi') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('Gunakan kata sandi yang panjang dan acak untuk menjaga keamanan akun Anda.') }}
                    </p>
                </header>

                <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="update_password_current_password" :value="__('Kata sandi saat ini')" />
                        <x-text-input
                            id="update_password_current_password"
                            name="current_password"
                            type="password"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500"
                            autocomplete="current-password"
                        />
                        <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="update_password_password" :value="__('Kata sandi baru')" />
                        <x-text-input
                            id="update_password_password"
                            name="password"
                            type="password"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500"
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="update_password_password_confirmation" :value="__('Konfirmasi kata sandi')" />
                        <x-text-input
                            id="update_password_password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500"
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                </form>
            </div>
        </section>

        @if (auth()->user()->role === 'admin')
            <section class="rounded-2xl border border-red-100 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
                <div class="max-w-2xl space-y-6">
                    <header>
                        <h2 class="text-lg font-semibold text-slate-900">
                            {{ __('Hapus Akun') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Setelah akun dihapus, seluruh sumber daya dan datanya akan dihapus secara permanen. Unduh terlebih dahulu data yang ingin Anda simpan.') }}
                        </p>
                    </header>

                    <x-danger-button
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                    >{{ __('Hapus Akun') }}</x-danger-button>

                    <x-modal name="confirm-user-deletion" :show="$errors->has('password')" focusable>
                        <form method="POST" action="{{ route('profile.destroy') }}" class="p-6">
                            @csrf
                            @method('DELETE')

                            <h2 class="text-lg font-semibold text-slate-900">
                                {{ __('Apakah Anda yakin ingin menghapus akun?') }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ __('Setelah akun dihapus, seluruh sumber daya dan datanya akan dihapus secara permanen. Masukkan kata sandi untuk mengonfirmasi penghapusan akun.') }}
                            </p>

                            <div class="mt-6">
                                <x-input-label for="delete_password" value="{{ __('Kata sandi') }}" class="sr-only" />
                                <x-text-input
                                    id="delete_password"
                                    name="password"
                                    type="password"
                                    class="mt-1 block w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500 sm:w-3/4"
                                    placeholder="{{ __('Kata sandi') }}"
                                />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="mt-6 flex justify-end">
                                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                    {{ __('Batal') }}
                                </x-secondary-button>
                                <x-danger-button class="ms-3">{{ __('Hapus Akun') }}</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            </section>
        @endif
    </div>
@endsection
