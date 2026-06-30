<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Teacher Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Welcome, {{ Auth::user()->firstname }} {{ Auth::user()->lastname }}!</h3>
                    <p class="text-gray-600 mb-6">You are logged in as a Teacher.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-blue-800">My Students</h4>
                            <p class="text-2xl font-bold text-blue-600">{{ Auth::user()->students()->count() }}</p>
                            <a href="{{ route('advisers.my-students') }}" class="text-blue-600 hover:text-blue-800 text-sm">View all →</a>
                        </div>
                        
                        <div class="bg-yellow-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-yellow-800">Pending Requests</h4>
                            <p class="text-2xl font-bold text-yellow-600">{{ Auth::user()->studentRequests()->pending()->count() }}</p>
                            <a href="{{ route('advisers.pending-requests') }}" class="text-yellow-600 hover:text-yellow-800 text-sm">Review →</a>
                        </div>
                        
                        <div class="bg-green-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-green-800">Student Projects</h4>
                            <p class="text-2xl font-bold text-green-600">{{ Auth::user()->accessibleProjects()->count() - Auth::user()->ownedProjects()->count() }}</p>
                            <a href="{{ route('projects.index') }}" class="text-green-600 hover:text-green-800 text-sm">View Projects →</a>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Your Information:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                            <p><strong>Campus:</strong> {{ Auth::user()->campus }}</p>
                            <p><strong>Department:</strong> {{ Auth::user()->course }}</p>
                            <p><strong>Section:</strong> {{ Auth::user()->section }}</p>
                            <p><strong>Status:</strong> 
                                <span class="px-2 py-1 rounded text-xs {{ Auth::user()->status === 'Verified' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ Auth::user()->status }}
                                </span>
                            </p>
                        </div>
                    </div>

                    @if(Auth::user()->studentRequests()->pending()->count() > 0)
                        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 mb-2">Recent Student Requests</h4>
                            <div class="space-y-2">
                                @foreach(Auth::user()->studentRequests()->pending()->with('student')->latest()->take(3)->get() as $request)
                                    <div class="flex items-center justify-between bg-white p-3 rounded border">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $request->student->name }}</p>
                                            <p class="text-sm text-gray-600">{{ $request->student->course }} - {{ $request->student->section }}</p>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $request->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('advisers.pending-requests') }}" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">View all pending requests →</a>
                            </div>
                        </div>
                    @endif

                    @php
                        $studentProjects = Auth::user()->accessibleProjects()
                            ->where('owner_id', '!=', Auth::id())
                            ->with(['owner', 'documents'])
                            ->latest('updated_at')
                            ->take(3)
                            ->get();
                    @endphp

                    @if($studentProjects->count() > 0)
                        <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">Recent Student Projects</h4>
                            <div class="space-y-2">
                                @foreach($studentProjects as $project)
                                    <div class="flex items-center justify-between bg-white p-3 rounded border">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $project->title }}</p>
                                            <p class="text-sm text-gray-600">by {{ $project->owner->name }} • {{ $project->documents()->count() }} files</p>
                                        </div>
                                        <div class="text-right">
                                            <a href="{{ route('projects.show', $project) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">View →</a>
                                            <p class="text-xs text-gray-500">{{ $project->updated_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('projects.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">View all student projects →</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
