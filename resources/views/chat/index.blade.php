<x-app-layout>
    @php
        $memberWithoutGroup = Auth::user()->isStudent() && Auth::user()->joinedProjects()->doesntExist();
        $leaderWithoutGroup = Auth::user()->canLeadGroup() && Auth::user()->ownedProjects()->doesntExist();
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chat System') }}
        </h2>
    </x-slot>
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Chat & Collaboration</h1>
            <p class="mt-2 text-gray-600">Communicate with your project team and advisers in real-time</p>
        </div>

        @if($leaderWithoutGroup)
            @include('partials.leader-create-group-card')
        @else
        @php
            $chatRoomGroups = $chatRooms->groupBy(fn ($room) => $room->project?->title ?? 'General Rooms');
            $showFolderSidebar = Auth::user()->isTeacher();
            $canCreateChatRooms = Auth::user()->canLeadGroup() || Auth::user()->isTeacher() || Auth::user()->isAdmin();
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[calc(100vh-12rem)]">
            <!-- Chat Rooms Sidebar -->
            <div class="lg:col-span-1 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Chat Rooms</h2>
                        @if($canCreateChatRooms)
                            <button onclick="openCreateRoomModal()" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-md transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
                
                <div class="overflow-y-auto h-full" id="chatRoomsList">
                    @if($chatRooms->isNotEmpty() && $showFolderSidebar)
                        <div class="divide-y divide-gray-100">
                            @foreach($chatRoomGroups as $projectName => $rooms)
                                <details class="chat-room-folder" open>
                                    <summary class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50">
                                        <span class="text-sm font-semibold text-gray-900 truncate">{{ $projectName }}</span>
                                        <span class="text-xs rounded-full bg-blue-100 text-blue-700 px-2 py-1">{{ $rooms->count() }}</span>
                                    </summary>

                                    <div class="pb-2">
                                        @foreach($rooms as $room)
                                            @include('chat.partials.room-item', ['room' => $room])
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @elseif($chatRooms->isNotEmpty())
                        @foreach($chatRooms as $room)
                            @include('chat.partials.room-item', ['room' => $room])
                        @endforeach
                    @else
                        <div class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            @if($memberWithoutGroup)
                                <p class="text-sm">You're not in a group chat yet</p>
                                <p class="text-xs mt-1">Ask your leader to send you the invitation link for your group. Once you join, your group chat will appear here.</p>
                            @else
                                <p class="text-sm">No chat rooms yet</p>
                                <p class="text-xs mt-1">Chat rooms appear here after your group leader creates one</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Chat Interface -->
            <div class="lg:col-span-3 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                <div id="chatHeader" class="p-4 border-b border-gray-200 bg-gray-50 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 id="chatRoomName" class="text-lg font-semibold text-gray-900"></h3>
                            <p id="chatRoomDescription" class="text-sm text-gray-600"></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="showParticipants()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100" title="View Participants">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"></path>
                                </svg>
                            </button>
                            <button onclick="leaveChatRoom()" class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50" title="Leave Chat Room">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                            <button id="deleteChatRoomBtn" onclick="deleteChatRoom()" class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 hidden" title="Delete Chat Room">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 hidden">
                    <!-- Messages will be loaded here -->
                </div>

                <!-- Typing Indicator -->
                <div id="typingIndicator" class="px-4 py-2 text-sm text-gray-500 italic hidden">
                    <!-- Typing status will be shown here -->
                </div>

                <!-- Welcome Message -->
                <div id="welcomeMessage" class="flex-1 flex items-center justify-center text-center p-8">
                    <div>
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        @if($memberWithoutGroup && $chatRooms->isEmpty())
                            <h3 class="text-lg font-medium text-gray-900 mb-2">You're not in a group yet</h3>
                            <p class="text-gray-600 max-w-md">Ask your leader to send you the invitation link for your group. After you join, you can send chats and files here.</p>
                        @else
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Welcome to Paper Trail Chat</h3>
                            <p class="text-gray-600 max-w-md">Select a chat room from the sidebar to start collaborating with your team members and advisers.</p>
                        @endif
                    </div>
                </div>

                <!-- Message Input -->
                <div id="messageInput" class="p-4 border-t border-gray-200 hidden">
                    <form id="sendMessageForm" class="flex items-end space-x-3">
                        <div class="flex-1">
                            <div class="flex items-end space-x-2">
                                <button type="button" onclick="toggleFileUpload()" class="text-gray-400 hover:text-gray-600 p-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                </button>
                                <div class="flex-1">
                                    <textarea id="messageText" name="message" rows="1" 
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 resize-none"
                                              placeholder="Type your message..." 
                                              onkeydown="handleMessageKeydown(event)"></textarea>
                                    <input type="file" id="fileInput" name="file" class="hidden" onchange="handleFileSelect(event)">
                                </div>
                            </div>
                            <div id="filePreview" class="hidden mt-2 p-2 bg-gray-50 rounded border text-sm">
                                <div class="flex items-center justify-between">
                                    <span id="fileName"></span>
                                    <button type="button" onclick="clearFileSelection()" class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Create Room Modal -->
