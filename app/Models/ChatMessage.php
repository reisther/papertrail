<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_room_id',
        'user_id',
        'message',
        'message_type',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'google_message_id',
        'metadata',
        'is_edited',
        'edited_at',
        'seen_by',
        'deleted_for_users',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'seen_by' => 'array',
        'deleted_for_users' => 'array',
    ];

    /**
     * Get the chat room that owns the message
     */
    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    /**
     * Get the user who sent the message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the message has a file attachment
     */
    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }

    /**
     * Get the file URL if it exists
     */
    public function getFileUrl(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }

        return asset('storage/' . $this->file_path);
    }

    /**
     * Check if the message is a system message
     */
    public function isSystemMessage(): bool
    {
        return $this->message_type === 'system';
    }

    /**
     * Check if the message is an image
     */
    public function isImage(): bool
    {
        return $this->message_type === 'image' || 
               in_array($this->file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Mark message as edited
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Get the reactions for the message
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class, 'message_id');
    }

    /**
     * Get reactions grouped by emoji with counts and users
     */
    public function getReactionsSummary(): array
    {
        return $this->reactions()
            ->with('user:id,firstname,lastname')
            ->get()
            ->groupBy('emoji')
            ->map(function ($reactions, $emoji) {
                return [
                    'emoji' => $emoji,
                    'count' => $reactions->count(),
                    'users' => $reactions->map(function ($reaction) {
                        return [
                            'id' => $reaction->user->id,
                            'name' => $reaction->user->firstname . ' ' . $reaction->user->lastname
                        ];
                    })->toArray(),
                    'user_ids' => $reactions->pluck('user_id')->toArray()
                ];
            })
            ->values()
            ->toArray();
    }
}
