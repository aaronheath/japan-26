<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Models\User;
use App\Models\WhitelistedEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/users', [
            'users' => User::query()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'auth_type' => $user->google_id ? 'google' : 'password',
                ]),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['auth_type'] === 'google') {
            $this->createGoogleUser($validated);

            return back();
        }

        $generatedPassword = $this->createPasswordUser($validated);

        return back()->with('generated_password', $generatedPassword);
    }

    private function createGoogleUser(array $validated): User
    {
        $user = User::create([
            'name' => Str::before($validated['email'], '@'),
            'email' => strtolower($validated['email']),
            'password' => null,
            'email_verified_at' => now(),
        ]);

        if (! WhitelistedEmail::isWhitelisted($validated['email'])) {
            WhitelistedEmail::create([
                'email' => strtolower($validated['email']),
            ]);
        }

        return $user;
    }

    private function createPasswordUser(array $validated): string
    {
        $generatedPassword = Str::random(16);

        User::create([
            'name' => Str::before($validated['email'], '@'),
            'email' => strtolower($validated['email']),
            'password' => $generatedPassword,
            'email_verified_at' => now(),
        ]);

        return $generatedPassword;
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account from this page.',
            ]);
        }

        $user->delete();

        return back();
    }
}