<div id="createRoomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Create Chat Room</h3>
                <button onclick="closeCreateRoomModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="createRoomForm" method="POST" action="{{ route('chat.rooms.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="roomName" class="block text-sm font-medium text-gray-700">Room Name</label>
                        <input type="text" id="roomName" name="name" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="roomDescription" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="roomDescription" name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label for="roomType" class="block text-sm font-medium text-gray-700">Type</label>
                        <select id="roomType" name="type" required 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="project">Project Chat</option>
                            <option value="group">Group Chat</option>
                        </select>
                    </div>
                    @if($availableProjects->isNotEmpty())
                        <div>
                            <label for="projectId" class="block text-sm font-medium text-gray-700">Group</label>
                            <select id="projectId" name="project_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($availableProjects as $project)
                                    <option value="{{ $project->id }}">{{ $project->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif(Auth::user()->canLeadGroup() || Auth::user()->isTeacher())
                        <p class="text-sm text-gray-500">This room will be created in your active group.</p>
                    @endif
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateRoomModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" id="createRoomSubmit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                        Create Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Participants Modal -->
<div id="addParticipantsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add Participants</h3>
                <button onclick="closeAddParticipantsModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="addParticipantsForm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Users to Add</label>
                        <div id="availableUsersList" class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-2">
                            <!-- Available users will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeAddParticipantsModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                        Add Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Participants List Modal -->
<div id="participantsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Participants</h3>
                <button onclick="closeParticipantsModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="participantsList" class="space-y-2">
                <!-- Participants will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 id="notificationTitle" class="text-lg font-medium text-gray-900">Notification</h3>
                <button onclick="closeNotificationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="notificationMessage" class="mb-4 text-gray-600">
                <!-- Message content -->
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeNotificationModal()" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 id="confirmationTitle" class="text-lg font-medium text-gray-900">Confirm Action</h3>
                <button onclick="closeConfirmationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="confirmationMessage" class="mb-4 text-gray-600">
                <!-- Message content -->
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeConfirmationModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
                <button id="confirmationButton" onclick="handleConfirmation()" 
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Emoji Picker Modal -->
<div id="emojiPickerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-80 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add Reaction</h3>
                <button onclick="closeEmojiPicker()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-8 gap-2 mb-4">
                <!-- Common emoji reactions -->
                <button onclick="addReaction('👍')" class="p-2 text-2xl hover:bg-gray-100 rounded">👍</button>
                <button onclick="addReaction('❤️')" class="p-2 text-2xl hover:bg-gray-100 rounded">❤️</button>
                <button onclick="addReaction('😂')" class="p-2 text-2xl hover:bg-gray-100 rounded">😂</button>
                <button onclick="addReaction('😮')" class="p-2 text-2xl hover:bg-gray-100 rounded">😮</button>
                <button onclick="addReaction('😢')" class="p-2 text-2xl hover:bg-gray-100 rounded">😢</button>
                <button onclick="addReaction('😡')" class="p-2 text-2xl hover:bg-gray-100 rounded">😡</button>
                <button onclick="addReaction('👏')" class="p-2 text-2xl hover:bg-gray-100 rounded">👏</button>
                <button onclick="addReaction('🔥')" class="p-2 text-2xl hover:bg-gray-100 rounded">🔥</button>
                <button onclick="addReaction('✅')" class="p-2 text-2xl hover:bg-gray-100 rounded">✅</button>
                <button onclick="addReaction('❌')" class="p-2 text-2xl hover:bg-gray-100 rounded">❌</button>
                <button onclick="addReaction('🎉')" class="p-2 text-2xl hover:bg-gray-100 rounded">🎉</button>
                <button onclick="addReaction('💯')" class="p-2 text-2xl hover:bg-gray-100 rounded">💯</button>
                <button onclick="addReaction('🤔')" class="p-2 text-2xl hover:bg-gray-100 rounded">🤔</button>
                <button onclick="addReaction('👀')" class="p-2 text-2xl hover:bg-gray-100 rounded">👀</button>
                <button onclick="addReaction('💡')" class="p-2 text-2xl hover:bg-gray-100 rounded">💡</button>
                <button onclick="addReaction('⚡')" class="p-2 text-2xl hover:bg-gray-100 rounded">⚡</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Message Options Modal -->
<div id="deleteMessageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Delete Message</h3>
                <button onclick="closeDeleteMessageModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-4 text-gray-600">
                Choose how you want to delete this message:
            </div>
            <div class="space-y-3" id="deleteMessageButtons">
                <button onclick="performDeleteMessage('self')" 
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                    Delete for me only
                </button>
                <button onclick="performDeleteMessage('everyone')" 
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                    Delete for everyone
                </button>
                <button onclick="closeDeleteMessageModal()" 
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRoomId = null;
let messagePollingInterval = null;
let confirmationCallback = null;
let isInitialLoad = false;
let shouldScrollToBottom = false;

// Modal functions
function showNotification(title, message, type = 'info') {
    document.getElementById('notificationTitle').textContent = title;
    document.getElementById('notificationMessage').innerHTML = message;
    
    const button = document.querySelector('#notificationModal button[onclick="closeNotificationModal()"]');
    if (type === 'error') {
        button.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md';
    } else if (type === 'success') {
        button.className = 'px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md';
    } else {
        button.className = 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md';
    }
    
    document.getElementById('notificationModal').classList.remove('hidden');
}

function closeNotificationModal() {
    document.getElementById('notificationModal').classList.add('hidden');
}

function showConfirmation(title, message, callback, confirmText = 'Confirm', confirmType = 'danger') {
    document.getElementById('confirmationTitle').textContent = title;
    document.getElementById('confirmationMessage').innerHTML = message;
    
    const confirmButton = document.getElementById('confirmationButton');
    confirmButton.textContent = confirmText;
    
    if (confirmType === 'danger') {
        confirmButton.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md';
    } else {
        confirmButton.className = 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md';
    }
    
    confirmationCallback = callback;
    document.getElementById('confirmationModal').classList.remove('hidden');
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').classList.add('hidden');
    confirmationCallback = null;
}

function handleConfirmation() {
    if (confirmationCallback) {
        confirmationCallback();
    }
    closeConfirmationModal();
}

// Initialize chat interface
document.addEventListener('DOMContentLoaded', function() {
    // Auto-select first room if available
    const firstRoom = document.querySelector('.chat-room-item');
    if (firstRoom) {
        const roomId = firstRoom.dataset.roomId;
        selectChatRoom(roomId);
    }
});

// Select and load a chat room
function selectChatRoom(roomId) {
    currentRoomId = roomId;
    
    // Update UI
    document.querySelectorAll('.chat-room-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500');
    });
    
    const selectedRoom = document.querySelector(`[data-room-id="${roomId}"]`);
    if (selectedRoom) {
        selectedRoom.classList.add('bg-blue-50', 'border-l-4', 'border-blue-500');
    }
    
    // Show chat interface
    document.getElementById('welcomeMessage').classList.add('hidden');
    document.getElementById('chatHeader').classList.remove('hidden');
    document.getElementById('messagesContainer').classList.remove('hidden');
    document.getElementById('messageInput').classList.remove('hidden');
    
    // Set flag for initial load
    isInitialLoad = true;
    
    // Load room details and messages
    loadChatRoom(roomId);
    loadMessages(roomId);
    
    // Start polling for new messages
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    messagePollingInterval = setInterval(() => loadMessages(roomId), 3000);
    
    // Start typing indicator polling
    if (window.typingPollingInterval) {
        clearInterval(window.typingPollingInterval);
    }
    window.typingPollingInterval = setInterval(getTypingUsers, 2000); // Check every 2 seconds
}

// Load chat room details
async function loadChatRoom(roomId) {
    try {
        const response = await fetch(`/chat/rooms/${roomId}`);
        const data = await response.json();
        
        if (data.chat_room) {
            document.getElementById('chatRoomName').textContent = data.chat_room.name;
            document.getElementById('chatRoomDescription').textContent = data.chat_room.description || '';
            
            // Check if current user is creator to show delete button
            const currentUserId = {{ Auth::id() }};
            const isCreator = data.chat_room.created_by === currentUserId;
            const isAdmin = {{ Auth::user()->role === 'Admin' ? 'true' : 'false' }};
            
            const deleteBtn = document.getElementById('deleteChatRoomBtn');
            if (deleteBtn) {
                if (isCreator || isAdmin) {
                    deleteBtn.classList.remove('hidden');
                } else {
                    deleteBtn.classList.add('hidden');
                }
            }
        }
    } catch (error) {
        console.error('Error loading chat room:', error);
    }
}

// Load messages for current room
async function loadMessages(roomId) {
    try {
        const response = await fetch(`/chat/rooms/${roomId}/messages`);
        const data = await response.json();
        
        if (data.messages) {
            displayMessages(data.messages);
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

// Display messages in the chat interface
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    const currentUserId = {{ Auth::id() }};
    
    container.innerHTML = messages.map(message => {
        const isOwnMessage = message.user.id === currentUserId;
        const messageClass = isOwnMessage ? 'ml-auto bg-blue-600 text-white' : 'mr-auto bg-gray-100 text-gray-900';
        
        return `
            <div class="flex ${isOwnMessage ? 'justify-end' : 'justify-start'} group" data-message-id="${message.id}">
                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${messageClass} relative message-content">
                    ${!isOwnMessage ? `<div class="text-xs font-medium mb-1 ${isOwnMessage ? 'text-blue-100' : 'text-gray-600'}">${message.user.name}</div>` : ''}
                    <div class="text-sm">${message.message}</div>
                    ${message.file_url ? `
                        <div class="mt-2">
                            ${message.is_image ? 
                                `<img src="${message.file_url}" alt="${message.file_name}" class="max-w-full rounded">` :
                                `<a href="${message.file_url}" target="_blank" class="inline-flex items-center text-xs underline">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    ${message.file_name} (${message.file_size})
                                </a>`
                            }
                        </div>
                    ` : ''}
                    <div class="flex items-center justify-between mt-1">
                        <div class="text-xs ${isOwnMessage ? 'text-blue-100' : 'text-gray-500'}">
                            ${message.created_at_human}
                            ${message.seen_by_count > 0 ? `<span class="ml-2">✓ Seen by ${message.seen_by_count}</span>` : ''}
                        </div>
                        <div class="flex items-center space-x-1">
                            ${message.can_delete ? `
                                <button onclick="showDeleteOptions(${message.id})" class="opacity-0 group-hover:opacity-100 transition-opacity text-xs ${isOwnMessage ? 'text-blue-100 hover:text-white' : 'text-gray-400 hover:text-gray-600'}">
                                    ⋯
                                </button>
                            ` : ''}
                            <button onclick="showEmojiPicker(${message.id})" class="opacity-0 group-hover:opacity-100 transition-opacity text-xs ${isOwnMessage ? 'text-blue-100 hover:text-white' : 'text-gray-400 hover:text-gray-600'}" title="Add Reaction">
                                😊
                            </button>
                        </div>
                    </div>
                    ${message.reactions && message.reactions.length > 0 ? `
                        <div class="flex flex-wrap gap-1 mt-2">
                            ${message.reactions.map(reaction => `
                                <button onclick="toggleReaction(${message.id}, '${reaction.emoji}')" 
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs border transition-colors ${reaction.user_ids.includes(currentUserId) ? 'bg-blue-100 border-blue-300 text-blue-800' : 'bg-gray-100 border-gray-300 text-gray-600 hover:bg-gray-200'}"
                                        title="${reaction.users.map(u => u.name).join(', ')}">
                                    ${reaction.emoji} ${reaction.count}
                                </button>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
    
    // Mark messages as seen
    const unseenMessageIds = messages.filter(msg => !msg.is_seen && msg.user.id !== currentUserId).map(msg => msg.id);
    if (unseenMessageIds.length > 0) {
        markMessagesAsSeen(unseenMessageIds);
    }
    
    // Smart scroll: auto-scroll on initial load, after sending message, or if user is near the bottom (within 100px)
    const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
    if (isInitialLoad || shouldScrollToBottom || isNearBottom) {
        container.scrollTop = container.scrollHeight;
        isInitialLoad = false; // Reset flag after initial scroll
        shouldScrollToBottom = false; // Reset flag after forced scroll
    }
}

// Delete message functions
function showDeleteOptions(messageId) {
    // Store the message ID in the modal
    const buttonsContainer = document.getElementById('deleteMessageButtons');
    buttonsContainer.setAttribute('data-message-id', messageId);
    
    document.getElementById('deleteMessageModal').classList.remove('hidden');
}

function closeDeleteMessageModal() {
    document.getElementById('deleteMessageModal').classList.add('hidden');
    
    // Clear the stored message ID
    const buttonsContainer = document.getElementById('deleteMessageButtons');
    buttonsContainer.removeAttribute('data-message-id');
}

async function performDeleteMessage(deleteFor) {
    // Get the message ID from the modal
    const buttonsContainer = document.getElementById('deleteMessageButtons');
    const messageId = buttonsContainer.getAttribute('data-message-id');
    
    if (!messageId) {
        showNotification('Error', 'No message selected for deletion', 'error');
        return;
    }
    
    closeDeleteMessageModal();
    await deleteMessage(messageId, deleteFor);
}

// Send message
document.getElementById('sendMessageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!currentRoomId) return;
    
    const messageText = document.getElementById('messageText').value.trim();
    const fileInput = document.getElementById('fileInput');
    
    if (!messageText && !fileInput.files[0]) return;
    
    const formData = new FormData();
    if (messageText) formData.append('message', messageText);
    if (fileInput.files[0]) formData.append('file', fileInput.files[0]);
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/messages`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json')
            ? await response.json()
            : { error: await response.text() };
        
        if (data.success) {
            document.getElementById('messageText').value = '';
            clearFileSelection();
            shouldScrollToBottom = true; // Force scroll after sending message
            loadMessages(currentRoomId);
        } else {
            let errorMessage = data.error || 'Failed to send message';

            if (data.errors) {
                errorMessage = Object.values(data.errors).flat().join('<br>');
            }

            showNotification('Error', errorMessage, 'error');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        showNotification('Network Error', 'Failed to send message. Please check your connection.', 'error');
    }
});

// Handle Enter key in message input
function handleMessageKeydown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        document.getElementById('sendMessageForm').dispatchEvent(new Event('submit'));
    }
}

// File upload functions
function toggleFileUpload() {
    document.getElementById('fileInput').click();
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = `${file.name} (${formatFileSize(file.size)})`;
        document.getElementById('filePreview').classList.remove('hidden');
    }
}

function clearFileSelection() {
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreview').classList.add('hidden');
}

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${Math.round(size * 100) / 100} ${units[unitIndex]}`;
}

// Modal functions
function openCreateRoomModal() {
    document.getElementById('createRoomModal').classList.remove('hidden');
}

function closeCreateRoomModal() {
    document.getElementById('createRoomModal').classList.add('hidden');
    document.getElementById('createRoomForm').reset();
}

// Create room form submission
document.getElementById('createRoomForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.participants = [{{ Auth::id() }}]; // Add current user as participant
    delete data._token;
    
    // Validate required fields
    if (!data.name || !data.type) {
        alert('Please fill in all required fields');
        return;
    }

    const submitButton = document.getElementById('createRoomSubmit');
    submitButton.disabled = true;
    submitButton.textContent = 'Creating...';
    
    try {
        const response = await fetch('{{ route('chat.rooms.store') }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const contentType = response.headers.get('content-type') || '';
        const result = contentType.includes('application/json')
            ? await response.json()
            : { error: await response.text() };
        
        if (result.success) {
            closeCreateRoomModal();
            showNotification('Success', 'Chat room created successfully!', 'success');
            setTimeout(() => location.reload(), 700); // Refresh to show new room
        } else {
            // Show validation errors
            if (result.errors) {
                let errorMessage = 'Validation errors:<br>';
                Object.keys(result.errors).forEach(field => {
                    errorMessage += `<strong>${field}:</strong> ${result.errors[field].join(', ')}<br>`;
                });
                showNotification('Validation Error', errorMessage, 'error');
            } else {
                showNotification('Error', result.error || 'Failed to create chat room', 'error');
            }
        }
    } catch (error) {
        console.error('Error creating room:', error);
        HTMLFormElement.prototype.submit.call(this);
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Create Room';
    }
});

// Enhanced chat features

// Participant management functions
function showAddParticipantsModal() {
    if (!currentRoomId) return;
    
    document.getElementById('addParticipantsModal').classList.remove('hidden');
    loadAvailableUsers();
}

function closeAddParticipantsModal() {
    document.getElementById('addParticipantsModal').classList.add('hidden');
    document.getElementById('addParticipantsForm').reset();
}

function showParticipants() {
    if (!currentRoomId) return;
    
    document.getElementById('participantsModal').classList.remove('hidden');
    loadParticipants();
}

function closeParticipantsModal() {
    document.getElementById('participantsModal').classList.add('hidden');
}

// Load available users for adding to chat room
async function loadAvailableUsers() {
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/available-users`);
        const data = await response.json();
        
        const container = document.getElementById('availableUsersList');
        
        if (data.success && data.users && data.users.length > 0) {
            container.innerHTML = data.users.map(user => `
                <div class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded">
                    <input type="checkbox" id="user_${user.id}" value="${user.id}" class="rounded border-gray-300">
                    <label for="user_${user.id}" class="flex-1 cursor-pointer">
                        <div class="font-medium text-gray-900">${user.name}</div>
                        <div class="text-sm text-gray-500">${user.email} (${user.role})</div>
                    </label>
                </div>
            `).join('');
        } else {
            let message = 'No available users to add';
            if (data.error) {
                message = `Error: ${data.error}`;
                console.error('API Error:', data.error);
            } else if (data.users && data.users.length === 0) {
                message = 'All verified users are already participants';
            }
            container.innerHTML = `<p class="text-gray-500 text-center py-4">${message}</p>`;
        }
    } catch (error) {
        console.error('Error loading available users:', error);
        document.getElementById('availableUsersList').innerHTML = '<p class="text-red-500 text-center py-4">Network error loading users</p>';
    }
}

// Load current participants
async function loadParticipants() {
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}`);
        const data = await response.json();
        
        const container = document.getElementById('participantsList');
        
        if (data.chat_room && data.chat_room.participants) {
            container.innerHTML = data.chat_room.participants.map(participant => `
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                            ${participant.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">${participant.name}</div>
                            <div class="text-sm text-gray-500">${participant.pivot.role}</div>
                        </div>
                    </div>
                    ${participant.pivot.role !== 'creator' ? `
                        <button onclick="removeParticipant(${participant.id})" class="text-red-500 hover:text-red-700 text-sm">
                            Remove
                        </button>
                    ` : ''}
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading participants:', error);
    }
}

// Add participants form submission
document.getElementById('addParticipantsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const selectedUsers = Array.from(document.querySelectorAll('#availableUsersList input[type="checkbox"]:checked'))
                              .map(checkbox => parseInt(checkbox.value));
    
    if (selectedUsers.length === 0) {
        showNotification('Selection Required', 'Please select at least one user to add', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/participants`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ user_ids: selectedUsers })
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeAddParticipantsModal();
            showNotification('Success', result.message, 'success');
        } else {
            showNotification('Error', result.error || 'Failed to add participants', 'error');
        }
    } catch (error) {
        console.error('Error adding participants:', error);
        showNotification('Network Error', 'Failed to add participants. Please check your connection.', 'error');
    }
});

// Remove participant
async function removeParticipant(userId) {
    showConfirmation(
        'Remove Participant',
        'Are you sure you want to remove this participant from the chat room?',
        () => performRemoveParticipant(userId),
        'Remove',
        'danger'
    );
}

async function performRemoveParticipant(userId) {
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/participants`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success', result.message, 'success');
            loadParticipants(); // Refresh the list
        } else {
            showNotification('Error', result.error || 'Failed to remove participant', 'error');
        }
    } catch (error) {
        console.error('Error removing participant:', error);
        showNotification('Network Error', 'Failed to remove participant. Please check your connection.', 'error');
    }
}

// Delete message function (called from modal)
async function deleteMessage(messageId, deleteFor = 'self') {
    if (!messageId) {
        showNotification('Error', 'Invalid message ID', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/messages/${messageId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ delete_for: deleteFor })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadMessages(currentRoomId); // Refresh messages
            showNotification('Success', 'Message deleted successfully', 'success');
        } else {
            showNotification('Error', result.error || 'Failed to delete message', 'error');
        }
    } catch (error) {
        console.error('Error deleting message:', error);
        showNotification('Network Error', 'Failed to delete message. Please check your connection.', 'error');
    }
}

