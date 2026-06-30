<div class="bg-white shadow-sm rounded-lg border border-gray-200 p-12 text-center">
    <div class="w-20 h-20 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 100-8 4 4 0 000 8zm8 0a4 4 0 100-8 4 4 0 000 8z"></path>
        </svg>
    </div>
    <h3 class="text-xl font-semibold text-gray-900 mb-3">You haven't created a group yet</h3>
    <p class="text-gray-600 mb-8 max-w-md mx-auto">
        Create your group first so you can manage projects, find an adviser, schedule meetings, and make chat rooms.
    </p>
    <a href="{{ route('group-description.show') }}"
       class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
        Create Now
    </a>
</div>
