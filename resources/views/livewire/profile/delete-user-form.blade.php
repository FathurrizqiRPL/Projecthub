<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
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

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-semibold text-slate-900">
                {{ __('Apakah Anda yakin ingin menghapus akun?') }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ __('Setelah akun dihapus, seluruh sumber daya dan datanya akan dihapus secara permanen. Masukkan kata sandi untuk mengonfirmasi penghapusan akun.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Kata sandi') }}" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-slate-300 focus:border-red-500 focus:ring-red-500 sm:w-3/4"
                    placeholder="{{ __('Kata sandi') }}"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Batal') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Hapus Akun') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