// Mark messages as seen
async function markMessagesAsSeen(messageIds) {
    if (!messageIds.length) return;
    
    try {
        await fetch(`/chat/rooms/${currentRoomId}/messages/seen`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ message_ids: messageIds })
        });
    } catch (error) {
        console.error('Error marking messages as seen:', error);
    }
}

// Emoji reaction functions
let currentMessageIdForReaction = null;

function showEmojiPicker(messageId) {
    currentMessageIdForReaction = messageId;
    document.getElementById('emojiPickerModal').classList.remove('hidden');
}

function closeEmojiPicker() {
    document.getElementById('emojiPickerModal').classList.add('hidden');
    currentMessageIdForReaction = null;
}

async function addReaction(emoji) {
    if (!currentMessageIdForReaction) return;
    
    await toggleReaction(currentMessageIdForReaction, emoji);
    closeEmojiPicker();
}

async function toggleReaction(messageId, emoji) {
    if (!currentRoomId) return;
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/messages/${messageId}/reactions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ emoji: emoji })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update the message reactions in real-time
            updateMessageReactions(messageId, result.reactions);
        } else {
            showNotification('Error', result.error || 'Failed to toggle reaction', 'error');
        }
    } catch (error) {
        console.error('Error toggling reaction:', error);
        showNotification('Network Error', 'Failed to toggle reaction. Please check your connection.', 'error');
    }
}

