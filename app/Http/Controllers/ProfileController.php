<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $photo = $validated['photo'] ?? null;
        unset($validated['photo']);

        $user->fill($validated);

        if ($photo !== null) {
            $newPhotoPath = $photo->storePublicly('profile-photos', 'public');

            if ($newPhotoPath === false) {
                throw new \RuntimeException('The profile photo could not be stored.');
            }

            $oldPhotoPath = $user->profile_photo;
            $user->profile_photo = $newPhotoPath;
        }

        $user->save();

        if (
            isset($oldPhotoPath)
            && str_starts_with($oldPhotoPath, 'profile-photos/')
            && ! Storage::disk('public')->delete($oldPhotoPath)
        ) {
            throw new \RuntimeException('The previous profile photo could not be deleted.');
        }

        return back()->with('status', 'profile-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $user->delete();

        return redirect()->route('login');
    }
}
