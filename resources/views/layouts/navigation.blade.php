@php
    $chatUnreadCount = 0;
    $notificationUnreadCount = 0;

    if (Auth::check()) {
        $chatUnreadCount = Auth::user()->chatRooms()
            ->where('chat_rooms.is_active', true)
            ->get()
            ->sum(fn ($room) => $room->getUnreadCountForUser(Auth::user()));

        $notificationUnreadCount = $chatUnreadCount;
    }
@endphp

 <!-- Header Navigation -->
    <header class="bg-white border-b border-gray-200" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd"
                                    d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <span class="ml-3 text-xl font-semibold text-gray-900">PaperTrail</span>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="hidden md:flex space-x-8">
                    @auth
                        @if(Auth::user()->role === 'Admin')
                            <a href="{{ route('admin.dashboard') }}"
                                class="text-gray-900 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Dashboard</a>
                        @elseif(Auth::user()->role === 'Teacher')
                            <a href="{{ route('teacher.dashboard') }}"
                                class="text-gray-900 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('dashboard') }}"
                                class="text-gray-900 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Dashboard</a>
                        @endif

                        @if(Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher())
                            <a href="{{ route('projects.index') }}"
                                class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Projects</a>
                        @endif

                        @if(Auth::user()->canLeadGroup())
                            <a href="{{ route('advisers.title-submission') }}"
                                class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Find Advisers</a>
                        @elseif(Auth::user()->isTeacher())
                            <a href="{{ route('advisers.pending-requests') }}"
                                class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Student Requests</a>
                        @elseif(Auth::user()->role === 'Admin')
                            <a href="{{ route('admin.pending-users') }}"
                                class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Verify Users</a>
                            <a href="{{ route('admin.all-users') }}"
                                class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">User Management</a>
                        @endif

                        @if(Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher())
                            <a href="{{ route('defense-schedule.index') }}"
                                class="text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Defense
                                Schedule</a>
                        @endif

                        @if(Auth::user()->isStudentGroupRole() || Auth::user()->isTeacher())
                            <a href="{{ route('chat.index') }}"
                                class="relative inline-flex items-center text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">
                                Chat
                                @if($chatUnreadCount > 0)
                                    <span class="ml-2 min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                        {{ $chatUnreadCount > 99 ? '99+' : $chatUnreadCount }}
                                    </span>
                                @endif
                            </a>
                        @endif

                        <a href="{{ route('notifications.index') }}"
                            class="relative inline-flex items-center text-gray-500 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">
                            Notifications
                            @if($notificationUnreadCount > 0)
                                <span class="ml-2 min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                            class="text-gray-900 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Dashboard</a>
                    @endauth
                </nav>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 focus:outline-none">
                            @auth
                                @if(Auth::user()->profile_picture_path)
                                    <img src="{{ route('profile.picture', Auth::user()) }}?v={{ Auth::user()->updated_at?->timestamp }}"
                                         alt="{{ Auth::user()->name }}"
                                         class="w-8 h-8 rounded-full object-cover border border-gray-200">
                                @else
                                    <div class="w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-sm font-semibold">
                                        {{ strtoupper(substr(Auth::user()->firstname, 0, 1) . substr(Auth::user()->lastname, 0, 1)) }}
                                    </div>
                                @endif
                            @else
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @endauth
                            <span class="hidden md:block text-sm font-medium">
                                @auth
                                    {{ Auth::user()->name }}
                                @else
                                    Guest
                                @endauth
                            </span>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            @auth
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <div class="border-t border-gray-100"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Logout
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Login</a>
                                <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Register</a>
                            @endauth
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button type="button" @click="mobileOpen = !mobileOpen"
                            class="text-gray-500 hover:text-gray-600 focus:outline-none focus:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <nav x-show="mobileOpen" x-transition class="md:hidden border-t border-gray-100 py-3 space-y-1">
                @auth
                    @if(Auth::user()->role === 'Admin')
                        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-900 hover:text-blue-600">Dashboard</a>
                        <a href="{{ route('admin.pending-users') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Verify Users</a>
                        <a href="{{ route('admin.all-users') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">User Management</a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Notifications</span>
                            @if($notificationUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @elseif(Auth::user()->role === 'Teacher')
                        <a href="{{ route('teacher.dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-900 hover:text-blue-600">Dashboard</a>
                        <a href="{{ route('projects.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Projects</a>
                        <a href="{{ route('advisers.pending-requests') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Student Requests</a>
                        <a href="{{ route('defense-schedule.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Defense Schedule</a>
                        <a href="{{ route('chat.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Chat</span>
                            @if($chatUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $chatUnreadCount > 99 ? '99+' : $chatUnreadCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Notifications</span>
                            @if($notificationUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-900 hover:text-blue-600">Dashboard</a>
                        <a href="{{ route('projects.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Projects</a>
                        @if(Auth::user()->canLeadGroup())
                            <a href="{{ route('advisers.title-submission') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Find Advisers</a>
                        @endif
                        <a href="{{ route('defense-schedule.index') }}" class="block px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">Defense Schedule</a>
                        <a href="{{ route('chat.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Chat</span>
                            @if($chatUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $chatUnreadCount > 99 ? '99+' : $chatUnreadCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-500 hover:text-blue-600">
                            <span>Notifications</span>
                            @if($notificationUnreadCount > 0)
                                <span class="min-w-5 h-5 rounded-full bg-red-600 px-1.5 text-center text-[11px] font-semibold leading-5 text-white">
                                    {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
                                </span>
                            @endif
                        </a>
                    @endif
                @else
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-sm font-medium text-gray-900 hover:text-blue-600">Dashboard</a>
                @endauth
            </nav>
        </div>
    </header>
