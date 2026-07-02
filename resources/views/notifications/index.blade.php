<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Recent updates</h3>
                    <p class="text-sm text-gray-500">Student requests and unread chat activity appear here.</p>
                </div>

                @if($studentRequestNotifications->isNotEmpty() || $chatNotifications->isNotEmpty())
                    <div class="divide-y divide-gray-100">
                        @foreach($studentRequestNotifications as $requestNotification)
                            @php
                                $student = $requestNotification->student;
                                $studentName = $student?->name ?? 'A student';
                                $project = $student?->ownedProjects?->first();
                                $projectTitle = $project?->title ?? 'Student group';
                            @endphp

                            <a href="{{ route('advisers.pending-requests') }}" class="block px-5 py-4 hover:bg-amber-50 transition">
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3M12 7a4 4 0 11-8 0 4 4 0 018 0zM4 20a6 6 0 0112 0"></path>
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate">New adviser request</p>
                                                <p class="mt-1 text-sm text-gray-600 truncate">
                                                    {{ $studentName }} from {{ $projectTitle }} requested you as adviser.
                                                </p>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-amber-500 px-2 py-1 text-xs font-semibold text-white">
                                                Request
                                            </span>
                                        </div>

                                        <p class="mt-2 text-xs text-gray-400">
                                            {{ $requestNotification->created_at?->diffForHumans() ?? 'Pending request' }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach

                        @foreach($chatNotifications as $notification)
                            @php
                                $room = $notification['room'];
                                $latestMessage = $notification['latest_message'];
                                $sender = $latestMessage?->user
                                    ? trim($latestMessage->user->firstname . ' ' . $latestMessage->user->lastname)
                                    : 'Someone';
                                $preview = $latestMessage?->file_name
                                    ?: ($latestMessage?->message ?: 'New message');
                            @endphp

                            <a href="{{ route('chat.index', ['room' => $room->id]) }}" class="block px-5 py-4 hover:bg-blue-50 transition">
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.77 9.77 0 01-4.255-.949L3 20l1.395-3.72A7.651 7.651 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate">{{ $room->name }}</p>
                                                <p class="mt-1 text-sm text-gray-600 truncate">
                                                    {{ $sender }}: {{ \Illuminate\Support\Str::limit($preview, 90) }}
                                                </p>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-red-600 px-2 py-1 text-xs font-semibold text-white">
                                                {{ $notification['unread_count'] > 99 ? '99+' : $notification['unread_count'] }}
                                            </span>
                                        </div>

                                        <p class="mt-2 text-xs text-gray-400">
                                            {{ $latestMessage?->created_at?->diffForHumans() ?? 'Unread messages' }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-10 text-center">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900">No notifications yet</h3>
                        <p class="mt-2 text-gray-600">Student requests and unread chat activity will appear here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
