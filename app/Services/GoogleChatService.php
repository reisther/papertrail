<?php

namespace App\Services;

use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleChatService
{
    private bool $googleChatEnabled = false;

    public function __construct()
    {
        // For now, we'll focus on local chat functionality
        // Google Chat API integration will be added after proper OAuth setup
        $this->googleChatEnabled = false;
    }

    /**
     * Create a chat room (local functionality for now)
     */
    public function createChatRoom(string $name, string $description, array $participants, ?int $projectId = null): ?ChatRoom
    {
        try {
            // Validate auth user exists
            if (!auth()->check()) {
                Log::error('Cannot create chat room: User not authenticated');
                return null;
            }

            // Create local chat room (Google Chat integration will be added later)
            $chatRoom = ChatRoom::create([
                'name' => $name,
                'description' => $description,
                'type' => $projectId ? 'project' : 'group',
                'project_id' => $projectId,
                'created_by' => auth()->id(),
                'google_space_id' => null, // Will be set when Google Chat is integrated
                'is_active' => true,
            ]);

            Log::info('ChatRoom model created', ['id' => $chatRoom->id, 'name' => $name]);

            // Add participants
            foreach ($participants as $participant) {
                if (!$participant instanceof User && is_array($participant) && isset($participant['id'])) {
                    $participant = User::find($participant['id']);
                }

                if (!$participant instanceof User) {
                    Log::warning('Invalid participant type', ['participant' => $participant]);
                    continue;
                }

                $chatRoom->addParticipant($participant, 'member');
                Log::info('Added participant', ['user_id' => $participant->id, 'chat_room_id' => $chatRoom->id]);
            }

            // Add creator as admin (avoid duplicate if already in participants)
            $creatorUser = auth()->user();
            if ($creatorUser && !$chatRoom->hasParticipant($creatorUser)) {
                $chatRoom->addParticipant($creatorUser, 'admin');
                Log::info('Added creator as admin', ['user_id' => $creatorUser->id, 'chat_room_id' => $chatRoom->id]);
            }

            Log::info('Chat room created successfully: ' . $name);
            return $chatRoom;
            
        } catch (Exception $e) {
            Log::error('Failed to create chat room', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'name' => $name,
                'project_id' => $projectId
            ]);
            return null;
        }
    }

    /**
     * Send a message (local storage for now)
     */
    public function sendChatMessage(ChatRoom $chatRoom, string $message, ?array $fileData = null): ?ChatMessage
    {
        try {
            // Create local message
            $chatMessage = ChatMessage::create([
                'chat_room_id' => $chatRoom->id,
                'user_id' => auth()->id(),
                'message' => $message,
                'message_type' => $fileData ? ($fileData['type'] ?? 'file') : 'text',
                'file_path' => $fileData['path'] ?? null,
                'file_name' => $fileData['name'] ?? null,
                'file_type' => $fileData['mime_type'] ?? null,
                'file_size' => $fileData['size'] ?? null,
            ]);

            // Google Chat integration will be added here later
            Log::info('Message sent to chat room: ' . $chatRoom->name);
            return $chatMessage;
            
        } catch (Exception $e) {
            Log::error('Failed to send chat message: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if Google Chat integration is available
     */
    public function isAvailable(): bool
    {
        // For now, always return false since we're using local functionality
        return false;
    }

    /**
     * Get authorization URL for Google Chat setup
     */
    public function getAuthUrl(): string
    {
        // Will be implemented when Google Chat API is integrated
        throw new Exception('Google Chat integration is not yet available. Please use local chat functionality.');
    }

    /**
     * Handle OAuth callback for Google Chat
     */
    public function handleCallback(string $code): bool
    {
        // Will be implemented when Google Chat API is integrated
        return false;
    }
}
