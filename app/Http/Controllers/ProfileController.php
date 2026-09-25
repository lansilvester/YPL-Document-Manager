<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        return view('profile.edit', [
            'user' => $user,
            'pendingRequest' => \App\Models\RoleRequest::where('user_id', $user->id)->where('status', 'pending')->first(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_info' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = auth()->user();
        $user->name = $data['name'];
        $user->academic_info = $data['academic_info'] ?? null;
        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }
        $user->save();

        return back()->with('ok', 'Profil diperbarui.');
    }
}
