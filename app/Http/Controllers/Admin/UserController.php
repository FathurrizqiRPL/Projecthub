<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan halaman User Management.
     */
    public function index(): View
    {
        return view('admin.users.index');
    }

    /**
     * Tampilkan halaman tambah user.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                'unique:users,username',
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'role' => [
                'required',
                Rule::in([
                    'project_manager',
                    'employee',
                ]),
            ],
            'department' => [
                'nullable',
                'string',
                'max:255',
            ],
            'skills' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'joined_at' => [
                'nullable',
                'date',
            ],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',

            'username.required' => 'Username wajib diisi.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, tanda hubung, dan garis bawah.',
            'username.unique' => 'Username sudah digunakan.',

            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',

            'role.required' => 'Role wajib dipilih.',
        ]);

        $temporaryPassword = $this->generateTemporaryPassword();

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'] ?: null,
            'role' => $validated['role'],
            'department' => $validated['department'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'joined_at' => $validated['joined_at'] ?? null,
            'password' => $temporaryPassword,
            'status' => 'active',
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with(
                'success',
                'Akun pengguna berhasil dibuat.'
            )
            ->with(
                'credentials',
                $this->credentials(
                    $user,
                    $temporaryPassword
                )
            );
    }

    /**
     * Tampilkan detail user.
     */
    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Tampilkan halaman edit user.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update data user.
     */
    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Administrator Protection
        |--------------------------------------------------------------------------
        |
        | Administrator boleh mengubah informasi profil,
        | tetapi role Administrator tidak boleh diubah
        | melalui User Management.
        |
        */

        $allowedRoles = $user->role === 'admin'
            ? ['admin']
            : [
                'project_manager',
                'employee',
            ];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique(
                    'users',
                    'username'
                )->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore($user->id),
            ],
            'role' => [
                'required',
                Rule::in($allowedRoles),
            ],
            'department' => [
                'nullable',
                'string',
                'max:255',
            ],
            'skills' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'joined_at' => [
                'nullable',
                'date',
            ],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',

            'username.required' => 'Username wajib diisi.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, tanda hubung, dan garis bawah.',
            'username.unique' => 'Username sudah digunakan.',

            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',

            'role.required' => 'Role wajib dipilih.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'] ?: null,
            'role' => $validated['role'],
            'department' => $validated['department'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'joined_at' => $validated['joined_at'] ?? null,
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with(
                'success',
                'Data pengguna berhasil diperbarui.'
            );
    }

    /**
     * Aktifkan / nonaktifkan akun user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Administrator Protection
        |--------------------------------------------------------------------------
        |
        | Akun Administrator tidak boleh dinonaktifkan
        | melalui User Management.
        |
        */

        if ($user->role === 'admin') {
            return redirect()
                ->route('admin.users.show', $user)
                ->with(
                    'error',
                    'Akun Administrator tidak dapat dinonaktifkan.'
                );
        }

        $newStatus = $user->status === 'active'
            ? 'inactive'
            : 'active';

        $user->update([
            'status' => $newStatus,
        ]);

        $message = $newStatus === 'active'
            ? 'Akun pengguna berhasil diaktifkan.'
            : 'Akun pengguna berhasil dinonaktifkan.';

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', $message);
    }

    /**
     * Reset password user.
     */
    public function resetPassword(
        User $user
    ): RedirectResponse {
        if ($user->role === 'admin') {
            return redirect()
                ->route('admin.users.show', $user)
                ->with(
                    'error',
                    'Password Administrator tidak dapat direset melalui User Management.'
                );
        }

        $temporaryPassword = $this->generateTemporaryPassword();

        $user->update([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with(
                'success',
                'Password sementara baru berhasil dibuat.'
            )
            ->with(
                'credentials',
                $this->credentials(
                    $user,
                    $temporaryPassword
                )
            );
    }

    /**
     * Generate password sementara.
     */
    private function generateTemporaryPassword(): string
    {
        return 'PHub-'
            .Str::upper(Str::random(4))
            .random_int(1000, 9999);
    }

    /**
     * Data kredensial untuk ditampilkan sekali.
     */
    private function credentials(
        User $user,
        string $password
    ): array {
        return [
            'name' => $user->name,
            'username' => $user->username,
            'password' => $password,
        ];
    }
}
