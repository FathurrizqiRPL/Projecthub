<div>
    <div class="mb-6 grid gap-3 lg:grid-cols-[1fr_200px_180px]">
        <div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama, username, atau email..." class="block w-full rounded-xl border-slate-300 bg-[#fffdfa] text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <select wire:model.live="role" class="rounded-xl border-slate-300 bg-[#fffdfa] text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            <option value="">Semua Role</option>
            <option value="admin">Administrator</option>
            <option value="project_manager">Project Manager</option>
            <option value="employee">Karyawan</option>
        </select>

        <select wire:model.live="status" class="rounded-xl border-slate-300 bg-[#fffdfa] text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-[#fffdfa] shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Pengguna</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Username</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Role</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-600">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-slate-400">{{ $user->email ?: 'Email belum diisi' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                {{ $user->username }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-600">
                                    {{ $user->role === 'admin' ? 'Administrator' : ($user->role === 'project_manager' ? 'Project Manager' : 'Karyawan') }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="text-sm font-semibold text-red-600 transition hover:text-red-700">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm font-medium text-slate-500">Pengguna tidak ditemukan.</p>
                                <p class="mt-1 text-xs text-slate-400">Coba ubah kata pencarian atau filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
