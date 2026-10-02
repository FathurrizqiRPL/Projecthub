@extends('layouts.admin')

@section('title', 'Edit Pengguna')
@section('header', 'Edit Pengguna')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="text-sm font-medium text-slate-500 transition hover:text-red-600">
            ← Kembali ke Detail Pengguna
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-[#fffdfa] p-6 shadow-sm lg:p-8">
        <div class="mb-7">
            <h2 class="text-lg font-bold text-slate-900">Edit Data Pengguna</h2>
            <p class="mt-1 text-sm text-slate-500">Perbarui informasi akun {{ $user->name }}.</p>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Nama Lengkap" />
                    <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $user->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="username" value="Username" />
                    <x-text-input id="username" name="username" type="text" class="mt-2 block w-full" :value="old('username', $user->username)" required />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    <p class="mt-2 text-xs text-slate-400">Username digunakan untuk login.</p>
                </div>

                <div>
                    <x-input-label for="email" value="Email (Opsional)" />
                    <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $user->email)" placeholder="Belum diisi" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="role" value="Role" />

                    @if ($user->role === 'admin')
                        <input type="hidden" name="role" value="admin">

                        <div class="mt-2 flex h-[42px] items-center rounded-md border border-slate-200 bg-slate-100 px-3 text-sm font-medium text-slate-500">
                            Administrator
                        </div>
                    @else
                        <select id="role" name="role" required class="mt-2 block w-full rounded-md border-slate-300 bg-white text-slate-900 shadow-sm focus:border-red-500 focus:ring-red-500">
                            <option value="project_manager" @selected(old('role', $user->role) === 'project_manager')>Project Manager</option>
                            <option value="employee" @selected(old('role', $user->role) === 'employee')>Karyawan</option>
                        </select>
                    @endif

                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="department" value="Departemen" />
                    <x-text-input id="department" name="department" type="text" class="mt-2 block w-full" :value="old('department', $user->department)" />
                    <x-input-error :messages="$errors->get('department')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" value="Nomor Telepon" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-2 block w-full" :value="old('phone', $user->phone)" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="joined_at" value="Tanggal Bergabung" />
                    <x-text-input id="joined_at" name="joined_at" type="date" class="mt-2 block w-full" :value="old('joined_at', $user->joined_at?->format('Y-m-d'))" />
                    <x-input-error :messages="$errors->get('joined_at')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="skills" value="Keahlian" />
                    <x-text-input id="skills" name="skills" type="text" class="mt-2 block w-full" :value="old('skills', $user->skills)" />
                    <x-input-error :messages="$errors->get('skills')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
                <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Batal
                </a>

                <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
