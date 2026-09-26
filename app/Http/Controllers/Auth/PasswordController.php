<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return back();
    }

    /**
     * Show the mandatory password change page for first-time login.
     */
    public function showForceChange(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Auth/ForceChangePassword');
    }

    /**
     * Handle the mandatory password change on first-time login.
     */
    public function updateForceChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        \App\Services\ActionLogService::log(
            action: 'password_change',
            module: 'auth',
            description: "User '{$user->name}' updated their initial temporary password.",
            garageId: $user->garage_id,
            userId: $user->id
        );

        return redirect()->route('dashboard')->with('success', 'Your password has been changed successfully. Welcome!');
    }
}
