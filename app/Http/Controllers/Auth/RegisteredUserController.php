<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'id_document_file' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'], // 10MB max
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:Student'], // Only allow Student
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ]);

        // Handle file upload
        $idDocumentPath = null;
        if ($request->hasFile('id_document_file')) {
            $file = $request->file('id_document_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $idDocumentPath = $file->storeAs('id_documents', $filename, 'public');
        }

        $user = User::create([
            'firstname' => $request->firstname,
            'middlename' => $request->middlename,
            'lastname' => $request->lastname,
            'campus' => $request->campus,
            'course' => $request->course,
            'section' => $request->section,
            'id_document_path' => $idDocumentPath,
            'status' => 'Pending', // Default status
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);
        $user->syncRoleProfile();

        event(new Registered($user));

        // Don't auto-login since account needs admin verification
        // Auth::login($user);

        return redirect()->route('registration.success');
    }
}
