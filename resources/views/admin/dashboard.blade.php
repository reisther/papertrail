<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Welcome, {{ Auth::user()->firstname }} {{ Auth::user()->lastname }}!</h3>
                    <p class="text-gray-600 mb-6">You are logged in as an Administrator.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-blue-800">Pending Registrations</h4>
                            <p class="text-2xl font-bold text-blue-600">{{ \App\Models\User::where('status', 'Pending')->count() }}</p>
                            <a href="{{ route('admin.pending-users') }}" class="text-blue-600 hover:text-blue-800 text-sm">Review Documents →</a>
                        </div>
                        
                        <div class="bg-green-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-green-800">Total Students</h4>
                            <p class="text-2xl font-bold text-green-600">{{ \App\Models\User::where('role', 'Student')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Student']) }}" class="text-green-600 hover:text-green-800 text-sm">Manage →</a>
                        </div>

                        <div class="bg-indigo-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-indigo-800">Total Leaders</h4>
                            <p class="text-2xl font-bold text-indigo-600">{{ \App\Models\User::where('role', 'Leader')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Leader']) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">Manage →</a>
                        </div>
                        
                        <div class="bg-purple-50 p-6 rounded-lg">
                            <h4 class="font-semibold text-purple-800">Total Teachers</h4>
                            <p class="text-2xl font-bold text-purple-600">{{ \App\Models\User::where('role', 'Teacher')->where('status', 'Verified')->count() }}</p>
                            <a href="{{ route('admin.all-users', ['role' => 'Teacher']) }}" class="text-purple-600 hover:text-purple-800 text-sm">Manage →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
