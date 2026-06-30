<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Serve profile pictures without depending on the public storage symlink.
     */
    public function picture(User $user)
    {
        abort_unless(Auth::check(), 403);
        abort_unless($user->profile_picture_path, 404);
        abort_unless(Storage::disk('public')->exists($user->profile_picture_path), 404);

        return response()->file(Storage::disk('public')->path($user->profile_picture_path));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $expertise = $validated['expertise'] ?? [];
        $customExpertise = collect(preg_split('/[\r\n,]+/', $validated['custom_expertise'] ?? ''))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique(fn ($item) => mb_strtolower($item))
            ->values()
            ->all();

        unset($validated['expertise']);
        unset($validated['custom_expertise']);
        unset($validated['profile_picture']);

        if ($request->hasFile('profile_picture')) {
            if ($request->user()->profile_picture_path) {
                Storage::disk('public')->delete($request->user()->profile_picture_path);
            }

            $validated['profile_picture_path'] = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if ($request->user()->isTeacher()) {
            $request->user()->expertise()->updateOrCreate(
                ['adviser_id' => $request->user()->id],
                [
                    'machine_learning' => in_array('Machine Learning', $expertise, true),
                    'ai_integration' => in_array('AI Integration', $expertise, true),
                    'cybersecurity' => in_array('Cybersecurity', $expertise, true),
                    'iot' => in_array('IoT', $expertise, true),
                    'cloud_computing' => in_array('Cloud Computing', $expertise, true),
                    'data_analytics' => in_array('Data Analytics', $expertise, true),
                    'web_development' => in_array('Web Development', $expertise, true),
                    'mobile_development' => in_array('Mobile Development', $expertise, true),
                    'database_systems' => in_array('Database Systems', $expertise, true),
                    'networking' => in_array('Networking', $expertise, true),
                    'custom_expertise' => $customExpertise,
                ]
            );
        }

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return Redirect::route('profile.edit')->with('error', 'Admin accounts cannot delete themselves.');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->profile_picture_path) {
            Storage::disk('public')->delete($user->profile_picture_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
