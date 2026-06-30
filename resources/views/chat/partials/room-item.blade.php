<div class="chat-room-item p-4 border-b border-gray-100 hover:bg-blue-50 cursor-pointer transition-colors"
     data-room-id="{{ $room->id }}" onclick="selectChatRoom({{ $room->id }})">
    <div class="flex items-center justify-between gap-3">
        <div class="flex-1 min-w-0">
            <h3 class="text-sm font-medium text-gray-900 truncate">{{ $room->name }}</h3>
            <p class="text-xs text-gray-500 mt-1">
                @if($room->type === 'project' && $room->project)
                    Project: {{ $room->project->title }}
                @else
                    {{ ucfirst($room->type) }} Chat
                @endif
            </p>
            @if($room->latestMessage->first())
                <p class="text-xs text-gray-400 mt-1 truncate">
                    {{ $room->latestMessage->first()->user?->firstname ?? 'User' }}:
                    {{ Str::limit($room->latestMessage->first()->message, 30) }}
                </p>
            @endif
        </div>
        @if($room->unread_count > 0)
            <span class="bg-blue-600 text-white text-xs rounded-full px-2 py-1">
                {{ $room->unread_count }}
            </span>
        @endif
    </div>
</div>
