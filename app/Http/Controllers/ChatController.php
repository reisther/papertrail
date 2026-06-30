<?php

namespace App\Http\Controllers;

use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\MessageReaction;
use App\Models\Project;
use App\Models\User;
use App\Services\GoogleChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatController extends BaseController
{
    protected ?GoogleChatService $googleChatService = null;


    /**
     * Get or create GoogleChatService instance
     */
    private function getGoogleChatService(): GoogleChatService
    {
        if (!$this->googleChatService) {
            $this->googleChatService = new GoogleChatService();
        }
        return $this->googleChatService;
    }

    /**
     * Display chat interface
     */
    public function index()
    {
        $user = Auth::user();

        $accessibleProjects = $user->accessibleProjects()
            ->with(['owner', 'adviser', 'members'])
            ->get();

        $accessibleProjects->each(fn (Project $project) => $this->ensureProjectChatRoom($project));

        ChatRoom::whereIn('project_id', $accessibleProjects->pluck('id'))
            ->where('type', 'project')
            ->with(['project.owner', 'project.adviser', 'project.members'])
            ->get()
            ->each(fn (ChatRoom $room) => $this->syncProjectChatParticipants($room));
        
        $accessibleProjectIds = $accessibleProjects->pluck('id');

        // Include participant rooms, rooms the user created, and rooms attached to projects they can access.
        $chatRooms = ChatRoom::query()
            ->where('is_active', true)
            ->where(function ($rooms) use ($user, $accessibleProjectIds) {
                $rooms->where('created_by', $user->id)
                    ->orWhereHas('participants', function ($participants) use ($user) {
                        $participants->where('users.id', $user->id);
                    })
                    ->when($accessibleProjectIds->isNotEmpty(), function ($rooms) use ($accessibleProjectIds) {
                        $rooms->orWhereIn('project_id', $accessibleProjectIds);
                    });
            })
            ->with(['project', 'latestMessage.user'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->unique('id')
            ->values()
            ->map(function ($room) use ($user) {
                if ($room->type === 'project' && $room->project) {
                    $this->syncProjectChatParticipants($room);
                } elseif (!$room->hasParticipant($user)) {
                    $room->addParticipant($user, $room->created_by === $user->id ? 'admin' : 'member');
                }

                $room->unread_count = $room->getUnreadCountForUser($user);
                return $room;
            });

        $availableProjects = $accessibleProjects
            ->filter(fn (Project $project) => $this->canCreateProjectChatRoom($project, $user))
            ->values();

        return view('chat.index', compact('chatRooms', 'availableProjects'));
    }

    /**
     * Create a new chat room
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canLeadGroup() && !$user->isTeacher() && !$user->isAdmin()) {
            return $this->chatRoomCreateError($request, 'Only group leaders, advisers, and admins can create chat rooms', 403);
        }

        if (!$request->filled('project_id')) {
            $defaultProject = $this->defaultChatProjectFor($user);

            if ($defaultProject) {
                $request->merge(['project_id' => $defaultProject->id]);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:project,direct,group',
            'project_id' => 'nullable|exists:projects,id',
            'participants' => 'nullable|array|min:1',
            'participants.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            if (!$request->expectsJson()) {
                return redirect()
                    ->route('chat.index')
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Get participants
            $participantIds = $request->input('participants', [$user->id]);
            $project = null;

            if ($request->project_id) {
                $project = $project ?: Project::with(['owner', 'adviser', 'members'])->findOrFail($request->project_id);

                if (!$this->canCreateProjectChatRoom($project, $user)) {
                    return $this->chatRoomCreateError($request, 'You cannot create a chat room for this project', 403);
                }

                $participants = $this->getProjectChatParticipants($project);
                $participantIds = $participants->pluck('id')->all();
            } else {
                $participants = User::whereIn('id', $participantIds)->get();
            }

            // Validate participants exist
            if ($participants->count() !== count($participantIds)) {
                return $this->chatRoomCreateError($request, 'Some participants not found', 422);
            }

            // Add current user to participants if not already included
            $currentUserId = Auth::id();
            if (!in_array($currentUserId, $participantIds)) {
                $currentUser = Auth::user();
                $participants->push($currentUser);
            }

            // Create chat room using Google Chat service
            $chatRoom = $this->getGoogleChatService()->createChatRoom(
                $request->name,
                $request->description ?? '',
                $participants->all(),
                $request->project_id
            );

            if (!$chatRoom) {
                \Log::error('ChatRoom creation returned null', [
                    'name' => $request->name,
                    'participants' => $participantIds,
                    'project_id' => $request->project_id
                ]);
                return $this->chatRoomCreateError($request, 'Failed to create chat room. Please check the logs for details.', 500);
            }

            if ($chatRoom->type === 'project') {
                $this->syncProjectChatParticipants($chatRoom->load(['project.owner', 'project.adviser', 'project.members']));
            }

            if (!$request->expectsJson()) {
                return redirect()
                    ->route('chat.index')
                    ->with('success', 'Chat room created successfully!');
            }

            return response()->json([
                'success' => true,
                'chat_room' => $chatRoom->load(['participants', 'project']),
                'message' => 'Chat room created successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Exception in chat room creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->chatRoomCreateError($request, 'Failed to create chat room: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get messages for a specific chat room
     */
    public function getMessages(ChatRoom $chatRoom): JsonResponse
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            if (!$chatRoom->hasParticipant(Auth::user()) && $this->canAccessChatRoom($chatRoom, Auth::user())) {
                $chatRoom->addParticipant(Auth::user(), $chatRoom->created_by === Auth::id() ? 'admin' : 'member');
            }

            // Check if user is a participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $currentUserId = Auth::id();
            
            $messages = $chatRoom->messages()
                                ->with(['user:id,firstname,lastname', 'reactions.user:id,firstname,lastname'])
                                ->orderBy('created_at', 'asc')
                                ->get()
                                ->filter(function ($message) use ($currentUserId) {
                                    // Filter out messages deleted for this user
                                    $deletedForUsers = $message->deleted_for_users ?? [];
                                    return !in_array($currentUserId, $deletedForUsers);
                                })
                                ->map(function ($message) use ($currentUserId) {
                                    // Handle case where user might be deleted
                                    $user = $message->user;
                                    $userName = $user ? ($user->firstname . ' ' . $user->lastname) : 'Deleted User';
                                    $userId = $user ? $user->id : null;
                                    
                                    // Check if current user has seen this message
                                    $seenBy = $message->seen_by ?? [];
                                    $isSeenByCurrentUser = in_array($currentUserId, $seenBy);
                                    
                                    // Get seen by info (excluding current user)
                                    $seenByOthers = array_filter($seenBy, function($id) use ($currentUserId) {
                                        return $id !== $currentUserId;
                                    });
                                    
                                    return [
                                        'id' => $message->id,
                                        'message' => $message->message,
                                        'message_type' => $message->message_type,
                                        'user' => [
                                            'id' => $userId,
                                            'name' => $userName,
                                        ],
                                        'file_url' => $message->getFileUrl(),
                                        'file_name' => $message->file_name,
                                        'file_size' => $message->getFormattedFileSize(),
                                        'is_image' => $message->isImage(),
                                        'is_edited' => $message->is_edited,
                                        'is_seen' => $isSeenByCurrentUser,
                                        'seen_by_count' => count($seenByOthers),
                                        'can_delete' => $userId === $currentUserId, // User can delete their own messages
                                        'reactions' => $message->getReactionsSummary(),
                                        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                                        'created_at_human' => $message->created_at->diffForHumans(),
                                    ];
                                })
                                ->values(); // Reset array keys after filtering

            // Mark messages as read
            $chatRoom->participants()
                     ->wherePivot('user_id', Auth::id())
                     ->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

            return response()->json(['messages' => $messages]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading messages', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Failed to load messages: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send a message to a chat room
     */
    public function sendMessage(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        $user = Auth::user();

        if (!$chatRoom->hasParticipant($user) && $this->canAccessChatRoom($chatRoom, $user)) {
            $chatRoom->addParticipant($user, $chatRoom->created_by === $user->id ? 'admin' : 'member');
        }

        // Check if user is a participant
        if (!$chatRoom->hasParticipant($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:file|string|max:2000',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $fileData = null;

            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('chat_files', $filename, 'public');

                $fileData = [
                    'path' => $filePath,
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file',
                ];
            }

            // Send message through Google Chat service
            $message = $this->getGoogleChatService()->sendChatMessage(
                $chatRoom,
                $request->message ?? 'File shared',
                $fileData
            );

            if (!$message) {
                return response()->json(['error' => 'Failed to send message'], 500);
            }

            $chatRoom->touch();

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'message_type' => $message->message_type,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->firstname . ' ' . $message->user->lastname,
                    ],
                    'file_url' => $message->getFileUrl(),
                    'file_name' => $message->file_name,
                    'file_size' => $message->getFormattedFileSize(),
                    'is_image' => $message->isImage(),
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $message->created_at->diffForHumans(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send chat message', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get chat room details
     */
    public function show(ChatRoom $chatRoom): JsonResponse
    {
        if (!$chatRoom->hasParticipant(Auth::user()) && $this->canAccessChatRoom($chatRoom, Auth::user())) {
            $chatRoom->addParticipant(Auth::user(), $chatRoom->created_by === Auth::id() ? 'admin' : 'member');
        }

        // Check if user is a participant
        if (!$chatRoom->hasParticipant(Auth::user())) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $chatRoom->load(['participants:id,firstname,lastname', 'project:id,title']);

        return response()->json([
            'chat_room' => [
                'id' => $chatRoom->id,
                'name' => $chatRoom->name,
                'description' => $chatRoom->description,
                'type' => $chatRoom->type,
                'created_by' => $chatRoom->created_by,
                'project' => $chatRoom->project,
                'participants' => $chatRoom->participants->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'pivot' => [
                            'role' => $user->pivot->role ?? 'member'
                        ]
                    ];
                }),
                'unread_count' => $chatRoom->getUnreadCountForUser(Auth::user()),
            ]
        ]);
    }

    /**
     * Create a project-based chat room
     */
    public function createProjectChat(Project $project): JsonResponse
    {
        try {
            // Check if user can manage the project
            if (!$project->canEdit(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $chatRoom = $this->ensureProjectChatRoom($project->load(['owner', 'adviser', 'members']));

            return response()->json([
                'success' => true,
                'chat_room' => $chatRoom->load(['participants', 'project']),
                'message' => 'Project chat room created successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create project chat: ' . $e->getMessage()], 500);
        }
    }

    private function ensureProjectChatRoom(Project $project): ChatRoom
    {
        $chatRoom = ChatRoom::firstOrCreate(
            [
                'project_id' => $project->id,
                'type' => 'project',
            ],
            [
                'name' => $project->title . ' - Project Chat',
                'description' => 'Chat room for project: ' . $project->title,
                'created_by' => $project->owner_id ?: Auth::id(),
                'google_space_id' => null,
                'is_active' => true,
            ]
        );

        $this->syncProjectChatParticipants($chatRoom->setRelation('project', $project));

        return $chatRoom;
    }

    private function syncProjectChatParticipants(ChatRoom $chatRoom): void
    {
        $project = $chatRoom->project ?: $chatRoom->project()->with(['owner', 'adviser', 'members'])->first();

        if (!$project) {
            return;
        }

        $participants = $this->getProjectChatParticipants($project);

        $allowedIds = $participants->pluck('id')->all();

        foreach ($participants as $participant) {
            $role = $participant->id === $project->owner_id
                ? 'admin'
                : ($participant->isTeacher() ? 'moderator' : 'member');

            $chatRoom->addParticipant($participant, $role);
        }

        $chatRoom->participants()
            ->whereNotIn('users.id', $allowedIds)
            ->detach();
    }

    private function getProjectChatParticipants(Project $project)
    {
        $students = collect([$project->owner])
            ->merge($project->members)
            ->filter()
            ->unique('id');

        $approvedAdvisers = $students
            ->flatMap(fn (User $student) => $student->advisers()->approved()->with('adviser')->get()->pluck('adviser'))
            ->filter();

        return collect([$project->owner])
            ->merge($project->adviser ? [$project->adviser] : [])
            ->merge($approvedAdvisers)
            ->merge($project->members)
            ->filter()
            ->unique('id')
            ->values();
    }

    private function canCreateProjectChatRoom(Project $project, User $user): bool
    {
        if ($user->isAdmin() || $project->canEdit($user)) {
            return true;
        }

        return $user->isTeacher() && $project->canAccess($user);
    }

    private function defaultChatProjectFor(User $user): ?Project
    {
        if ($user->canLeadGroup()) {
            return $user->ownedProjects()
                ->with(['owner', 'adviser', 'members'])
                ->latest()
                ->first();
        }

        if ($user->isTeacher()) {
            return $user->accessibleProjects()
                ->with(['owner', 'adviser', 'members'])
                ->latest()
                ->first();
        }

        return null;
    }

    private function chatRoomCreateError(Request $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], $status);
        }

        return redirect()
            ->route('chat.index')
            ->withInput()
            ->with('error', $message);
    }

    private function canAccessChatRoom(ChatRoom $chatRoom, User $user): bool
    {
        if ($user->isAdmin() || $chatRoom->created_by === $user->id) {
            return true;
        }

        if ($chatRoom->project_id) {
            $project = $chatRoom->project ?: $chatRoom->project()->with(['owner', 'adviser', 'members'])->first();

            return $project ? $project->canAccess($user) : false;
        }

        return false;
    }

    /**
     * Add participants to a chat room
     */
    public function addParticipants(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        try {
            return response()->json([
                'error' => 'Members can only be added through project invitation links.'
            ], 403);

            // Check if user is admin or creator
            $currentUser = Auth::user();
            $userParticipant = $chatRoom->participants()->where('user_id', $currentUser->id)->first();
            
            if (!$userParticipant || !in_array($userParticipant->pivot->role, ['admin', 'creator'])) {
                return response()->json(['error' => 'Only admins can add participants'], 403);
            }

            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $addedUsers = [];
            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);
                if ($user && !$chatRoom->hasParticipant($user)) {
                    $chatRoom->addParticipant($user, 'member');
                    $addedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->firstname . ' ' . $user->lastname
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($addedUsers) . ' participants added successfully',
                'added_users' => $addedUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error adding participants', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to add participants: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove participant from chat room
     */
    public function removeParticipant(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        try {
            // Check if user is admin or creator
            $currentUser = Auth::user();
            $userParticipant = $chatRoom->participants()->where('user_id', $currentUser->id)->first();
            
            if (!$userParticipant || !in_array($userParticipant->pivot->role, ['admin', 'creator'])) {
                return response()->json(['error' => 'Only admins can remove participants'], 403);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $userToRemove = User::find($request->user_id);
            
            // Prevent removing the creator
            if ($chatRoom->created_by === $userToRemove->id) {
                return response()->json(['error' => 'Cannot remove the chat room creator'], 400);
            }

            if ($chatRoom->hasParticipant($userToRemove)) {
                $chatRoom->participants()->detach($userToRemove->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Participant removed successfully'
                ]);
            } else {
                return response()->json(['error' => 'User is not a participant'], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Error removing participant', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to remove participant: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage(Request $request, ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            // Check if user owns the message or is admin
            $currentUser = Auth::user();
            $userParticipant = $chatRoom->participants()->where('user_id', $currentUser->id)->first();
            $isAdmin = $userParticipant && in_array($userParticipant->pivot->role, ['admin', 'creator']);
            
            if ($message->user_id !== $currentUser->id && !$isAdmin) {
                return response()->json(['error' => 'You can only delete your own messages'], 403);
            }

            $validator = Validator::make($request->all(), [
                'delete_for' => 'required|in:self,everyone',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->delete_for === 'everyone') {
                // Only message owner or admin can delete for everyone
                if ($message->user_id !== $currentUser->id && !$isAdmin) {
                    return response()->json(['error' => 'Only message owner or admin can delete for everyone'], 403);
                }
                
                // Delete the message completely
                $message->delete();
                $messageText = 'Message deleted for everyone';
            } else {
                // Delete for self only - mark as deleted for this user
                $message->update([
                    'deleted_for_users' => array_unique(array_merge(
                        $message->deleted_for_users ?? [],
                        [$currentUser->id]
                    ))
                ]);
                $messageText = 'Message deleted for you';
            }

            return response()->json([
                'success' => true,
                'message' => $messageText,
                'delete_type' => $request->delete_for
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting message', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark messages as seen
     */
    public function markAsSeen(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'message_ids' => 'required|array|min:1',
                'message_ids.*' => 'exists:chat_messages,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $currentUser = Auth::user();
            
            // Update seen status for messages
            foreach ($request->message_ids as $messageId) {
                $message = ChatMessage::find($messageId);
                if ($message && $message->chat_room_id === $chatRoom->id) {
                    $seenBy = $message->seen_by ?? [];
                    if (!in_array($currentUser->id, $seenBy)) {
                        $seenBy[] = $currentUser->id;
                        $message->update(['seen_by' => $seenBy]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as seen'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error marking messages as seen', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to mark messages as seen: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available users to add to chat room
     */
    public function getAvailableUsers(ChatRoom $chatRoom): JsonResponse
    {
        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            // Get users who are not already participants
            $existingParticipantIds = $chatRoom->participants()->pluck('users.id')->toArray();
            
            $availableUsers = User::whereNotIn('id', $existingParticipantIds)
                                 ->where('status', 'Verified') // Only verified users
                                 ->select('id', 'firstname', 'lastname', 'email', 'role')
                                 ->get()
                                 ->map(function ($user) {
                                     return [
                                         'id' => $user->id,
                                         'name' => $user->firstname . ' ' . $user->lastname,
                                         'email' => $user->email,
                                         'role' => $user->role
                                     ];
                                 });

            return response()->json([
                'success' => true,
                'users' => $availableUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting available users', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to get available users: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Leave a chat room
     */
    public function leaveChatRoom(ChatRoom $chatRoom): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Check if user is participant
            if (!$chatRoom->hasParticipant($currentUser)) {
                return response()->json(['error' => 'You are not a participant in this chat room'], 400);
            }

            // Prevent creator from leaving (they should delete the room instead)
            if ($chatRoom->created_by === $currentUser->id) {
                return response()->json(['error' => 'Room creator cannot leave. Delete the room instead.'], 400);
            }

            // Remove user from participants
            $chatRoom->participants()->detach($currentUser->id);

            // Add system message about user leaving
            ChatMessage::create([
                'chat_room_id' => $chatRoom->id,
                'user_id' => $currentUser->id,
                'message' => $currentUser->firstname . ' ' . $currentUser->lastname . ' left the chat',
                'message_type' => 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'You have left the chat room successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error leaving chat room', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to leave chat room: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a chat room (creator/admin only)
     */
    public function deleteChatRoom(ChatRoom $chatRoom): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Check if user is creator or admin
            if ($chatRoom->created_by !== $currentUser->id && !$currentUser->isAdmin()) {
                return response()->json(['error' => 'Only the room creator or admin can delete this chat room'], 403);
            }

            // Store room name for response
            $roomName = $chatRoom->name;

            // Delete all related data (messages, participants, etc.)
            // Laravel will handle cascade deletes based on foreign key constraints
            $chatRoom->delete();

            return response()->json([
                'success' => true,
                'message' => "Chat room '{$roomName}' has been deleted successfully"
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting chat room', [
                'chat_room_id' => $chatRoom->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to delete chat room: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update typing status
     */
    public function updateTypingStatus(Request $request, ChatRoom $chatRoom): JsonResponse
    {
        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'is_typing' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $currentUser = Auth::user();
            $isTyping = $request->is_typing;

            // Store typing status in cache (expires in 10 seconds)
            $cacheKey = "typing_status_{$chatRoom->id}_{$currentUser->id}";
            
            if ($isTyping) {
                cache()->put($cacheKey, [
                    'user_id' => $currentUser->id,
                    'user_name' => $currentUser->firstname . ' ' . $currentUser->lastname,
                    'timestamp' => now()
                ], 10); // 10 seconds
            } else {
                cache()->forget($cacheKey);
            }

            return response()->json([
                'success' => true,
                'message' => 'Typing status updated'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating typing status', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to update typing status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get typing users for a chat room
     */
    public function getTypingUsers(ChatRoom $chatRoom): JsonResponse
    {
        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $currentUserId = Auth::id();
            $typingUsers = [];

            // Get all typing status cache keys for this room
            $cachePattern = "typing_status_{$chatRoom->id}_*";
            
            // Get participants to check typing status
            $participants = $chatRoom->participants()->get();
            
            foreach ($participants as $participant) {
                if ($participant->id !== $currentUserId) {
                    $cacheKey = "typing_status_{$chatRoom->id}_{$participant->id}";
                    $typingData = cache()->get($cacheKey);
                    
                    if ($typingData) {
                        $typingUsers[] = [
                            'user_id' => $participant->id,
                            'user_name' => $participant->firstname . ' ' . $participant->lastname
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'typing_users' => $typingUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting typing users', [
                'chat_room_id' => $chatRoom->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to get typing users: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add or toggle a reaction to a message
     */
    public function toggleReaction(Request $request, ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            // Validate that message belongs to this chat room
            if ($message->chat_room_id !== $chatRoom->id) {
                return response()->json(['error' => 'Message not found in this chat room'], 404);
            }

            $validator = Validator::make($request->all(), [
                'emoji' => 'required|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $currentUser = Auth::user();
            $emoji = $request->emoji;

            // Check if user already reacted with this emoji
            $existingReaction = MessageReaction::where([
                'message_id' => $message->id,
                'user_id' => $currentUser->id,
                'emoji' => $emoji
            ])->first();

            if ($existingReaction) {
                // Remove reaction (toggle off)
                $existingReaction->delete();
                $action = 'removed';
            } else {
                // Add reaction (toggle on)
                MessageReaction::create([
                    'message_id' => $message->id,
                    'user_id' => $currentUser->id,
                    'emoji' => $emoji
                ]);
                $action = 'added';
            }

            // Get updated reactions summary
            $reactionsSummary = $message->getReactionsSummary();

            return response()->json([
                'success' => true,
                'action' => $action,
                'emoji' => $emoji,
                'reactions' => $reactionsSummary
            ]);

        } catch (\Exception $e) {
            \Log::error('Error toggling reaction', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to toggle reaction: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get reactions for a specific message
     */
    public function getMessageReactions(ChatRoom $chatRoom, ChatMessage $message): JsonResponse
    {
        try {
            // Check if user is participant
            if (!$chatRoom->hasParticipant(Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            // Validate that message belongs to this chat room
            if ($message->chat_room_id !== $chatRoom->id) {
                return response()->json(['error' => 'Message not found in this chat room'], 404);
            }

            $reactionsSummary = $message->getReactionsSummary();

            return response()->json([
                'success' => true,
                'reactions' => $reactionsSummary
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting message reactions', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to get reactions: ' . $e->getMessage()], 500);
        }
    }
}
