<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdviserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DefenseScheduleController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TitleSubmissionController;
use App\Http\Controllers\SuggestedAIController;

Route::get('/', function () {
    return view('papertrail-landing');
})->name('home');

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/terms', function () {
    return view('terms');
})->name('terms');

Route::get('/registration-success', function () {
    return view('registration-success');
})->name('registration.success');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

// Admin Dashboard
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->middleware(['auth'])->name('admin.dashboard');

// Teacher Dashboard
Route::get('/teacher/dashboard', function () {
    return view('teacher.dashboard');
})->middleware(['auth'])->name('teacher.dashboard');

// Test route to check authentication
Route::get('/auth-test', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return response()->json([
            'authenticated' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status
            ]
        ]);
    }
    return response()->json(['authenticated' => false]);
});

// Test dashboard without middleware
Route::get('/dashboard-test', function () {
    if (Auth::check()) {
        return view('dashboard');
    }
    return 'Not authenticated';
});

// Direct login test
Route::get('/login-test', function () {
    $user = \App\Models\User::where('email', 'student@papertrail.com')->first();
    if ($user) {
        Auth::login($user);
        return redirect(route('dashboard'));
    }
    return 'User not found';
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile-picture/{user}', [ProfileController::class, 'picture'])->name('profile.picture');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications', function () {
        $user = Auth::user();

        $chatNotifications = $user->chatRooms()
            ->where('chat_rooms.is_active', true)
            ->with(['latestMessage.user', 'project'])
            ->get()
            ->map(function ($room) use ($user) {
                $unreadCount = $room->getUnreadCountForUser($user);
                $latestMessage = $room->latestMessage->first();

                return [
                    'room' => $room,
                    'unread_count' => $unreadCount,
                    'latest_message' => $latestMessage,
                ];
            })
            ->filter(fn ($notification) => $notification['unread_count'] > 0)
            ->sortByDesc(fn ($notification) => optional($notification['latest_message'])->created_at)
            ->values();

        return view('notifications.index', compact('chatNotifications'));
    })->name('notifications.index');
    
    // Adviser routes
    Route::get('/advisers/title-submission', [AdviserController::class, 'TitleSubmission'])->name('advisers.title-submission');
    Route::post('/advisers/send-request', [AdviserController::class, 'sendRequest'])->name('advisers.send-request');
    Route::get('/advisers/pending-requests', [AdviserController::class, 'pendingRequests'])->name('advisers.pending-requests');
    Route::post('/advisers/respond/{adviserStudent}', [AdviserController::class, 'respondToRequest'])->name('advisers.respond');
    Route::patch('/advisers/{adviserStudent}/archive', [AdviserController::class, 'archiveStudentGroup'])->name('advisers.archive');
    Route::delete('/advisers/{adviserStudent}', [AdviserController::class, 'releaseAdviser'])->name('advisers.release');
    Route::get('/suggested-ai', fn () => redirect()->route('advisers.title-submission'));
    Route::post('/suggested-ai', [SuggestedAIController::class, 'index'])->name('suggested-ai');
    Route::post('/title-submission',[TitleSubmissionController::class, 'store'])->name('title-submission.store');
    Route::get('/my-advisers', [AdviserController::class, 'myAdvisers'])->name('advisers.my-advisers');
    Route::get('/my-students', [AdviserController::class, 'myStudents'])->name('advisers.my-students');

    // Leader group management
    Route::get('/group-description', [GroupController::class, 'show'])->name('group-description.show');
    Route::get('/group-description/{project}', [GroupController::class, 'details'])->name('group-description.details');
    Route::patch('/group-description', [GroupController::class, 'update'])->name('group-description.update');
    Route::post('/group-description/share-link', [GroupController::class, 'shareLink'])->name('group-description.share-link');
    Route::delete('/group-description/members/{member}', [GroupController::class, 'removeMember'])->name('group-description.members.remove');
    
    // Admin routes
    Route::get('/admin/pending-users', [AdminController::class, 'pendingUsers'])->name('admin.pending-users');
    Route::get('/admin/users/{user}', [AdminController::class, 'viewUser'])->name('admin.view-user');
    Route::post('/admin/users/{user}/verify', [AdminController::class, 'verifyUser'])->name('admin.verify-user');
    Route::post('/admin/users/{user}/reject', [AdminController::class, 'rejectUser'])->name('admin.reject-user');
    Route::get('/admin/users/{user}/document', [AdminController::class, 'viewDocument'])->name('admin.view-document');
    Route::get('/admin/all-users', [AdminController::class, 'allUsers'])->name('admin.all-users');
    Route::post('/admin/users/{user}/update-role', [AdminController::class, 'updateUserRole'])->name('admin.update-user-role');
    Route::post('/admin/users/{user}/update-status', [AdminController::class, 'updateUserStatus'])->name('admin.update-user-status');
    Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.delete-user');

    // Project routes
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/invitations', [ProjectController::class, 'generateInvitation'])->name('projects.invitations.generate');
    Route::get('/project-invitations/{token}', [ProjectController::class, 'acceptInvitation'])->name('projects.accept-invitation');
    Route::post('/projects/{project}/folders', [ProjectController::class, 'createFolder'])->name('projects.create-folder');
    Route::post('/projects/{project}/upload', [ProjectController::class, 'uploadDocuments'])->name('projects.upload-documents');
    Route::get('/projects/{project}/documents/{document}/preview', [ProjectController::class, 'previewDocument'])->name('projects.preview-document');
    Route::get('/projects/{project}/documents/{document}/download', [ProjectController::class, 'downloadDocument'])->name('projects.download-document');
    Route::delete('/projects/{project}/documents/{document}', [ProjectController::class, 'deleteDocument'])->name('projects.delete-document');
    Route::delete('/projects/{project}/folders/{folder}', [ProjectController::class, 'deleteFolder'])->name('projects.delete-folder');
    
    // Defense Schedule routes
    Route::resource('defense-schedule', DefenseScheduleController::class);
    Route::get('/defense-schedule-events', [DefenseScheduleController::class, 'getEvents'])->name('defense-schedule.events');
    Route::get('/students/{student}/projects', [DefenseScheduleController::class, 'getStudentProjects'])->name('students.projects');
    Route::post('/defense-schedule/{defenseSchedule}/create-google-meet', [DefenseScheduleController::class, 'createGoogleMeet'])->name('defense-schedule.create-google-meet');
    Route::post('/defense-schedule/{defenseSchedule}/update-google-meet', [DefenseScheduleController::class, 'updateGoogleMeet'])->name('defense-schedule.update-google-meet');
    Route::get('/setup-google-auth', [DefenseScheduleController::class, 'setupGoogleAuth'])->name('setup-google-auth');
    Route::get('/auth/google/callback', [DefenseScheduleController::class, 'handleGoogleCallback'])->name('google-auth-callback');
    
    // Chat routes
    Route::prefix('chat')->name('chat.')->middleware('auth')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/files/{message}', [ChatController::class, 'showFile'])->name('files.show');
        Route::post('/rooms', [ChatController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{chatRoom}', [ChatController::class, 'show'])->name('rooms.show');
        Route::get('/rooms/{chatRoom}/messages', [ChatController::class, 'getMessages'])->name('rooms.messages');
        Route::post('/rooms/{chatRoom}/messages', [ChatController::class, 'sendMessage'])->name('rooms.send-message');
        Route::post('/projects/{project}/chat', [ChatController::class, 'createProjectChat'])->name('project.create');
        
        // Enhanced chat features
        Route::post('/rooms/{chatRoom}/participants', [ChatController::class, 'addParticipants'])->name('rooms.add-participants');
        Route::delete('/rooms/{chatRoom}/participants', [ChatController::class, 'removeParticipant'])->name('rooms.remove-participant');
        Route::get('/rooms/{chatRoom}/available-users', [ChatController::class, 'getAvailableUsers'])->name('rooms.available-users');
        Route::delete('/rooms/{chatRoom}/messages/{message}', [ChatController::class, 'deleteMessage'])->name('rooms.delete-message');
        Route::post('/rooms/{chatRoom}/messages/{message}/pin', [ChatController::class, 'togglePin'])->name('rooms.messages.toggle-pin');
        Route::post('/rooms/{chatRoom}/messages/seen', [ChatController::class, 'markAsSeen'])->name('rooms.mark-seen');
        
        // New features
        Route::post('/rooms/{chatRoom}/leave', [ChatController::class, 'leaveChatRoom'])->name('rooms.leave');
        Route::delete('/rooms/{chatRoom}', [ChatController::class, 'deleteChatRoom'])->name('rooms.delete');
        Route::post('/rooms/{chatRoom}/typing', [ChatController::class, 'updateTypingStatus'])->name('rooms.typing');
        Route::get('/rooms/{chatRoom}/typing', [ChatController::class, 'getTypingUsers'])->name('rooms.get-typing');
        
        // Emoji reactions
        Route::post('/rooms/{chatRoom}/messages/{message}/reactions', [ChatController::class, 'toggleReaction'])->name('rooms.messages.toggle-reaction');
        Route::get('/rooms/{chatRoom}/messages/{message}/reactions', [ChatController::class, 'getMessageReactions'])->name('rooms.messages.get-reactions');
    });
    
    // Test route for chat system
    Route::get('/chat-test', function () {
        return response()->json([
            'status' => 'Chat system is working!',
            'controller_exists' => class_exists('App\Http\Controllers\ChatController'),
            'service_exists' => class_exists('App\Services\GoogleChatService'),
            'chat_route' => route('chat.index'),
            'auth_user' => auth()->check() ? auth()->user()->id : 'Not authenticated',
            'users_count' => \App\Models\User::count(),
            'chat_rooms_count' => \App\Models\ChatRoom::count()
        ]);
    })->name('chat.test');
    
    // Test route for creating a simple chat room
    Route::post('/chat-test-create', function () {
        try {
            $service = new \App\Services\GoogleChatService();
            $users = \App\Models\User::limit(2)->get();
            
            if ($users->count() < 1) {
                return response()->json(['error' => 'No users found for testing']);
            }
            
            $chatRoom = $service->createChatRoom(
                'Test Chat Room',
                'This is a test chat room',
                $users->toArray()
            );
            
            return response()->json([
                'success' => $chatRoom ? true : false,
                'chat_room_id' => $chatRoom ? $chatRoom->id : null,
                'participants_count' => $chatRoom ? $chatRoom->participants()->count() : 0
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('chat.test.create');
    
    // Debug route for specific chat room
    Route::get('/chat-debug/{id}', function ($id) {
        try {
            $chatRoom = \App\Models\ChatRoom::find($id);
            if (!$chatRoom) {
                return response()->json(['error' => 'Chat room not found', 'id' => $id]);
            }
            
            $user = auth()->user();
            return response()->json([
                'chat_room' => [
                    'id' => $chatRoom->id,
                    'name' => $chatRoom->name,
                    'participants_count' => $chatRoom->participants()->count(),
                    'messages_count' => $chatRoom->messages()->count(),
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->firstname . ' ' . $user->lastname,
                    'is_participant' => $chatRoom->hasParticipant($user),
                ],
                'participants' => $chatRoom->participants()->get(['id', 'firstname', 'lastname'])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('chat.debug');
    
    // Test messages route
    Route::get('/test-messages/{id}', function ($id) {
        try {
            $chatRoom = \App\Models\ChatRoom::findOrFail($id);
            $user = auth()->user();
            
            // Test the pivot update
            $chatRoom->participants()
                     ->wherePivot('user_id', $user->id)
                     ->updateExistingPivot($user->id, ['last_read_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pivot update successful',
                'chat_room' => $chatRoom->name,
                'user' => $user->firstname . ' ' . $user->lastname
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('test.messages');
    
    // Debug route for available users
    Route::get('/debug-users/{roomId}', function ($roomId) {
        try {
            $chatRoom = \App\Models\ChatRoom::findOrFail($roomId);
            $existingParticipantIds = $chatRoom->participants()->pluck('users.id')->toArray();
            $allUsers = \App\Models\User::where('status', 'Verified')->get(['id', 'firstname', 'lastname', 'email', 'role']);
            $availableUsers = \App\Models\User::whereNotIn('id', $existingParticipantIds)
                                             ->where('status', 'Verified')
                                             ->get(['id', 'firstname', 'lastname', 'email', 'role']);
            
            return response()->json([
                'chat_room_id' => $roomId,
                'existing_participant_ids' => $existingParticipantIds,
                'all_verified_users_count' => $allUsers->count(),
                'all_verified_users' => $allUsers->toArray(),
                'available_users_count' => $availableUsers->count(),
                'available_users' => $availableUsers->toArray()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->middleware('auth')->name('debug.users');
});

require __DIR__.'/auth.php';