function updateMessageReactions(messageId, reactions) {
    // Find the message element and update its reactions
    const messageElements = document.querySelectorAll('[data-message-id]');
    
    messageElements.forEach(element => {
        if (element.dataset.messageId == messageId) {
            const reactionsContainer = element.querySelector('.reactions-container');
            if (reactionsContainer) {
                reactionsContainer.remove();
            }
            
            if (reactions && reactions.length > 0) {
                const currentUserId = {{ Auth::id() }};
                const reactionsHtml = `
                    <div class="reactions-container flex flex-wrap gap-1 mt-2">
                        ${reactions.map(reaction => `
                            <button onclick="toggleReaction(${messageId}, '${reaction.emoji}')" 
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs border transition-colors ${reaction.user_ids.includes(currentUserId) ? 'bg-blue-100 border-blue-300 text-blue-800' : 'bg-gray-100 border-gray-300 text-gray-600 hover:bg-gray-200'}"
                                    title="${reaction.users.map(u => u.name).join(', ')}">
                                ${reaction.emoji} ${reaction.count}
                            </button>
                        `).join('')}
                    </div>
                `;
                
                const messageContent = element.querySelector('.message-content');
                if (messageContent) {
                    messageContent.insertAdjacentHTML('beforeend', reactionsHtml);
                }
            }
        }
    });
}

// New features functions

// Leave chat room
async function leaveChatRoom() {
    if (!currentRoomId) return;
    
    showConfirmation(
        'Leave Chat Room',
        'Are you sure you want to leave this chat room? You will no longer receive messages from this room.',
        () => performLeaveChatRoom(),
        'Leave Room',
        'danger'
    );
}

async function performLeaveChatRoom() {
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/leave`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success', result.message, 'success');
            setTimeout(() => location.reload(), 1500); // Refresh to update room list
        } else {
            showNotification('Error', result.error || 'Failed to leave chat room', 'error');
        }
    } catch (error) {
        console.error('Error leaving chat room:', error);
        showNotification('Network Error', 'Failed to leave chat room. Please check your connection.', 'error');
    }
}

// Delete chat room
async function deleteChatRoom() {
    if (!currentRoomId) return;
    
    showConfirmation(
        'Delete Chat Room',
        'Are you sure you want to delete this chat room? <br><br><strong>This action cannot be undone and all messages will be permanently deleted.</strong>',
        () => performDeleteChatRoom(),
        'Delete Room',
        'danger'
    );
}

async function performDeleteChatRoom() {
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Success', result.message, 'success');
            setTimeout(() => location.reload(), 1500); // Refresh to update room list
        } else {
            showNotification('Error', result.error || 'Failed to delete chat room', 'error');
        }
    } catch (error) {
        console.error('Error deleting chat room:', error);
        showNotification('Network Error', 'Failed to delete chat room. Please check your connection.', 'error');
    }
}

// Typing indicator functions
let typingTimeout;
let isTyping = false;

// Send typing status
async function sendTypingStatus(typing) {
    if (!currentRoomId) return;
    
    try {
        await fetch(`/chat/rooms/${currentRoomId}/typing`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ is_typing: typing })
        });
    } catch (error) {
        console.error('Error sending typing status:', error);
    }
}

// Get typing users
async function getTypingUsers() {
    if (!currentRoomId) return;
    
    try {
        const response = await fetch(`/chat/rooms/${currentRoomId}/typing`);
        const result = await response.json();
        
        if (result.success) {
            displayTypingIndicator(result.typing_users);
        }
    } catch (error) {
        console.error('Error getting typing users:', error);
    }
}

// Display typing indicator
function displayTypingIndicator(typingUsers) {
    const indicator = document.getElementById('typingIndicator');
    
    if (typingUsers.length === 0) {
        indicator.classList.add('hidden');
        return;
    }
    
    let message = '';
    if (typingUsers.length === 1) {
        message = `${typingUsers[0].user_name} is typing...`;
    } else if (typingUsers.length === 2) {
        message = `${typingUsers[0].user_name} and ${typingUsers[1].user_name} are typing...`;
    } else {
        message = `${typingUsers[0].user_name} and ${typingUsers.length - 1} others are typing...`;
    }
    
    indicator.textContent = message;
    indicator.classList.remove('hidden');
}

// Handle typing in message input
function handleTyping() {
    if (!isTyping) {
        isTyping = true;
        sendTypingStatus(true);
    }
    
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        isTyping = false;
        sendTypingStatus(false);
    }, 3000); // Stop typing indicator after 3 seconds of inactivity
}

// Delete button visibility is now handled in loadChatRoom function

// Typing indicator polling is now handled in selectChatRoom function

// Add typing event listener to message input
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('messageText');
    if (messageInput) {
        messageInput.addEventListener('input', handleTyping);
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                // Stop typing when sending message
                if (isTyping) {
                    isTyping = false;
                    sendTypingStatus(false);
                }
            }
        });
    }
});
</script>
</div>
</x-app-layout>
